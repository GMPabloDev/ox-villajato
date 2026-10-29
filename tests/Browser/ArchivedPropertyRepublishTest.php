<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ArchivedPropertyRepublishTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_canceling_republication_keeps_property_archived(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
            'title' => 'Casa por republicar',
        ]);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.archived'))
                ->waitForText($property->title)
                ->press('Republicar')
                ->waitForText('Republicar propiedad')
                ->assertSee('«Casa por republicar» volverá a aparecer en el catálogo')
                ->press('Cancelar')
                ->waitUntilMissingText('Republicar propiedad')
                ->assertSee('Republicar');
        });

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
    }

    public function test_confirming_republication_shows_success_and_makes_property_available(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
            'title' => 'Casa republicada',
        ]);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.archived'))
                ->waitForText($property->title)
                ->press('Republicar')
                ->waitForText('Republicar propiedad')
                ->press('Confirmar')
                ->waitForRoute('panel.properties.index')
                ->waitForText('Propiedad republicada.')
                ->assertSee($property->title);
        });

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'available',
        ]);
    }
}
