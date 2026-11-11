<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_publish_a_property(): void
    {
        $response = $this->post(route('properties.store'));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('properties', 0);
    }

    public function test_publishing_a_valid_property_redirects_to_its_public_page_and_saves_data_and_photos(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('properties.store'), [
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'description' => 'Casa de tres pisos con jardín y terraza.',
            'price' => 250000,
            'currency' => 'PEN',
            ...$this->propertyLocation('150122'),
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120.5,
            'photos' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('living.png'),
                UploadedFile::fake()->image('kitchen.webp'),
            ],
        ]);

        $property = Property::sole();

        $response->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'description' => 'Casa de tres pisos con jardín y terraza.',
            'price' => 250000,
            'currency' => 'PEN',
            'district_code' => '150122',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120.5,
        ]);

        $this->assertCount(3, $property->photos);
        $this->assertSame([1, 2, 3], $property->photos->pluck('position')->all());
        $this->assertStringEndsWith('.jpg', $property->photos[0]->path);
        $this->assertStringEndsWith('.png', $property->photos[1]->path);
        $this->assertStringEndsWith('.webp', $property->photos[2]->path);

        foreach ($property->photos as $photo) {
            Storage::disk('public')->assertExists($photo->path);
        }
    }
}
