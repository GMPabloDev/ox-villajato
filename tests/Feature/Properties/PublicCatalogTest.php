<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
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
                ->where('filters.operation', null)
                ->where('filters.type', null)
                ->where('filters.zone', null)
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

    public function test_zone_filter_lists_each_published_zone_once_in_alphabetical_order(): void
    {
        Property::factory()->create(['zone' => 'Miraflores']);
        Property::factory()->create(['zone' => 'barranco']);
        Property::factory()->create(['zone' => 'Miraflores']);
        Property::factory()->create(['zone' => 'Ámbar']);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->where('zones', ['Ámbar', 'barranco', 'Miraflores'])
            );
    }

    public function test_catalog_filters_by_operation(): void
    {
        $sale = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa en venta',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'title' => 'Casa en alquiler',
        ]);

        $response = $this->get(route('home', ['operation' => 'sale']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $sale->id)
                ->where('properties.0.title', 'Casa en venta')
            );
    }

    public function test_catalog_filters_by_type(): void
    {
        $house = Property::factory()->create([
            'type' => 'house',
            'title' => 'Casa con jardín',
        ]);
        Property::factory()->create([
            'type' => 'apartment',
            'title' => 'Departamento con balcón',
        ]);

        $response = $this->get(route('home', ['type' => 'house']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $house->id)
                ->where('properties.0.title', 'Casa con jardín')
            );
    }

    public function test_catalog_filters_by_zone_exactly(): void
    {
        $miraflores = Property::factory()->create([
            'zone' => 'Miraflores',
            'title' => 'Departamento en Miraflores',
        ]);
        Property::factory()->create([
            'zone' => 'Miraflores Norte',
            'title' => 'Casa en Miraflores Norte',
        ]);
        Property::factory()->create([
            'zone' => 'Barranco',
            'title' => 'Casa en Barranco',
        ]);

        $response = $this->get(route('home', ['zone' => 'Miraflores']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $miraflores->id)
                ->where('properties.0.zone', 'Miraflores')
            );
    }

    public function test_catalog_combines_filters_and_returns_the_same_card_data_from_the_url(): void
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

        $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler',
            'zone' => 'Miraflores',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'apartment',
            'title' => 'Departamento en venta',
            'zone' => 'Miraflores',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en Barranco',
            'zone' => 'Barranco',
        ]);

        $response = $this->get(route('home', [
            'operation' => 'sale',
            'type' => 'house',
            'zone' => 'Miraflores',
        ]));

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
                ->where('filters', [
                    'operation' => 'sale',
                    'type' => 'house',
                    'zone' => 'Miraflores',
                ])
            );
    }

    public function test_catalog_keeps_filtered_properties_in_latest_id_order(): void
    {
        $older = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa antigua',
        ]);
        $newer = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa reciente',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler',
        ]);

        $response = $this->get(route('home', ['operation' => 'sale']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 2)
                ->where('properties.0.id', $newer->id)
                ->where('properties.1.id', $older->id)
            );
    }

    public function test_catalog_returns_no_properties_when_filters_match_nothing(): void
    {
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'zone' => 'Miraflores',
        ]);

        $response = $this->get(route('home', [
            'operation' => 'rent',
            'type' => 'apartment',
            'zone' => 'Barranco',
        ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 0)
                ->where('filters', [
                    'operation' => 'rent',
                    'type' => 'apartment',
                    'zone' => 'Barranco',
                ])
            );
    }

    public function test_catalog_rejects_invalid_operation_and_type_filters(): void
    {
        $response = $this
            ->from(route('home', ['zone' => 'Barranco']))
            ->get(route('home', [
                'operation' => 'venta',
                'type' => 'casa',
            ]));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['operation', 'type']);
    }

    public static function invalidCatalogFilters(): array
    {
        return [
            'operation' => ['operation', 'venta', 'El valor seleccionado para operación no es válido.'],
            'type' => ['type', 'casa', 'El valor seleccionado para tipo no es válido.'],
            'zone is not text' => ['zone', ['Miraflores'], 'El campo zona debe ser texto.'],
            'zone is too long' => ['zone', str_repeat('a', 256), 'El campo zona no debe superar 255 caracteres.'],
        ];
    }

    #[DataProvider('invalidCatalogFilters')]
    public function test_catalog_rejects_invalid_filter_values(string $field, mixed $value, string $message): void
    {
        $response = $this
            ->from(route('home', ['zone' => 'Barranco']))
            ->followingRedirects()
            ->get(route('home', [$field => $value]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->where("errors.{$field}", $message)
            );
    }
}
