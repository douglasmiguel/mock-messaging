<?php

namespace Database\Factories;

use App\Models\ItemCategory;
use App\Models\Restaurant;
use App\Models\RestaurantItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RestaurantItem> */
class RestaurantItemFactory extends Factory
{
    protected $model = RestaurantItem::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'item_category_id' => ItemCategory::factory(),
            'name' => fake()->words(3, true),
            'item_price' => fake()->numberBetween(450, 2_500),
            'is_available' => true,
        ];
    }
}
