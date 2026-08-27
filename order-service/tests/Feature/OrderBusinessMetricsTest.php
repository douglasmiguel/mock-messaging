<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderBusinessMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_current_and_creation_time_order_metrics(): void
    {
        $pizzaPlace = Restaurant::factory()->create(['name' => 'Pizza Place']);
        $noodleHouse = Restaurant::factory()->create(['name' => 'Noodle House']);
        Order::factory()->create([
            'restaurant_id' => $pizzaPlace->id,
            'status' => OrderStatus::Placed,
            'created_at' => '2026-08-24 10:00:00',
        ]);
        Order::factory()->create([
            'restaurant_id' => $pizzaPlace->id,
            'status' => OrderStatus::Accepted,
            'created_at' => '2026-08-24 11:00:00',
        ]);
        Order::factory()->create([
            'restaurant_id' => $noodleHouse->id,
            'status' => OrderStatus::Delivered,
            'created_at' => '2026-08-25 12:00:00',
        ]);

        $this->get(route('metrics.business'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
            ->assertSee('mock_messaging_order_service_orders_current_by_status{status="new"} 0', false)
            ->assertSee('mock_messaging_order_service_orders_current_by_status{status="placed"} 1', false)
            ->assertSee('mock_messaging_order_service_orders_current_by_status{status="accepted"} 1', false)
            ->assertSee('mock_messaging_order_service_orders_current_by_restaurant_status{restaurant_id="'.$pizzaPlace->id.'",restaurant_name="Pizza Place",status="placed"} 1', false)
            ->assertSee('mock_messaging_order_service_outbox_pending_messages 0', false)
            ->assertSee('mock_messaging_order_service_outbox_oldest_pending_age_seconds 0', false)
            ->assertSee('mock_messaging_order_service_outbox_publish_failures_total 0', false);
    }
}
