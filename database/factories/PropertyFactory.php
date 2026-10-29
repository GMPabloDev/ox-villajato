<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->agent(),
            'operation' => fake()->randomElement(PropertyOperation::cases()),
            'type' => fake()->randomElement(PropertyType::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(50_000, 2_000_000),
            'currency' => fake()->randomElement(Currency::cases()),
            'city' => fake()->city(),
            'zone' => fake()->streetName(),
            'bedrooms' => fake()->numberBetween(0, 5),
            'bathrooms' => fake()->numberBetween(1, 4),
            'area' => fake()->randomFloat(2, 40, 400),
        ];
    }
}
