<?php

namespace App\Services;

use App\Models\ConsumerIncident;
use App\Models\ObservedEvent;
use App\Models\OrderProjection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordObservedEvent
{
    public function __construct(private ConsumerHealthRecorder $consumerHealth) {}

    /** @param array<string, mixed> $event */
    public function handle(array $event): bool
    {
        $eventId = (string) Arr::get($event, 'event_id', '');
        $eventType = (string) Arr::get($event, 'event_type', '');
        $orderId = Arr::get($event, 'data.order.id');
        $eventVersion = (int) Arr::get($event, 'event_version', 0);
        $aggregateType = (string) Arr::get($event, 'aggregate_type', '');
        $aggregateId = (string) Arr::get($event, 'aggregate_id', '');
        $aggregateVersion = (int) Arr::get($event, 'aggregate_version', 0);

        if ($eventId === '' || $eventType === '') {
            throw new \InvalidArgumentException('The event is missing an event ID or event type.');
        }

        if (Str::startsWith($eventType, 'order.') && ($eventVersion !== 1 || $aggregateType !== 'order' || $aggregateId !== (string) $orderId || $aggregateVersion < 1)) {
            throw new \InvalidArgumentException('Order events require a positive aggregate version.');
        }

        return DB::transaction(function () use ($event, $eventId, $eventType, $orderId, $aggregateVersion): bool {
            if (ObservedEvent::query()->where('event_id', $eventId)->exists()) {
                return false;
            }

            $occurredAt = Carbon::parse(Arr::get($event, 'occurred_at', now()));
            ObservedEvent::query()->create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'event_version' => (int) Arr::get($event, 'event_version', 1),
                'order_id' => is_numeric($orderId) ? (int) $orderId : null,
                'payload' => $this->redactPayload($event),
                'occurred_at' => $occurredAt,
                'received_at' => now(),
            ]);

            if (is_numeric($orderId) && Str::startsWith($eventType, 'order.')) {
                $this->updateOrderProjection($event, (int) $orderId, $eventType, $occurredAt, $aggregateVersion);
            }

            $this->recordConsumerOutcome($event, $eventId, $eventType, $occurredAt);
            $this->consumerHealth->recordSuccess('observability-service');

            return true;
        });
    }

    /** @param array<string, mixed> $event */
    private function updateOrderProjection(array $event, int $orderId, string $eventType, Carbon $occurredAt, int $aggregateVersion): void
    {
        $projection = OrderProjection::query()->where('order_id', $orderId)->lockForUpdate()->first();
        $attributes = [
            'status' => (string) Arr::get($event, 'data.order.status', 'unknown'),
            'aggregate_version' => $aggregateVersion,
            'restaurant_id' => Arr::get($event, 'data.restaurant.id'),
            'client_id' => Arr::get($event, 'data.client.id'),
            'rider_id' => Arr::get($event, 'data.rider_id'),
            'last_event_type' => $eventType,
            'last_event_at' => $occurredAt,
        ];

        if ($projection === null) {
            OrderProjection::query()->create(['order_id' => $orderId, ...$attributes]);

            return;
        }

        if ($aggregateVersion > $projection->aggregate_version) {
            $projection->update($attributes);
        }
    }

    /** @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function redactPayload(array $event): array
    {
        Arr::forget($event, [
            'data.order.delivery_address',
            'data.order.order_price',
            'data.order.delivery_fee',
            'data.order.total_price',
            'data.order.items',
            'data.client.name',
            'data.client.email',
            'data.restaurant.name',
            'data.restaurant.email',
        ]);

        return $event;
    }

    /** @param array<string, mixed> $event */
    private function recordConsumerOutcome(array $event, string $eventId, string $eventType, Carbon $occurredAt): void
    {
        $outcome = match ($eventType) {
            'messaging.message_processed' => 'processed',
            'messaging.message_retry_scheduled' => 'retry_scheduled',
            'messaging.message_dead_lettered' => 'dead_lettered',
            default => null,
        };

        if ($outcome === null) {
            return;
        }

        $service = (string) Arr::get($event, 'data.service', 'unknown');
        ConsumerIncident::query()->create([
            'event_id' => $eventId,
            'service' => $service,
            'outcome' => $outcome,
            'source_event_id' => Arr::get($event, 'data.source_event_id'),
            'source_event_type' => Arr::get($event, 'data.source_event_type'),
            'order_id' => Arr::get($event, 'data.order.id'),
            'retry_count' => (int) Arr::get($event, 'data.retry_count', 0),
            'error' => Arr::get($event, 'data.error'),
            'occurred_at' => $occurredAt,
        ]);

        if ($outcome === 'processed') {
            $this->consumerHealth->recordSuccess($service);

            return;
        }

        $this->consumerHealth->recordFailure($service, (string) Arr::get($event, 'data.error', 'Message processing failed.'));
    }
}
