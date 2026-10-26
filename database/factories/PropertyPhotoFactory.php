<?php

namespace Database\Factories;

use App\Models\PropertyPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyPhoto>
 */
class PropertyPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => 'properties/'.fake()->uuid().'.jpg',
            'position' => 1,
        ];
    }
}
