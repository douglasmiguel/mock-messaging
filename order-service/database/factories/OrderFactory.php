<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $orderPrice = fake()->numberBetween(1_000, 6_000);
        $deliveryFee = fake()->numberBetween(199, 599);
        $createdAt = fake()->dateTimeBetween('-90 days', '-1 minute');

        return [
            'client_id' => Client::factory(),
            'client_name' => fake()->name(),
            'client_phone' => fake()->phoneNumber(),
            'client_email' => fake()->safeEmail(),
            'delivery_address' => fake()->address(),
            'restaurant_id' => Restaurant::factory(),
            'order_price' => $orderPrice,
            'delivery_fee' => $deliveryFee,
            'total_price' => $orderPrice + $deliveryFee,
            'rider_id' => null,
            'status' => OrderStatus::Placed,
            'created_at' => $createdAt,
            'updated_at' => fake()->dateTimeBetween($createdAt, 'now'),
        ];
    }
}
