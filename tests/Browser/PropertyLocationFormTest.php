<?php

namespace Tests\Browser;

use App\Models\District;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\UbigeoSeeder;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PropertyLocationFormTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_location_search_folds_accents_and_selects_with_keyboard(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.')
                ->assertAttribute('#property-department', 'aria-haspopup', 'dialog')
                ->assertPresent('label#property-department-label[for="property-department"]')
                ->assertSeeIn('#property-department-label', '(obligatorio)')
                ->assertAttribute('#property-department', 'aria-labelledby', 'property-department-label property-department-value')
                ->assertMissing('#property-department[aria-required]')
                ->assertSeeIn('#property-department-value', 'Elige un departamento')
                ->click('#property-department')
                ->assertPresent('[role="dialog"][aria-label="Seleccionar departamento"]')
                ->waitFor('input[placeholder="Buscar ubicación..."]')
                ->assertAttribute('input[placeholder="Buscar ubicación..."]', 'aria-label', 'Buscar departamento')
                ->assertMissing('#property-department[role="combobox"]')
                ->type('input[placeholder="Buscar ubicación..."]', 'ancash')
                ->waitForText('Áncash')
                ->assertDontSee('Arequipa')
                ->keys('input[placeholder="Buscar ubicación..."]', '{arrow_down}', '{enter}')
                ->waitForTextIn('#property-department', 'Áncash')
                ->assertSeeIn('#property-department-value', 'Áncash')
                ->assertInputValue('input[name="department_code"]', '02');
        });
    }

    public function test_location_search_shows_an_empty_message(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.')
                ->click('#property-department')
                ->waitFor('input[placeholder="Buscar ubicación..."]')
                ->type('input[placeholder="Buscar ubicación..."]', 'sin coincidencias xyz')
                ->waitForText('No se encontraron ubicaciones.')
                ->assertSee('No se encontraron ubicaciones.');
        });
    }

    public function test_agent_publishes_a_property_choosing_department_province_and_district(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.')
                ->type('#title', 'Casa con jardín en Miraflores')
                ->type('#description', 'Casa luminosa con jardín y terraza.')
                ->type('#price', '250000')
                ->type('#bedrooms', '3')
                ->type('#bathrooms', '2')
                ->type('#area', '120')
                ->attach('#property-photos', base_path('database/seeders/assets/properties/casa.jpeg'));

            $this->selectLocation($browser, '#property-department', 'Lima');
            $this->selectLocation($browser, '#property-province', 'Lima');
            $this->selectLocation($browser, '#property-district', 'Miraflores');

            $browser->assertInputValue('input[name="department_code"]', '15')
                ->assertInputValue('input[name="province_code"]', '1501')
                ->assertInputValue('input[name="district_code"]', '150122')
                ->press('form button[type="submit"]')
                ->waitForText('Casa con jardín en Miraflores')
                ->assertSee('Miraflores, Lima · Lima');
        });

        $this->assertSame('150122', Property::sole()->district_code);
    }

    public function test_edit_sheet_preloads_the_saved_location_and_clears_the_dependent_choices(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
            'status' => 'available',
        ]);

        $property->photos()->create(['path' => 'properties/cover.jpg', 'position' => 1]);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa en Miraflores')
                ->press('Editar propiedad')
                ->waitForText('Actualiza la información y las fotos del anuncio.')
                ->waitForTextIn('#property-district', 'Miraflores')
                ->assertSeeIn('#property-department', 'Lima')
                ->assertSeeIn('#property-province', 'Lima')
                ->assertInputValue('input[name="department_code"]', '15')
                ->assertInputValue('input[name="province_code"]', '1501')
                ->assertInputValue('input[name="district_code"]', '150122');

            $this->selectLocation($browser, '#property-department', 'Lima');

            $browser->assertInputValue('input[name="department_code"]', '15')
                ->assertInputValue('input[name="province_code"]', '1501')
                ->assertInputValue('input[name="district_code"]', '150122');

            $this->selectLocation($browser, '#property-province', 'Lima');

            $browser->assertInputValue('input[name="province_code"]', '1501')
                ->assertInputValue('input[name="district_code"]', '150122');

            $this->selectLocation($browser, '#property-department', 'Arequipa');

            $browser->waitForTextIn('#property-province', 'Elige una provincia')
                ->assertInputValue('input[name="province_code"]', '')
                ->assertInputValue('input[name="district_code"]', '')
                ->assertDisabled('#property-district');

            $this->selectLocation($browser, '#property-province', 'Arequipa');
            $this->selectLocation($browser, '#property-district', 'Cayma');

            $browser->assertInputValue('input[name="district_code"]', '040103')
                ->press('form button[type="submit"]')
                ->waitForLocation(
                    parse_url(route('properties.show', $property), PHP_URL_PATH),
                )
                ->waitForText('Casa en Miraflores')
                ->assertSee('Cayma, Arequipa · Arequipa');
        });

        $this->assertSame('040103', $property->refresh()->district_code);
    }

    public function test_the_form_reports_a_catalog_failure_and_offers_a_retry(): void
    {
        $agent = User::factory()->agent()->create();

        District::query()->delete();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.')
                ->waitForText('No se pudo cargar el catálogo de ubicaciones.')
                ->assertDisabled('#property-department')
                ->assertDisabled('form button[type="submit"]');

            app(UbigeoSeeder::class)->run();

            $browser->press('Reintentar')
                ->waitUntil("document.querySelector('#property-department') && !document.querySelector('#property-department').disabled")
                ->assertEnabled('#property-department')
                ->assertEnabled('form button[type="submit"]');

            $this->selectLocation($browser, '#property-department', 'Lima');

            $browser->waitForTextIn('#property-province', 'Elige una provincia');
        });
    }

    public function test_empty_catalog_response_shows_error_and_retry(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index'));
            $browser->script("window.originalFetch = window.fetch; window.fetch = () => Promise.resolve(new Response(JSON.stringify({departments: [], provinces: [], districts: []}), {status: 200, headers: {'Content-Type': 'application/json'}}))");
            $browser->press('Publicar propiedad')
                ->waitForText('No se pudo cargar el catálogo de ubicaciones.')
                ->assertSee('Reintentar')
                ->assertDisabled('#property-department')
                ->assertDisabled('form button[type="submit"]');
            $browser->script('window.fetch = window.originalFetch');
            $browser->press('Reintentar')->waitUntil("document.querySelector('#property-department') && !document.querySelector('#property-department').disabled")
                ->assertEnabled('#property-department');
        });
    }

    public function test_the_form_keeps_its_data_and_marks_the_location_when_the_server_rejects_it(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.')
                ->type('#title', 'Casa sin ubicación')
                ->type('#description', 'Falta elegir la ubicación.')
                ->type('#price', '250000')
                ->type('#bedrooms', '2')
                ->type('#bathrooms', '1')
                ->type('#area', '90')
                ->attach('#property-photos', base_path('database/seeders/assets/properties/casa.jpeg'))
                ->press('form button[type="submit"]')
                ->waitForText('El campo departamento es obligatorio.')
                ->assertSee('El campo provincia es obligatorio.')
                ->assertSee('El campo distrito es obligatorio.')
                ->assertInputValue('#title', 'Casa sin ubicación')
                ->assertSeeIn('#property-department', 'Elige un departamento')
                ->assertAttribute('#property-department', 'aria-describedby', 'property-department-error')
                ->assertSeeIn('#property-department-error', 'El campo departamento es obligatorio.');
        });

        $this->assertDatabaseCount('properties', 0);
    }

    /**
     * Choose the option with the exact label of an open location selector.
     */
    private function selectLocation(Browser $browser, string $triggerSelector, string $optionLabel): void
    {
        $browser->click($triggerSelector)
            ->waitFor('[role="listbox"]');

        $browser->driver
            ->findElement(WebDriverBy::xpath(
                "//div[@role='option'][normalize-space(.)='{$optionLabel}']",
            ))
            ->click();

        $browser->waitUntilMissing('[role="listbox"]');
    }
}
