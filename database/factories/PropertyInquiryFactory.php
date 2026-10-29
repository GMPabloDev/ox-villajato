<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyInquiry>
 */
class PropertyInquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('+51 9## ### ###'),
            'message' => fake()->paragraph(),
        ];
    }
}
