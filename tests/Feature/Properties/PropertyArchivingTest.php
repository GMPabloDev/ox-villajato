<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyArchivingTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_archive_an_available_property_and_sees_confirmation(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa archivada',
            'status' => 'available',
        ]);
        $otherProperty = Property::factory()->for($agent)->create([
            'title' => 'Casa disponible',
            'status' => 'available',
        ]);

        $response = $this->actingAs($agent)
            ->patch(route('panel.properties.archive', $property));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Propiedad archivada.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);

        $this->actingAs($agent)
            ->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $otherProperty->id)
            );
    }

    public function test_guest_archiving_an_available_property_redirects_to_login(): void
    {
        $property = Property::factory()->create([
            'status' => 'available',
        ]);

        $this->patch(route('panel.properties.archive', $property))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'available',
        ]);
    }

    public function test_agent_editing_or_closing_an_archived_property_returns_404(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
        ]);

        $this->actingAs($agent)
            ->get(route('panel.properties.edit', $property))
            ->assertNotFound();

        $this->actingAs($agent)
            ->patch(route('panel.properties.close', $property))
            ->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rolesThatCannotUpdateArchivedProperties(): array
    {
        return [
            'agent owner' => ['agent'],
            'administrator' => ['admin'],
        ];
    }

    #[DataProvider('rolesThatCannotUpdateArchivedProperties')]
    public function test_agent_or_admin_updating_an_archived_property_returns_404_and_preserves_status(string $role): void
    {
        $owner = User::factory()->agent()->create();
        $user = $role === 'admin'
            ? User::factory()->admin()->create()
            : $owner;
        $property = Property::factory()->for($owner)->create([
            'operation' => 'sale',
            'status' => 'archived',
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $response = $this->actingAs($user)
            ->put(
                route('panel.properties.update', $property),
                $this->propertyUpdatePayload($property, $photo->id, 'rent'),
            );

        $response->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'operation' => 'sale',
            'status' => 'archived',
        ]);
    }

    public function test_reopening_archived_property_returns_404_and_preserves_its_status(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
        ]);

        $response = $this->actingAs($agent)
            ->patch(route('panel.properties.reopen', $property));

        $response->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
    }

    public function test_admin_can_archive_an_agents_available_property(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'available',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('panel.properties.archive', $property));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Propiedad archivada.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function statusesThatCannotBeArchived(): array
    {
        return [
            'sold' => ['sale', 'sold'],
            'rented' => ['rent', 'rented'],
        ];
    }

    #[DataProvider('statusesThatCannotBeArchived')]
    public function test_archive_returns_404_and_preserves_sold_or_rented_status(
        string $operation,
        string $status,
    ): void {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'operation' => $operation,
            'status' => $status,
        ]);

        $response = $this->actingAs($agent)
            ->patch(route('panel.properties.archive', $property));

        $response->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'operation' => $operation,
            'status' => $status,
        ]);
    }

    public function test_archived_property_does_not_appear_in_public_catalog(): void
    {
        Property::factory()->create([
            'title' => 'Casa archivada',
            'zone' => 'Cayma',
            'status' => 'archived',
        ]);
        $availableProperty = Property::factory()->create([
            'title' => 'Casa disponible',
            'zone' => 'Miraflores',
            'status' => 'available',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $availableProperty->id)
                ->where('zones', ['Miraflores'])
            );
    }

    public function test_archive_returns_404_for_another_agents_property_and_preserves_it(): void
    {
        $agent = User::factory()->agent()->create();
        $owner = User::factory()->agent()->create();
        $property = Property::factory()->for($owner)->create([
            'status' => 'available',
        ]);

        $response = $this->actingAs($agent)
            ->patch(route('panel.properties.archive', $property));

        $response->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $owner->id,
            'status' => 'available',
        ]);
    }

    /**
     * @return array{
     *     operation: string,
     *     type: string,
     *     title: string,
     *     description: string,
     *     price: string,
     *     currency: string,
     *     city: string,
     *     zone: string,
     *     bedrooms: int,
     *     bathrooms: int,
     *     area: string,
     *     photo_order: array<int, int>
     * }
     */
    private function propertyUpdatePayload(Property $property, int $photoId, string $operation): array
    {
        return [
            'operation' => $operation,
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
            'photo_order' => [$photoId],
        ];
    }
}
