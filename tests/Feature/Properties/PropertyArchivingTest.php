<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
                ->has('properties.data', 1)
                ->where('properties.data.0.id', $otherProperty->id)
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

    public function test_agent_closing_an_archived_property_returns_404(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
        ]);

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
            'district_code' => '040103',
            'status' => 'archived',
        ]);
        $availableProperty = Property::factory()->create([
            'title' => 'Casa disponible',
            'district_code' => '150122',
            'status' => 'available',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $availableProperty->id)
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

    public function test_archived_list_shows_only_agents_properties_and_all_properties_to_admin(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        $admin = User::factory()->admin()->create();

        $agentsArchivedProperty = Property::factory()->for($agent)->create([
            'status' => 'archived',
        ]);
        $otherAgentsArchivedProperty = Property::factory()->for($otherAgent)->create([
            'status' => 'archived',
        ]);
        $adminsArchivedProperty = Property::factory()->for($admin)->create([
            'status' => 'archived',
        ]);
        Property::factory()->for($agent)->create([
            'status' => 'available',
        ]);

        $agentResponse = $this->actingAs($agent)
            ->get(route('panel.properties.archived'));

        $agentResponse
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/archived')
                ->has('properties', 1)
                ->where('properties.0.id', $agentsArchivedProperty->id)
            );

        $adminResponse = $this->actingAs($admin)
            ->get(route('panel.properties.archived'));

        $adminResponse
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/archived')
                ->has('properties', 3)
            );

        $actualPropertyIds = array_column($adminResponse->inertiaProps('properties'), 'id');
        $expectedPropertyIds = [
            $adminsArchivedProperty->id,
            $otherAgentsArchivedProperty->id,
            $agentsArchivedProperty->id,
        ];

        sort($actualPropertyIds, SORT_NUMERIC);
        sort($expectedPropertyIds, SORT_NUMERIC);

        $this->assertSame($expectedPropertyIds, $actualPropertyIds);
    }

    public function test_archived_list_shows_the_location_of_each_property(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'district_code' => '040103',
            'status' => 'archived',
        ]);

        $this->actingAs($agent)
            ->get(route('panel.properties.archived'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/archived')
                ->where('properties.0.id', $property->id)
                ->where('properties.0.district_code', '040103')
                ->where('properties.0.district', 'Cayma')
                ->where('properties.0.province', 'Arequipa')
                ->where('properties.0.department', 'Arequipa')
            );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function archivedListActorsWithoutProperties(): array
    {
        return [
            'agent' => ['agent'],
            'administrator' => ['admin'],
        ];
    }

    #[DataProvider('archivedListActorsWithoutProperties')]
    public function test_archived_list_is_empty_when_an_agent_or_admin_has_no_archived_properties(string $role): void
    {
        $user = $role === 'admin'
            ? User::factory()->admin()->create()
            : User::factory()->agent()->create();

        $response = $this->actingAs($user)
            ->get(route('panel.properties.archived'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/archived')
                ->has('properties', 0)
            );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function republishingActors(): array
    {
        return [
            'owner agent' => ['agent'],
            'administrator republishing another agent property' => ['admin'],
        ];
    }

    #[DataProvider('republishingActors')]
    public function test_agent_owner_or_admin_can_republish_and_restore_the_public_inquiry_flow(string $role): void
    {
        config(['properties.inquiry_recipient' => 'consultas@inmobiliaria.test']);

        Mail::fake();

        $owner = User::factory()->agent()->create();
        $actor = $role === 'admin'
            ? User::factory()->admin()->create()
            : $owner;
        $property = Property::factory()->for($owner)->create([
            'title' => 'Casa republicada',
            'district_code' => '150122',
            'status' => 'archived',
        ]);
        $remainingArchivedProperty = Property::factory()->for($owner)->create([
            'title' => 'Casa que permanece archivada',
            'district_code' => '040103',
            'status' => 'archived',
        ]);

        $response = $this->actingAs($actor)
            ->patch(route('panel.properties.republish', $property));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Propiedad republicada.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'available',
        ]);

        $this->actingAs($actor)
            ->get(route('panel.properties.archived'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $remainingArchivedProperty->id)
            );

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties.data', 1)
                ->where('properties.data.0.id', $property->id)
                ->where('properties.data.0.status', 'available')
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
            );

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/show')
                ->where('property.id', $property->id)
            );

        $this->post(route('properties.inquiries.store', $property), [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ])->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('property_inquiries', [
            'property_id' => $property->id,
            'email' => 'ana@example.com',
        ]);
    }

    public function test_agent_cannot_republish_another_agents_archived_property_and_preserves_it(): void
    {
        $agent = User::factory()->agent()->create();
        $owner = User::factory()->agent()->create();
        $property = Property::factory()->for($owner)->create([
            'status' => 'archived',
        ]);

        $response = $this->actingAs($agent)
            ->patch(route('panel.properties.republish', $property));

        $response->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $owner->id,
            'status' => 'archived',
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonArchivedPropertyStatuses(): array
    {
        return [
            'available' => ['available'],
            'sold' => ['sold'],
            'rented' => ['rented'],
        ];
    }

    #[DataProvider('nonArchivedPropertyStatuses')]
    public function test_republishing_non_archived_property_returns_404_and_preserves_its_status(string $status): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => $status,
        ]);

        $response = $this->actingAs($agent)
            ->patch(route('panel.properties.republish', $property));

        $response->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => $status,
        ]);
    }

    public function test_guest_is_redirected_for_archived_list_and_republishing(): void
    {
        $property = Property::factory()->create([
            'status' => 'archived',
        ]);

        $this->get(route('panel.properties.archived'))
            ->assertRedirect(route('login'));

        $this->patch(route('panel.properties.republish', $property))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
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
     *     department_code: string,
     *     province_code: string,
     *     district_code: string,
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
            ...$this->propertyLocation($property->district_code),
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'area' => $property->area,
            'photo_order' => [$photoId],
        ];
    }
}
