<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function closingStatuses(): array
    {
        return [
            'sale becomes sold' => ['sale', 'sold', 'Vendida', 'Propiedad marcada como vendida.'],
            'rent becomes rented' => ['rent', 'rented', 'Alquilada', 'Propiedad marcada como alquilada.'],
        ];
    }

    #[DataProvider('closingStatuses')]
    public function test_marking_an_available_property_closes_it_with_the_status_of_its_operation_and_shows_confirmation(
        string $operation,
        string $status,
        string $statusLabel,
        string $message,
    ): void {
        $property = Property::factory()->create([
            'operation' => $operation,
            'status' => 'available',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch(route('panel.properties.close', $property));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => $message,
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => $status,
        ]);

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('properties.0.id', $property->id)
                ->where('properties.0.status', $status)
                ->where('properties.0.status_label', $statusLabel)
                ->where('properties.0.status_action_label', 'Volver a disponible')
            );
    }

    public function test_closing_a_property_removes_it_from_the_catalog_and_the_zone_options(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa frente al parque',
            'zone' => 'Miraflores',
            'status' => 'available',
        ]);
        $other = Property::factory()->create([
            'title' => 'Departamento en Barranco',
            'zone' => 'Barranco',
            'status' => 'available',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('panel.properties.close', $property))
            ->assertRedirect(route('panel.properties.index'));

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $other->id)
                ->where('zones', ['Barranco'])
            );
    }

    public function test_reopening_a_closed_property_marks_it_available_and_returns_it_to_catalog_zones_and_public_page(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa frente al parque',
            'zone' => 'Miraflores',
            'status' => 'sold',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 0)
                ->where('zones', [])
            );

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch(route('panel.properties.reopen', $property));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Propiedad marcada como disponible.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'available',
        ]);

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('properties.0.id', $property->id)
                ->where('properties.0.status', 'available')
                ->where('properties.0.status_label', 'Disponible')
                ->where('properties.0.status_action_label', 'Marcar como vendida')
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('zones', ['Miraflores'])
            );

        $this->get(route('properties.show', $property))->assertOk();
    }

    public function test_guest_cannot_close_or_reopen_a_property(): void
    {
        $available = Property::factory()->create(['status' => 'available']);
        $closed = Property::factory()->create(['status' => 'sold']);

        $this->patch(route('panel.properties.close', $available))
            ->assertRedirect(route('login'));

        $this->patch(route('panel.properties.reopen', $closed))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('properties', [
            'id' => $available->id,
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $closed->id,
            'status' => 'sold',
        ]);
    }
}
