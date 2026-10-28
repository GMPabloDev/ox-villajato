<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function closedStatuses(): array
    {
        return [
            'sold' => ['sale', 'sold'],
            'rented' => ['rent', 'rented'],
        ];
    }

    #[DataProvider('closedStatuses')]
    public function test_closed_property_page_returns_not_found(string $operation, string $status): void
    {
        $property = Property::factory()->create([
            'operation' => $operation,
            'status' => $status,
        ]);

        $this->get(route('properties.show', $property))->assertNotFound();
    }

    public function test_guest_can_open_a_property_page_and_see_all_its_data_and_photos(): void
    {
        $property = Property::factory()->create([
            'operation' => 'rent',
            'type' => 'apartment',
            'title' => 'Departamento con vista al volcán',
            'description' => 'Departamento luminoso de dos dormitorios.',
            'price' => 1500,
            'currency' => 'USD',
            'city' => 'Arequipa',
            'zone' => 'Cayma',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'area' => 95.5,
        ]);

        $property->photos()->createMany([
            ['path' => 'properties/cover.jpg', 'position' => 1],
            ['path' => 'properties/living.jpg', 'position' => 2],
            ['path' => 'properties/kitchen.jpg', 'position' => 3],
        ]);

        $response = $this->get(route('properties.show', $property));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/show')
                ->where('property.id', $property->id)
                ->where('property.title', 'Departamento con vista al volcán')
                ->where('property.description', 'Departamento luminoso de dos dormitorios.')
                ->where('property.operation_label', 'Alquiler')
                ->where('property.type_label', 'Departamento')
                ->where('property.price_formatted', 'US$ 1,500')
                ->where('property.city', 'Arequipa')
                ->where('property.zone', 'Cayma')
                ->where('property.bedrooms', 2)
                ->where('property.bathrooms', 1)
                ->where('property.area', '95.50')
                ->where('property.cover_photo_url', Storage::disk('public')->url('properties/cover.jpg'))
                ->has('property.photos', 3)
                ->where('property.photos.0.url', Storage::disk('public')->url('properties/cover.jpg'))
                ->where('property.photos.1.url', Storage::disk('public')->url('properties/living.jpg'))
                ->where('property.photos.2.url', Storage::disk('public')->url('properties/kitchen.jpg'))
                ->where('property.photos.0.position', 1)
                ->where('property.photos.1.position', 2)
                ->where('property.photos.2.position', 3)
            );
    }
}
