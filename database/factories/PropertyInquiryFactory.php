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
    public function configure(): static
    {
        return $this->afterMaking(function (PropertyInquiry $inquiry): void {
            $property = $inquiry->property()->firstOrFail();

            $inquiry->property_title ??= $property->title;
            $inquiry->publisher_id ??= $property->user_id;
        });
    }

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
