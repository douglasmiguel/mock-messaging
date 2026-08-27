<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\ItemCategory;
use App\Models\OutboxMessage;
use App\Models\Restaurant;
use App\Models\RestaurantItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateDemoOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_orders_across_every_status_with_lifecycle_outbox_events(): void
    {
        $this->createOrderableRestaurant();
        Client::factory()->count(2)->create();

        $this->artisan('demo:generate-orders', ['--count' => 18, '--days' => 2])
            ->expectsOutputToContain('Generated 18 demo orders')
            ->assertSuccessful();

        $this->assertDatabaseCount('orders', 18);
        $this->assertDatabaseCount('outbox_messages', 56);

        foreach (OrderStatus::cases() as $status) {
            $this->assertDatabaseHas('orders', ['status' => $status->value]);
        }

        $expectedEventCounts = [
            'order.placed' => 18,
            'order.accepted' => 14,
            'order.preparing' => 2,
            'order.ready_for_pickup' => 8,
            'order.rider_assigned' => 6,
            'order.picked_up' => 4,
            'order.delivered' => 2,
            'order.cancelled' => 2,
        ];

        foreach ($expectedEventCounts as $eventType => $expectedCount) {
            $this->assertSame($expectedCount, OutboxMessage::query()
                ->where('event_type', $eventType)
                ->count());
        }
    }

    public function test_fails_without_orderable_seed_data(): void
    {
        $this->artisan('demo:generate-orders')
            ->expectsOutput('Seed at least one client and one restaurant with an available menu item before generating demo orders.')
            ->assertFailed();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    private function createOrderableRestaurant(): Restaurant
    {
        $restaurant = Restaurant::factory()->create();
        $category = ItemCategory::factory()->create(['restaurant_id' => $restaurant->id]);

        RestaurantItem::factory()->count(2)->create([
            'restaurant_id' => $restaurant->id,
            'item_category_id' => $category->id,
            'is_available' => true,
        ]);

        return $restaurant;
    }
}
