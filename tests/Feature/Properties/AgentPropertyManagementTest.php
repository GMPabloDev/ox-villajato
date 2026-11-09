<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AgentPropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_publish_a_property_that_appears_in_the_catalog_and_its_public_page(): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent)->post(route('properties.store'), [
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'description' => 'Casa de tres pisos con jardín y terraza.',
            'price' => 250000,
            'currency' => 'PEN',
            'city' => 'Lima',
            'zone' => 'Miraflores',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120.5,
            'photos' => [
                UploadedFile::fake()->image('front.jpg'),
            ],
        ]);

        $property = Property::sole();

        $response->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $agent->id,
            'title' => 'Casa frente al parque',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('properties.0.title', 'Casa frente al parque')
            );

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/show')
                ->where('property.id', $property->id)
                ->where('property.title', 'Casa frente al parque')
            );
    }

    public function test_agent_panel_lists_only_their_properties_including_closed_ones_with_management_actions(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        $available = Property::factory()->for($agent)->create([
            'operation' => 'sale',
            'title' => 'Casa disponible',
            'status' => 'available',
        ]);

        $closed = Property::factory()->for($agent)->create([
            'operation' => 'sale',
            'title' => 'Casa vendida',
            'status' => 'sold',
        ]);

        Property::factory()->for($otherAgent)->create([
            'operation' => 'sale',
            'title' => 'Casa de otro agente',
            'status' => 'available',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->has('properties', 2)
                ->where('properties.0.id', $closed->id)
                ->where('properties.0.title', 'Casa vendida')
                ->where('properties.0.status', 'sold')
                ->where('properties.0.status_action', 'reopen')
                ->where('properties.0.status_action_label', 'Volver a disponible')
                ->where('properties.1.id', $available->id)
                ->where('properties.1.title', 'Casa disponible')
                ->where('properties.1.status', 'available')
                ->where('properties.1.status_action', 'close')
                ->where('properties.1.status_action_label', 'Marcar como vendida')
            );
    }

    public function test_agent_without_properties_sees_empty_state_even_when_others_have_properties(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        Property::factory()->for($otherAgent)->create([
            'title' => 'Casa de otro agente',
            'status' => 'available',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->has('properties', 0)
            );
    }

    public function test_agent_can_update_their_own_property_and_changes_appear_in_the_panel_and_public_page(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'operation' => 'sale',
            'title' => 'Casa frente al parque',
            'price' => 250000,
            'currency' => 'PEN',
            'city' => 'Lima',
            'zone' => 'Miraflores',
            'status' => 'available',
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->actingAs($agent)
            ->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->where('properties.0.id', $property->id)
                ->where('properties.0.title', 'Casa frente al parque')
                ->where('properties.0.price', '250000.00')
                ->where('properties.0.currency', 'PEN')
                ->where('properties.0.city', 'Lima')
                ->where('properties.0.zone', 'Miraflores')
                ->has('properties.0.photos', 1)
                ->where('properties.0.photos.0.id', $photo->id)
            );

        $response = $this->actingAs($agent)->put(route('panel.properties.update', $property), [
            ...$this->propertyPayload($property, [
                'title' => 'Casa renovada',
                'price' => 300000,
            ]),
            'photo_order' => [$photo->id],
        ]);

        $response->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $agent->id,
            'title' => 'Casa renovada',
            'price' => 300000,
        ]);

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('properties.0.title', 'Casa renovada')
            );

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('property.id', $property->id)
                ->where('property.title', 'Casa renovada')
            );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function closedStatuses(): array
    {
        return [
            'sale' => ['sale', 'sold'],
            'rent' => ['rent', 'rented'],
        ];
    }

    #[DataProvider('closedStatuses')]
    public function test_agent_can_close_and_reopen_their_own_property(string $operation, string $closedStatus): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'operation' => $operation,
            'title' => 'Propiedad propia',
            'status' => 'available',
        ]);

        $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->actingAs($agent)
            ->patch(route('panel.properties.close', $property))
            ->assertRedirect(route('panel.properties.index'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => $closedStatus,
        ]);

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('properties.0.status', $closedStatus)
            );

        $this->get(route('properties.show', $property))->assertNotFound();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('properties', 0));

        $this->actingAs($agent)
            ->patch(route('panel.properties.reopen', $property))
            ->assertRedirect(route('panel.properties.index'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'available',
        ]);

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('properties.0.status', 'available')
            );

        $this->get(route('properties.show', $property))->assertOk();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
            );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function foreignPropertyRoutes(): array
    {
        return [
            'update property' => ['put', 'panel.properties.update', true],
            'update property with invalid data' => ['put', 'panel.properties.update', false],
            'close property' => ['patch', 'panel.properties.close', false],
            'reopen property' => ['patch', 'panel.properties.reopen', false],
        ];
    }

    #[DataProvider('foreignPropertyRoutes')]
    public function test_agent_cannot_manage_another_agents_property(
        string $method,
        string $routeName,
        bool $validPayload,
    ): void {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        $property = Property::factory()->for($otherAgent)->create([
            'operation' => 'sale',
            'title' => 'Casa ajena',
            'status' => 'available',
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $payload = $validPayload
            ? [
                ...$this->propertyPayload($property, ['title' => 'Intento ajeno']),
                'photo_order' => [$photo->id],
            ]
            : [];

        $this->actingAs($agent)
            ->call($method, route($routeName, $property), $payload)
            ->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $otherAgent->id,
            'title' => 'Casa ajena',
            'status' => 'available',
        ]);
    }

    public function test_admin_panel_lists_properties_from_all_agents(): void
    {
        $admin = User::factory()->admin()->create();
        $firstAgent = User::factory()->agent()->create();
        $secondAgent = User::factory()->agent()->create();

        $first = Property::factory()->for($firstAgent)->create([
            'title' => 'Casa de Ana',
            'status' => 'available',
        ]);

        $second = Property::factory()->for($secondAgent)->create([
            'title' => 'Casa de Beto',
            'status' => 'sold',
        ]);

        $response = $this->actingAs($admin)->get(route('panel.properties.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->has('properties', 2)
                ->where('properties.0.id', $second->id)
                ->where('properties.0.title', 'Casa de Beto')
                ->where('properties.0.status', 'sold')
                ->where('properties.1.id', $first->id)
                ->where('properties.1.title', 'Casa de Ana')
                ->where('properties.1.status', 'available')
            );
    }

    public function test_admin_can_update_and_close_an_agents_property(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'operation' => 'sale',
            'title' => 'Casa del agente',
            'price' => 200000,
            'currency' => 'PEN',
            'status' => 'available',
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('panel.properties.update', $property), [
                ...$this->propertyPayload($property, [
                    'title' => 'Casa gestionada por el administrador',
                ]),
                'photo_order' => [$photo->id],
            ])
            ->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $agent->id,
            'title' => 'Casa gestionada por el administrador',
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->patch(route('panel.properties.close', $property))
            ->assertRedirect(route('panel.properties.index'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $agent->id,
            'status' => 'sold',
        ]);
    }

    public function test_agent_publishing_invalid_data_sees_errors_and_creates_nothing(): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([
                'title' => 'El campo título es obligatorio.',
            ]);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk('public')->assertDirectoryEmpty('properties');
    }

    public function test_agent_updating_their_property_with_invalid_data_sees_errors_and_keeps_previous_data(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa original',
            'price' => 250000,
            'status' => 'available',
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $response = $this->actingAs($agent)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                'title' => '',
                'price' => -10,
                'photo_order' => [$photo->id],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([
                'title' => 'El campo título es obligatorio.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => 'Casa original',
            'price' => 250000,
            'status' => 'available',
        ]);
        $this->assertDatabaseCount('property_photos', 1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function propertyPayload(Property $property, array $overrides = []): array
    {
        return array_merge([
            'operation' => $property->operation->value,
            'type' => $property->type->value,
            'title' => $property->title,
            'description' => $property->description,
            'price' => $property->price,
            'currency' => $property->currency->value,
            'city' => $property->city,
            'zone' => $property->zone,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'area' => $property->area,
        ], $overrides);
    }
}
