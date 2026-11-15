<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PropertyGarageAndFurnishedFormTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_publish_form_starts_without_a_preselected_garage_or_furnishing_answer(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.')
                ->assertSee('La propiedad incluye un espacio de estacionamiento, techado o no.')
                ->assertRadioNotSelected('has_garage', '1')
                ->assertRadioNotSelected('has_garage', '0')
                ->assertRadioNotSelected('is_furnished', '1')
                ->assertRadioNotSelected('is_furnished', '0')
                ->click('input[name="has_garage"][value="1"] + span')
                ->click('input[name="is_furnished"][value="0"] + span')
                ->assertRadioSelected('has_garage', '1')
                ->assertRadioSelected('is_furnished', '0');

            $offset = $browser->driver->executeScript(<<<'JS'
                const top = (name) => document.querySelector('input[name="' + name + '"][value="1"]').nextElementSibling.getBoundingClientRect().top;
                return Math.abs(top('has_garage') - top('is_furnished'));
                JS);
            $this->assertLessThanOrEqual(1, $offset, 'Both yes/no groups must start at the same height.');
        });
    }

    public function test_segmented_choices_have_named_groups_containing_their_radios(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.');

            foreach (['operation' => 'Operación', 'type' => 'Tipo de propiedad', 'has_garage' => 'Garaje', 'is_furnished' => 'Amueblado'] as $name => $label) {
                $result = $browser->driver->executeScript(<<<'JS'
                    const radios = [...document.querySelectorAll(`input[type="radio"][name="${arguments[0]}"]`)];
                    const group = radios[0]?.closest('[role="group"]');
                    const label = group && document.getElementById(group.getAttribute('aria-labelledby'));
                    return radios.length === 2 && label?.firstChild?.textContent.trim() === arguments[1] && radios.every(radio => group.contains(radio));
                    JS, [$name, $label]);
                $this->assertTrue($result, "The {$label} radios must belong to a named group.");
            }
        });
    }

    public function test_panel_features_form_a_centered_block_on_desktop_and_mobile(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['title' => 'Casa para vista previa']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->resize(1440, 900)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa para vista previa');

            foreach ([false, true] as $mobile) {
                if ($mobile) {
                    $browser->resize(390, 844)
                        ->click('ul[aria-label="Propiedades"] button')
                        ->waitForText('Detalle de la propiedad');
                }

                $selector = $mobile ? '[role="dialog"]' : 'section[aria-label="Detalle de la propiedad seleccionada"]';
                $result = $browser->driver->executeScript(<<<'JS'
                    const root = document.querySelector(arguments[0]);
                    const features = [...root.querySelectorAll('[data-test^="panel-"]')].map(el => el.getBoundingClientRect());
                    const [a, b, c, d, e] = features;
                    return features.length === 5 && a.top === b.top && b.top === c.top && d.top === e.top && d.top > a.top && Math.abs((a.left + c.right) - (d.left + e.right)) < 2 && d.right <= e.left;
                    JS, [$selector]);
                $this->assertTrue($result);
            }
        });
    }

    public function test_edit_form_preloads_the_saved_answers_and_lets_the_agent_switch_both_of_them(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa con estacionamiento',
            'status' => 'available',
            'has_garage' => true,
            'is_furnished' => false,
        ]);

        $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->resize(1440, 900)
                ->loginAs($agent)
                ->visit(route('properties.show', $property))
                ->assertSeeIn('[data-test="property-garage"]', 'Sí')
                ->assertSeeIn('[data-test="property-furnished"]', 'No')
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa con estacionamiento')
                ->assertVisible('[data-test="panel-garage"] dt:not(.sr-only)')
                ->assertVisible('[data-test="panel-furnished"] dt:not(.sr-only)')
                ->assertSeeIn('[data-test="panel-garage"]', 'Sí')
                ->assertSeeIn('[data-test="panel-furnished"]', 'No')
                ->press('Editar propiedad')
                ->waitForText('Actualiza la información y las fotos del anuncio.')
                ->waitForTextIn('#property-district', 'Lima')
                ->assertRadioSelected('has_garage', '1')
                ->assertRadioSelected('is_furnished', '0')
                ->click('input[name="has_garage"][value="0"] + span')
                ->click('input[name="is_furnished"][value="1"] + span')
                ->assertRadioSelected('has_garage', '0')
                ->assertRadioSelected('is_furnished', '1')
                ->press('form button[type="submit"]')
                ->waitForLocation(route('properties.show', $property))
                ->assertSeeIn('[data-test="property-garage"]', 'No')
                ->assertSeeIn('[data-test="property-furnished"]', 'Sí');
        });

        $property->refresh();

        $this->assertFalse($property->has_garage);
        $this->assertTrue($property->is_furnished);
    }
}
