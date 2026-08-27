<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\ItemCategory;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@order-service.test'],
            [
                'name' => 'admin',
                'password' => Hash::make('admin'),
            ],
        );

        $restaurants = Restaurant::factory()->count(100)->create();
        $clients = Client::factory()->count(100)->create();

        // Ten restaurants receive ten categories and ten menu items each. This keeps
        // every requested table near 100 rows while leaving realistic menus to order from.
        $menuItems = collect();
        $restaurants->take(10)->each(function (Restaurant $restaurant) use ($menuItems): void {
            $categories = ItemCategory::factory()
                ->count(10)
                ->create(['restaurant_id' => $restaurant->id]);

            $menuItems->push(...RestaurantItem::factory()
                ->count(10)
                ->sequence(fn ($sequence) => [
                    'restaurant_id' => $restaurant->id,
                    'item_category_id' => $categories[$sequence->index]->id,
                ])
                ->create());
        });

        $menuRestaurants = $restaurants->take(10);

        foreach (range(0, 99) as $index) {
            $restaurant = $menuRestaurants->random();
            $selectedItems = $menuItems
                ->where('restaurant_id', $restaurant->id)
                ->random(random_int(1, 4));
            $status = OrderStatus::cases()[$index % count(OrderStatus::cases())];

            $orderItems = $selectedItems->map(function (RestaurantItem $item): array {
                $quantity = random_int(1, 3);

                return [
                    'restaurant_item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_price' => $item->item_price,
                    'quantity' => $quantity,
                    'total_price' => $item->item_price * $quantity,
                ];
            });
            $orderPrice = $orderItems->sum('total_price');
            $deliveryFee = random_int(199, 599);

            $order = Order::factory()->create([
                'client_id' => $clients[$index]->id,
                'client_name' => $clients[$index]->name,
                'client_phone' => $clients[$index]->phone,
                'client_email' => $clients[$index]->email,
                'restaurant_id' => $restaurant->id,
                'rider_id' => null,
                'order_price' => $orderPrice,
                'delivery_fee' => $deliveryFee,
                'total_price' => $orderPrice + $deliveryFee,
                'status' => $status,
            ]);

            $order->items()->createMany($orderItems->all());

        }
    }
}
