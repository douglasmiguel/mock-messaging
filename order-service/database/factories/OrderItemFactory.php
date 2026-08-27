<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $itemPrice = fake()->numberBetween(450, 2_500);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'restaurant_item_id' => RestaurantItem::factory(),
            'item_name' => fake()->words(3, true),
            'item_price' => $itemPrice,
            'quantity' => $quantity,
            'total_price' => $itemPrice * $quantity,
        ];
    }
}
