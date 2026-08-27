<?php

namespace Tests\Feature;

use App\Services\RecordObservedEvent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RecordObservedEventTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_records_an_event_and_updates_its_order_projection(): void
    {
        $recorded = app(RecordObservedEvent::class)->handle($this->event());

        $this->assertTrue($recorded);
        $this->assertDatabaseHas('observed_events', ['event_id' => 'event-9001', 'event_type' => 'order.rider_assigned']);
        $this->assertDatabaseHas('order_projections', ['order_id' => 9001, 'status' => 'rider_assigned', 'rider_id' => 42]);
    }

    public function test_ignores_a_duplicate_event_without_creating_another_projection(): void
    {
        app(RecordObservedEvent::class)->handle($this->event());

        $recorded = app(RecordObservedEvent::class)->handle($this->event());

        $this->assertFalse($recorded);
        $this->assertDatabaseCount('observed_events', 1);
        $this->assertDatabaseCount('order_projections', 1);
    }

    public function test_records_consumer_failure_diagnostics_for_metrics(): void
    {
        app(RecordObservedEvent::class)->handle([
            'event_id' => 'incident-9001',
            'event_type' => 'messaging.message_dead_lettered',
            'occurred_at' => '2026-08-26T13:00:00+00:00',
            'data' => [
                'service' => 'notification-service',
                'source_event_id' => 'event-9001',
                'source_event_type' => 'order.placed',
                'retry_count' => 3,
                'error' => 'SMTP unavailable',
            ],
        ]);

        $this->assertDatabaseHas('consumer_incidents', ['service' => 'notification-service', 'outcome' => 'dead_lettered']);
        $this->assertDatabaseHas('consumer_health', ['service' => 'notification-service', 'last_error' => 'SMTP unavailable']);
    }

    public function test_consumer_diagnostic_does_not_overwrite_the_order_projection(): void
    {
        $recorder = app(RecordObservedEvent::class);
        $recorder->handle($this->event());

        $recorder->handle([
            'event_id' => 'diagnostic-9001',
            'event_type' => 'messaging.message_processed',
            'event_version' => 1,
            'occurred_at' => '2026-08-26T13:01:00+00:00',
            'data' => [
                'service' => 'rider-service',
                'source_event_id' => 'event-9001',
                'source_event_type' => 'order.rider_assigned',
                'retry_count' => 0,
                'order' => ['id' => 9001],
            ],
        ]);

        $this->assertDatabaseHas('order_projections', [
            'order_id' => 9001,
            'status' => 'rider_assigned',
            'restaurant_id' => 11,
            'client_id' => 12,
            'rider_id' => 42,
            'last_event_type' => 'order.rider_assigned',
        ]);
    }

    public function test_late_domain_event_does_not_move_the_projection_backwards(): void
    {
        $recorder = app(RecordObservedEvent::class);
        $recorder->handle($this->event());

        $lateEvent = $this->event();
        $lateEvent['event_id'] = 'event-9001-late';
        $lateEvent['event_type'] = 'order.placed';
        $lateEvent['aggregate_version'] = 1;
        $lateEvent['data']['order']['status'] = 'placed';
        $lateEvent['data']['rider_id'] = null;
        $recorder->handle($lateEvent);

        $this->assertDatabaseHas('order_projections', [
            'order_id' => 9001,
            'status' => 'rider_assigned',
            'aggregate_version' => 3,
            'rider_id' => 42,
        ]);
    }

    /** @return array<string, mixed> */
    private function event(): array
    {
        return [
            'event_id' => 'event-9001',
            'event_type' => 'order.rider_assigned',
            'event_version' => 1,
            'aggregate_type' => 'order',
            'aggregate_id' => '9001',
            'aggregate_version' => 3,
            'occurred_at' => '2026-08-26T13:00:00+00:00',
            'data' => [
                'order' => ['id' => 9001, 'status' => 'rider_assigned'],
                'restaurant' => ['id' => 11],
                'client' => ['id' => 12],
                'rider_id' => 42,
            ],
        ];
    }
}
