<?php

namespace Tests\Feature\TestOrder;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\ItemCategory;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_can_generate_a_new_test_order(): void
    {
        $this->createOrderableRestaurant();
        Client::factory()->create();

        $this->post('/test-orders')
            ->assertRedirect(route('home'))
            ->assertSessionHas('test_order_id');

        $order = Order::latest()->firstOrFail();
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertGreaterThan(0, $order->items()->count());
        $this->assertDatabaseHas('outbox_messages', [
            'order_id' => $order->id,
            'event_type' => 'order.placed',
        ]);
    }

    public function test_test_order_can_follow_the_delivery_flow(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::New]);
        $this->withSession(['test_order_id' => $order->id])
            ->post(route('test-orders.confirm', $order))
            ->assertRedirect(route('home'));
        $this->assertSame(OrderStatus::Accepted, $order->refresh()->status);

        $this->withSession(['test_order_id' => $order->id])
            ->post(route('test-orders.ready', $order))
            ->assertRedirect(route('home'));
        $this->assertSame(OrderStatus::ReadyForPickup, $order->refresh()->status);

        // The Rider Service owns this assignment and updates the Order Service through its internal API.
        $order->update(['rider_id' => 42, 'status' => OrderStatus::RiderAssigned]);

        $this->withSession(['test_order_id' => $order->id])
            ->post(route('test-orders.pick-up', $order))
            ->assertRedirect(route('home'));
        $this->assertSame(OrderStatus::PickedUp, $order->refresh()->status);

        $this->withSession(['test_order_id' => $order->id])
            ->post(route('test-orders.deliver', $order))
            ->assertRedirect(route('home'));
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);

        $this->assertDatabaseHas('outbox_messages', ['order_id' => $order->id, 'event_type' => 'order.accepted']);
        $this->assertDatabaseHas('outbox_messages', ['order_id' => $order->id, 'event_type' => 'order.ready_for_pickup']);
        $this->assertDatabaseHas('outbox_messages', ['order_id' => $order->id, 'event_type' => 'order.picked_up']);
        $this->assertDatabaseHas('outbox_messages', ['order_id' => $order->id, 'event_type' => 'order.delivered']);
    }

    public function test_client_can_cancel_a_new_or_accepted_test_order(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::New]);

        $this->withSession(['test_order_id' => $order->id])
            ->post(route('test-orders.cancel', $order))
            ->assertRedirect(route('home'));

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertDatabaseHas('outbox_messages', ['order_id' => $order->id, 'event_type' => 'order.cancelled']);
    }

    private function createOrderableRestaurant(): Restaurant
    {
        $restaurant = Restaurant::factory()->create();
        $category = ItemCategory::factory()->create(['restaurant_id' => $restaurant->id]);

        RestaurantItem::factory()->count(2)->create([
            'restaurant_id' => $restaurant->id,
            'item_category_id' => $category->id,
        ]);

        return $restaurant;
    }
}
