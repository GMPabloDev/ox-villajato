<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_published_properties_in_the_catalog_with_their_card_data(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'price' => 250000,
            'currency' => 'PEN',
            'city' => 'Lima',
            'zone' => 'Miraflores',
        ]);

        $property->photos()->createMany([
            ['path' => 'properties/cover.jpg', 'position' => 1],
            ['path' => 'properties/second.jpg', 'position' => 2],
        ]);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('properties.0.title', 'Casa frente al parque')
                ->where('properties.0.operation_label', 'Venta')
                ->where('properties.0.type_label', 'Casa')
                ->where('properties.0.price_formatted', 'S/ 250,000')
                ->where('properties.0.city', 'Lima')
                ->where('properties.0.zone', 'Miraflores')
                ->where('properties.0.cover_photo_url', Storage::disk('public')->url('properties/cover.jpg'))
                ->where('properties.0.url', route('properties.show', $property))
            );
    }

    public function test_catalog_returns_an_empty_list_when_there_are_no_properties(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 0)
            );
    }
}
