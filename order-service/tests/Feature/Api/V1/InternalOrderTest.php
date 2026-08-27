<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_confirmation_changes_order_status_and_records_an_event(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Placed]);

        $this->postJson('/api/v1/internal/orders/'.$order->id.'/restaurant/confirm', [], [
            'X-Service-Key' => config('messaging.service_key'),
        ])->assertOk()->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('outbox_messages', ['order_id' => $order->id, 'event_type' => 'order.accepted']);
    }

    public function test_rider_service_can_store_an_external_rider_id_after_order_is_ready(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::ReadyForPickup]);

        $headers = [
            'X-Service-Key' => config('messaging.service_key'),
            'X-Idempotency-Key' => 'rider-assignment-42',
        ];
        $this->postJson('/api/v1/internal/orders/'.$order->id.'/rider-assignment', ['rider_id' => 42], $headers)->assertOk()
            ->assertJsonPath('data.status', 'rider_assigned')
            ->assertJsonPath('data.rider_id', 42);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'rider_id' => 42, 'status' => 'rider_assigned']);
        $this->postJson('/api/v1/internal/orders/'.$order->id.'/rider-assignment', ['rider_id' => 42], $headers)
            ->assertOk()
            ->assertJsonPath('data.rider_id', 42);
        $this->assertDatabaseCount('outbox_messages', 1);
    }
}
