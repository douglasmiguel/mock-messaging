<?php

namespace App\Services;

use App\Mail\OrderNotificationMail;
use App\Models\ActionToken;
use App\Models\NotificationDelivery;
use App\Models\OrderSnapshot;
use App\Models\ProcessedEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationEventHandler
{
    /** @param array<string, mixed> $event */
    public function handle(array $event): void
    {
        $eventId = (string) ($event['event_id'] ?? '');
        $eventType = (string) ($event['event_type'] ?? '');
        $data = $event['data'] ?? [];
        $order = $data['order'] ?? [];
        $client = $data['client'] ?? [];
        $restaurant = $data['restaurant'] ?? [];
        $orderId = (int) ($order['id'] ?? 0);
        $eventVersion = (int) ($event['event_version'] ?? 0);
        $aggregateType = (string) ($event['aggregate_type'] ?? '');
        $aggregateId = (string) ($event['aggregate_id'] ?? '');
        $aggregateVersion = (int) ($event['aggregate_version'] ?? 0);

        if ($eventId === '' || $eventType === '' || $orderId < 1 || $eventVersion !== 1 || $aggregateType !== 'order' || $aggregateId !== (string) $orderId || $aggregateVersion < 1) {
            throw new \InvalidArgumentException('The order event is missing required fields.');
        }

        $isNewEvent = DB::transaction(function () use ($eventId, $eventType, $data, $orderId, $client, $restaurant, $aggregateVersion): bool {
            if (ProcessedEvent::query()->where('event_id', $eventId)->exists()) {
                return false;
            }

            $snapshot = $this->updateSnapshot($orderId, $aggregateVersion, $data, $client, $restaurant);

            if (in_array($eventType, ['order.cancelled', 'order.refused'], true)) {
                ActionToken::query()->where('order_id', $orderId)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }

            $this->queueDeliveries($eventId, $eventType, $snapshot);

            ProcessedEvent::query()->create(['event_id' => $eventId, 'event_type' => $eventType, 'order_id' => $orderId]);

            return true;
        });

        // A redelivery resumes only durable, pending work before RabbitMQ is acknowledged.
        $this->sendPendingDeliveries($eventId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $client
     * @param  array<string, mixed>  $restaurant
     */
    private function updateSnapshot(int $orderId, int $aggregateVersion, array $data, array $client, array $restaurant): OrderSnapshot
    {
        $snapshot = OrderSnapshot::query()->where('order_id', $orderId)->lockForUpdate()->first();
        $attributes = [
            'status' => (string) data_get($data, 'order.status', 'unknown'),
            'aggregate_version' => $aggregateVersion,
            'restaurant_name' => (string) ($restaurant['name'] ?? 'Restaurant'),
            'restaurant_email' => (string) ($restaurant['email'] ?? ''),
            'client_name' => (string) ($client['name'] ?? 'Customer'),
            'client_email' => (string) ($client['email'] ?? ''),
            'payload' => $data,
        ];

        if ($snapshot === null) {
            return OrderSnapshot::query()->create(['order_id' => $orderId, ...$attributes]);
        }

        if ($aggregateVersion > $snapshot->aggregate_version) {
            $snapshot->update($attributes);
        }

        return $snapshot->fresh() ?? $snapshot;
    }

    /** @return array{record: ActionToken, token: string} */
    private function tokenFor(int $orderId, string $actor): array
    {
        ActionToken::query()->where('order_id', $orderId)->where('actor', $actor)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $token = Str::random(64);

        return [
            'record' => ActionToken::query()->create([
                'order_id' => $orderId,
                'actor' => $actor,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(config('messaging.action_token_ttl_hours')),
            ]),
            'token' => $token,
        ];
    }

    private function queueDeliveries(
        string $eventId,
        string $eventType,
        OrderSnapshot $snapshot,
    ): void {
        match ($eventType) {
            'order.placed' => $this->queuePlacedDeliveries($eventId, $snapshot, $this->tokenFor($snapshot->order_id, 'restaurant'), $this->tokenFor($snapshot->order_id, 'client')),
            'order.accepted' => $this->queueClientDelivery($eventId, 'client_order_accepted', $snapshot, $this->tokenFor($snapshot->order_id, 'client'),
                'Your order #'.$snapshot->order_id.' was accepted', 'Restaurant confirmed your order',
                'Your restaurant has accepted the order and is preparing it.', 'View or cancel order'),
            'order.rider_assigned' => $this->queueClientDelivery($eventId, 'client_rider_assigned', $snapshot, $this->tokenFor($snapshot->order_id, 'client'),
                'Your rider is on the way for order #'.$snapshot->order_id, 'Your rider is on the way',
                'A rider has been assigned to your order. Let us know if there is a delivery or item issue.', 'Raise an issue'),
            'order.refused' => $this->queueClientDelivery($eventId, 'client_order_refused', $snapshot, $this->tokenFor($snapshot->order_id, 'client'),
                'Order #'.$snapshot->order_id.' could not be accepted', 'Your order was refused',
                'The restaurant was unable to accept this order.', 'View order'),
            default => null,
        };
    }

    private function queuePlacedDeliveries(
        string $eventId,
        OrderSnapshot $snapshot,
        array $restaurantToken,
        array $clientToken,
    ): void {
        $this->queueDelivery(
            $eventId,
            $snapshot->restaurant_email,
            'restaurant_order_placed',
            'New order #'.$snapshot->order_id,
            'New order received',
            'Please confirm or refuse this order, then mark it ready when the rider can collect it.',
            'Review restaurant order',
            route('restaurant-orders.show', $restaurantToken['token']),
        );

        $this->queueClientDelivery($eventId, 'client_order_placed', $snapshot, $clientToken,
            'We received your order #'.$snapshot->order_id, 'Your order was placed',
            'The restaurant has been notified. You can cancel while it is still being prepared.', 'View or cancel order');
    }

    private function queueClientDelivery(
        string $eventId,
        string $template,
        OrderSnapshot $snapshot,
        array $token,
        string $subject,
        string $title,
        string $message,
        string $label,
    ): void {
        $this->queueDelivery(
            $eventId,
            $snapshot->client_email,
            $template,
            $subject,
            $title,
            $message,
            $label,
            route('client-orders.show', $token['token']),
        );
    }

    private function queueDelivery(
        string $eventId,
        string $recipient,
        string $template,
        string $subject,
        string $title,
        string $message,
        string $label,
        string $actionUrl,
    ): void {
        if ($recipient === '') {
            return;
        }

        NotificationDelivery::query()->firstOrCreate(
            ['event_id' => $eventId, 'recipient' => $recipient, 'template' => $template],
            [
                'subject' => $subject,
                'title' => $title,
                'message' => $message,
                'action_label' => $label,
                'action_url' => $actionUrl,
            ],
        );
    }

    private function sendPendingDeliveries(string $eventId): void
    {
        NotificationDelivery::query()
            ->where('event_id', $eventId)
            ->whereNull('sent_at')
            ->orderBy('id')
            ->each(function (NotificationDelivery $delivery): void {
                Mail::to($delivery->recipient)->send(new OrderNotificationMail(
                    $delivery->subject,
                    $delivery->title,
                    $delivery->message,
                    $delivery->action_label,
                    $delivery->action_url,
                ));

                $delivery->forceFill(['sent_at' => now()])->save();
            });
    }
}
