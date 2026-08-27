<?php

namespace Tests\Feature\Api\V1;

use App\Models\ItemCategory;
use App\Models\Order;
use App\Models\OutboxMessage;
use App\Models\Restaurant;
use App\Models\RestaurantItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_and_snapshots_current_menu_prices(): void
    {
        $restaurant = Restaurant::factory()->create();
        $category = ItemCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        $burger = RestaurantItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'item_category_id' => $category->id,
            'name' => 'House burger',
            'item_price' => 1_250,
        ]);
        $fries = RestaurantItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'item_category_id' => $category->id,
            'name' => 'Skin-on fries',
            'item_price' => 350,
        ]);

        $response = $this->postJson('/api/v1/order', [
            'client_name' => 'Douglas Miguel',
            'client_phone' => '+44 7700 900000',
            'client_email' => 'douglas@example.test',
            'delivery_address' => '1 Test Street, London',
            'restaurant_id' => $restaurant->id,
            'items' => [
                ['restaurant_item_id' => $burger->id, 'quantity' => 2],
                ['restaurant_item_id' => $fries->id, 'quantity' => 1],
            ],
            // These are intentionally ignored: the server owns all price calculations.
            'order_price' => 1,
            'total_price' => 1,
        ], ['Idempotency-Key' => 'order-create-9001']);

        $response->assertCreated()
            ->assertJsonPath('data.order_price', 2_850)
            ->assertJsonPath('data.delivery_fee', 299)
            ->assertJsonPath('data.total_price', 3_149)
            ->assertJsonPath('data.status', 'placed')
            ->assertJsonPath('data.items.0.item_price', 1_250)
            ->assertJsonPath('data.items.0.quantity', 2);

        $order = Order::firstOrFail();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'restaurant_item_id' => $burger->id,
            'item_price' => 1_250,
            'quantity' => 2,
            'total_price' => 2_500,
        ]);
        $this->assertSame(1, OutboxMessage::count());
        $this->assertDatabaseHas('outbox_messages', [
            'order_id' => $order->id,
            'event_type' => 'order.placed',
            'published_at' => null,
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'aggregate_version' => 1]);
    }

    public function test_it_rejects_items_that_do_not_belong_to_the_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();
        $otherRestaurant = Restaurant::factory()->create();
        $otherCategory = ItemCategory::factory()->create(['restaurant_id' => $otherRestaurant->id]);
        $otherItem = RestaurantItem::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'item_category_id' => $otherCategory->id,
        ]);

        $response = $this->postJson('/api/v1/order', [
            'client_name' => 'Douglas Miguel',
            'client_email' => 'douglas@example.test',
            'delivery_address' => '1 Test Street, London',
            'restaurant_id' => $restaurant->id,
            'items' => [
                ['restaurant_item_id' => $otherItem->id, 'quantity' => 1],
            ],
        ], ['Idempotency-Key' => 'order-create-invalid']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_returns_the_original_order_when_an_idempotency_key_is_retried(): void
    {
        $restaurant = Restaurant::factory()->create();
        $category = ItemCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        $item = RestaurantItem::factory()->create(['restaurant_id' => $restaurant->id, 'item_category_id' => $category->id, 'item_price' => 500]);
        $payload = [
            'client_name' => 'Douglas Miguel',
            'client_email' => 'douglas@example.test',
            'delivery_address' => '1 Test Street, London',
            'restaurant_id' => $restaurant->id,
            'items' => [['restaurant_item_id' => $item->id, 'quantity' => 1]],
        ];

        $first = $this->postJson('/api/v1/order', $payload, ['Idempotency-Key' => 'order-create-retry']);
        $retry = $this->postJson('/api/v1/order', $payload, ['Idempotency-Key' => 'order-create-retry']);

        $first->assertCreated();
        $retry->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
    }

    public function test_it_rejects_reusing_an_idempotency_key_for_a_different_request(): void
    {
        $restaurant = Restaurant::factory()->create();
        $category = ItemCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        $item = RestaurantItem::factory()->create(['restaurant_id' => $restaurant->id, 'item_category_id' => $category->id]);
        $payload = [
            'client_name' => 'Douglas Miguel',
            'client_email' => 'douglas@example.test',
            'delivery_address' => '1 Test Street, London',
            'restaurant_id' => $restaurant->id,
            'items' => [['restaurant_item_id' => $item->id, 'quantity' => 1]],
        ];

        $this->postJson('/api/v1/order', $payload, ['Idempotency-Key' => 'order-create-conflict'])->assertCreated();
        $payload['delivery_address'] = '2 Different Street, London';

        $this->postJson('/api/v1/order', $payload, ['Idempotency-Key' => 'order-create-conflict'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
        $this->assertDatabaseCount('orders', 1);
    }
}
