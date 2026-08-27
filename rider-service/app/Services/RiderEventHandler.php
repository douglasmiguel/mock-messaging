<?php

namespace App\Services;

use App\Models\ProcessedEvent;
use App\Models\Rider;
use App\Models\RiderAssignment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RiderEventHandler
{
    /** @param array<string, mixed> $event */
    public function handle(array $event): void
    {
        $eventId = (string) ($event['event_id'] ?? '');
        $eventType = (string) ($event['event_type'] ?? '');
        $orderId = (int) data_get($event, 'data.order.id', 0);
        $eventVersion = (int) ($event['event_version'] ?? 0);
        $aggregateType = (string) ($event['aggregate_type'] ?? '');
        $aggregateId = (string) ($event['aggregate_id'] ?? '');
        $aggregateVersion = (int) ($event['aggregate_version'] ?? 0);

        if ($eventId === '' || $eventType === '' || $orderId < 1 || $eventVersion !== 1 || $aggregateType !== 'order' || $aggregateId !== (string) $orderId || $aggregateVersion < 1) {
            throw new \InvalidArgumentException('The order event is missing required fields.');
        }

        if (ProcessedEvent::query()->where('event_id', $eventId)->exists()) {
            return;
        }

        if ($eventType === 'order.delivered') {
            RiderAssignment::query()->where('order_id', $orderId)->whereIn('status', ['pending', 'confirmed'])->update(['status' => 'completed']);
            $this->markProcessed($eventId, $eventType, $orderId);

            return;
        }

        if ($eventType !== 'order.ready_for_pickup') {
            $this->markProcessed($eventId, $eventType, $orderId);

            return;
        }

        $assignment = DB::transaction(function () use ($orderId): RiderAssignment {
            $existing = RiderAssignment::query()->where('order_id', $orderId)->first();
            if ($existing !== null) {
                return $existing;
            }

            $rider = Rider::query()
                ->whereDoesntHave('assignments', fn ($query) => $query->whereIn('status', ['pending', 'confirmed']))
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($rider === null) {
                throw new \RuntimeException('No riders are currently available.');
            }

            return RiderAssignment::query()->create([
                'assignment_id' => (string) Str::ulid(),
                'order_id' => $orderId,
                'rider_id' => $rider->id,
                'status' => 'pending',
            ]);
        });

        $response = $this->orderServiceRequest()
            ->withHeader('X-Service-Key', config('messaging.service_key'))
            ->withHeader('X-Idempotency-Key', $assignment->assignment_id)
            ->post(config('messaging.order_service_url').'/api/v1/internal/orders/'.$orderId.'/rider-assignment', [
                'rider_id' => $assignment->rider_id,
            ]);

        if ($response->successful()) {
            $assignment->update(['status' => 'confirmed']);
        } elseif ($response->status() === 409 && $this->orderHasAssignment($orderId, $assignment->rider_id)) {
            $assignment->update(['status' => 'confirmed']);
        } else {
            throw new \RuntimeException('Order Service did not accept the rider assignment: '.$response->status());
        }

        $this->markProcessed($eventId, $eventType, $orderId);
    }

    private function markProcessed(string $eventId, string $eventType, int $orderId): void
    {
        ProcessedEvent::query()->create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'order_id' => $orderId,
        ]);
    }

    private function orderHasAssignment(int $orderId, int $riderId): bool
    {
        $response = $this->orderServiceRequest()
            ->withHeader('X-Service-Key', config('messaging.service_key'))
            ->get(config('messaging.order_service_url').'/api/v1/internal/orders/'.$orderId);

        return $response->successful()
            && $response->json('data.status') === 'rider_assigned'
            && (int) $response->json('data.rider_id') === $riderId;
    }

    private function orderServiceRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout(config('messaging.order_service_connect_timeout_seconds'))
            ->timeout(config('messaging.order_service_timeout_seconds'))
            ->retry([100, 500]);
    }
}
