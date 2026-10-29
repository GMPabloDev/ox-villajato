<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ArchivedPropertyDeletionTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_delete_archived_property_from_archived_page_by_confirming_its_title(): void
    {
        $admin = User::factory()->admin()->create();
        $title = 'Casa archivada para eliminar desde Dusk';
        $property = Property::factory()->create([
            'status' => 'archived',
            'title' => $title,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $title): void {
            $browser->loginAs($admin)
                ->visit(route('panel.properties.archived'))
                ->waitForText($title)
                ->press('Eliminar')
                ->waitForText('Eliminar propiedad')
                ->assertSee("Escribe exactamente «{$title}» para confirmar.")
                ->type('title_confirmation', $title)
                ->press('Eliminar definitivamente')
                ->waitForText('Propiedad eliminada.')
                ->waitUntilMissingText($title)
                ->assertSee('No hay propiedades archivadas.');
        });

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_agent_does_not_see_delete_option_for_their_archived_property(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
            'title' => 'Casa archivada del agente',
        ]);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.archived'))
                ->waitForText($property->title)
                ->assertSee('Republicar')
                ->assertDontSee('Eliminar');
        });
    }
}
