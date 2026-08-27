<?php

namespace Database\Factories;

use App\Models\OrderProjection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderProjection>
 */
class OrderProjectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => fake()->unique()->numberBetween(1, 100_000),
            'status' => 'placed',
            'restaurant_id' => fake()->numberBetween(1, 100_000),
            'client_id' => fake()->numberBetween(1, 100_000),
            'rider_id' => null,
            'last_event_type' => 'order.placed',
            'last_event_at' => now(),
        ];
    }
}
