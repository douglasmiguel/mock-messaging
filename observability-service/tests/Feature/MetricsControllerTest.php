<?php

namespace Tests\Feature;

use App\Models\ConsumerHealth;
use App\Models\ConsumerIncident;
use App\Models\ObservedEvent;
use App\Models\OrderProjection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MetricsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renders_prometheus_metrics_for_events_and_order_statuses(): void
    {
        ObservedEvent::factory()->create([
            'event_type' => 'order.placed',
            'occurred_at' => '2026-08-26 13:00:00',
        ]);
        OrderProjection::factory()->create(['status' => 'placed']);
        ConsumerIncident::query()->create([
            'event_id' => 'incident-9001',
            'service' => 'notification-service',
            'outcome' => 'dead_lettered',
            'retry_count' => 3,
            'occurred_at' => now(),
        ]);
        ConsumerHealth::query()->create([
            'service' => 'notification-service',
            'last_success_at' => now(),
            'last_failure_at' => now(),
            'last_error' => 'Mail server unavailable',
        ]);

        $this->get(route('metrics'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
            ->assertSee('mock_messaging_events_total{event_type="order.placed"} 1', false)
            ->assertSee('mock_messaging_orders_by_status{status="placed"} 1', false)
            ->assertSee('mock_messaging_consumer_dead_letters_total{service="notification-service"} 1', false)
            ->assertSee('mock_messaging_consumer_last_failure_timestamp_seconds{service="notification-service"}', false);
    }
}
