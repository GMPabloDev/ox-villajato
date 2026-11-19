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
            'district_code' => '040103',
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
                ->where('property.district_code', '040103')
                ->where('property.department', 'Arequipa')
                ->where('property.province', 'Arequipa')
                ->where('property.district', 'Cayma')
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

    public function test_initial_html_contains_escaped_preview_metadata_and_canonical_property_url(): void
    {
        $property = Property::factory()->create([
            'title' => 'Casa "Sol" & <mar>',
            'description' => 'Vista & jardín <privado> "amplio".',
        ]);
        $property->photos()->createMany([
            ['path' => 'properties/cover.jpg', 'position' => 1],
            ['path' => 'properties/second.jpg', 'position' => 2],
        ]);

        $response = $this->get(route('properties.show', $property).'?source=mail');

        $response->assertOk()
            ->assertSee('property="og:title" content="Casa &quot;Sol&quot; &amp; &lt;mar&gt;"', false)
            ->assertSee('property="og:description" content="Vista &amp; jardín &lt;privado&gt; &quot;amplio&quot;."', false)
            ->assertSee('property="og:url" content="'.route('properties.show', $property).'"', false)
            ->assertSee('property="og:image" content="'.Storage::disk('public')->url('properties/cover.jpg').'"', false);
    }

    public function test_preview_omits_missing_cover_and_limits_long_descriptions(): void
    {
        $property = Property::factory()->create(['description' => str_repeat('a', 220)]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('property="og:description" content="'.str_repeat('a', 200).'..."', false)
            ->assertDontSee('property="og:image"', false);
    }

    public function test_inertia_navigation_still_returns_property_data(): void
    {
        $property = Property::factory()->create();

        $version = $this->get(route('properties.show', $property))->viewData('page')['version'];

        $this->get(route('properties.show', $property), ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
            ->assertOk()
            ->assertJsonPath('component', 'properties/show')
            ->assertJsonPath('props.property.id', $property->id)
            ->assertJsonPath('props.property.url', route('properties.show', $property));
    }

    public function test_deleted_property_page_returns_not_found(): void
    {
        $property = Property::factory()->create();
        $url = route('properties.show', $property);
        $property->delete();

        $this->get($url)->assertNotFound();
    }

    public function test_archived_property_page_returns_not_found(): void
    {
        $property = Property::factory()->create([
            'status' => 'archived',
        ]);

        $this->get(route('properties.show', $property))->assertNotFound();
    }
}
