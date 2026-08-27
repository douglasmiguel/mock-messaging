<?php

namespace Database\Factories;

use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rider> */
class RiderFactory extends Factory
{
    protected $model = Rider::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'vehicle_name' => fake()->randomElement(['Bicycle', 'Electric bicycle', 'Scooter', 'Motorcycle']),
            'license' => strtoupper(fake()->bothify('???-####')),
        ];
    }
}
