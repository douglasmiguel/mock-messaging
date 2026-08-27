<?php

namespace Database\Factories;

use App\Models\ObservedEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObservedEvent>
 */
class ObservedEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => (string) fake()->unique()->uuid(),
            'event_type' => 'order.placed',
            'event_version' => 1,
            'order_id' => fake()->numberBetween(1, 100_000),
            'payload' => ['data' => ['order' => ['status' => 'placed']]],
            'occurred_at' => now(),
            'received_at' => now(),
        ];
    }
}
