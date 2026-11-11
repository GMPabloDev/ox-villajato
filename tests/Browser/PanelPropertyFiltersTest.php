<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\User;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PanelPropertyFiltersTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function refreshTestDatabase(): void
    {
        $this->artisan('migrate', ['--force' => true]);
    }

    private const DETAIL_SELECTOR = 'section[aria-label="Detalle de la propiedad seleccionada"]';

    private const LIST_SELECTOR = 'ul[aria-label="Propiedades"] > li';

    public function test_unavailable_applied_location_stays_visible_in_filters(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['district_code' => '150122']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index', [
                'department_code' => '04', 'province_code' => '0401', 'district_code' => '040126',
            ]))->waitFor('[aria-label="Departamento"]')
                ->waitForText('Departamento Arequipa (filtro actual)')
                ->assertSee('Departamento Arequipa (filtro actual)')
                ->assertSee('Provincia Arequipa')
                ->assertSee('Distrito Yanahuara');
        });
    }

    public function test_status_tabs_filter_the_list_and_keep_the_status_in_the_url(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa disponible uno',
            'status' => 'available',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa disponible dos',
            'status' => 'available',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa vendida única',
            'status' => 'sold',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa vendida única')
                ->waitFor('[aria-label="Filtrar por estado"]')
                ->assertSeeIn('[aria-label="Filtrar por estado"] button:nth-child(1) span', '3')
                ->assertSeeIn('[aria-label="Filtrar por estado"] button:nth-child(2) span', '2')
                ->assertSeeIn('[aria-label="Filtrar por estado"] button:nth-child(3) span', '1')
                ->press('Vendidas')
                ->waitUntilMissingText('Casa disponible uno')
                ->assertSee('Casa vendida única')
                ->assertDontSee('Casa disponible dos')
                ->assertAttribute('[aria-label="Filtrar por estado"] button:nth-child(3)', 'aria-pressed', 'true')
                ->assertCount(self::LIST_SELECTOR, 1);

            $this->assertStringContainsString(
                'status=sold',
                $browser->driver->getCurrentURL(),
            );
        });
    }

    public function test_quick_filters_apply_at_once_and_the_side_panel_waits_for_mostrar_resultados(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en venta',
            'operation' => 'sale',
            'type' => 'house',
            'bedrooms' => 3,
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Departamento en alquiler',
            'operation' => 'rent',
            'type' => 'apartment',
            'bedrooms' => 2,
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitFor('[aria-label="Operación"]')
                ->assertCount(self::LIST_SELECTOR, 2);

            $this->selectOption($browser, '[aria-label="Operación"]', 'Alquiler');

            $browser->waitUntilMissingText('Casa en venta')
                ->assertSee('Departamento en alquiler')
                ->assertCount(self::LIST_SELECTOR, 1);

            $this->assertStringContainsString(
                'operation=rent',
                $browser->driver->getCurrentURL(),
            );

            $browser->visit(route('panel.properties.index', ['operation' => 'rent']))
                ->waitForText('Departamento en alquiler')
                ->assertSeeIn('[aria-label="Operación"]', 'Alquiler');

            $this->openFiltersPanel($browser);

            $browser->assertSeeIn('[role="dialog"] [aria-label="Operación"]', 'Alquiler');

            $this->selectOption($browser, '[role="dialog"] [aria-label="Operación"]', 'Venta');

            $browser->assertSeeIn('[role="dialog"] [aria-label="Operación"]', 'Venta')
                ->press('Cerrar')
                ->waitUntilMissing('[role="dialog"]')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertSee('Departamento en alquiler');

            $this->assertStringContainsString(
                'operation=rent',
                $browser->driver->getCurrentURL(),
            );

            $this->openFiltersPanel($browser);

            $browser->assertSeeIn('[role="dialog"] [aria-label="Operación"]', 'Alquiler');

            $this->selectOption($browser, '[role="dialog"] [aria-label="Operación"]', 'Venta');
            $this->selectOption($browser, '[role="dialog"] [aria-label="Tipo"]', 'Casa');
            $this->selectOption($browser, '[role="dialog"] [aria-label="Dormitorios"]', '3 o más');

            $browser->press('Mostrar resultados')
                ->waitUntilMissingText('Departamento en alquiler')
                ->waitUntilMissing('[role="dialog"]')
                ->assertSee('Casa en venta')
                ->assertCount(self::LIST_SELECTOR, 1);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('operation=sale', $url);
            $this->assertStringContainsString('type=house', $url);
            $this->assertStringContainsString('bedrooms=3', $url);
        });
    }

    public function test_an_invalid_price_range_stays_in_the_panel_and_removing_the_currency_clears_it(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en soles',
            'currency' => 'PEN',
            'price' => 1200,
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en dólares',
            'currency' => 'USD',
            'price' => 800,
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa en dólares');

            $browser->click('[aria-label="Ordenar propiedades"]')
                ->waitFor('[role="listbox"]')
                ->assertPresent('[role="listbox"] [role="option"][data-disabled]');

            $this->selectVisibleOption($browser, 'Más recientes');

            $this->openFiltersPanel($browser);

            $browser->assertDisabled('[name="min_price"]');

            $this->selectOption($browser, '[role="dialog"] [aria-label="Moneda"]', 'S/ PEN');

            $browser->assertEnabled('[name="min_price"]')
                ->type('[name="min_price"]', '5000')
                ->type('[name="max_price"]', '1000')
                ->press('Mostrar resultados')
                ->waitForText('El precio mínimo no puede ser mayor que el precio máximo.')
                ->assertPresent('[role="dialog"]')
                ->assertSee('Filtrar propiedades');

            $url = $browser->driver->getCurrentURL();
            $this->assertStringNotContainsString('min_price', $url);
            $this->assertStringNotContainsString('currency', $url);

            $browser->assertCount(self::LIST_SELECTOR, 2);

            $browser->type('[name="min_price"]', '500')
                ->type('[name="max_price"]', '1500')
                ->press('Mostrar resultados')
                ->waitUntilMissingText('Casa en dólares')
                ->waitUntilMissing('[role="dialog"]')
                ->assertSee('Casa en soles')
                ->assertCount(self::LIST_SELECTOR, 1);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('currency=PEN', $url);
            $this->assertStringContainsString('min_price=500', $url);
            $this->assertStringContainsString('max_price=1500', $url);

            $browser->click('[aria-label="Ordenar propiedades"]')
                ->waitFor('[role="listbox"]')
                ->assertMissing('[role="listbox"] [role="option"][data-disabled]');

            $this->selectVisibleOption($browser, 'Precio: menor primero');

            $browser->waitUntil("window.location.search.includes('sort=price-asc')");

            $this->openFiltersPanel($browser);

            $this->selectOption($browser, '[role="dialog"] [aria-label="Moneda"]', 'Todas las monedas');

            $browser->assertInputValue('[name="min_price"]', '')
                ->assertInputValue('[name="max_price"]', '')
                ->assertDisabled('[name="min_price"]')
                ->press('Mostrar resultados')
                ->waitForText('Casa en dólares')
                ->waitUntilMissing('[role="dialog"]')
                ->assertSeeIn('[aria-label="Ordenar propiedades"]', 'Más recientes');

            $url = $browser->driver->getCurrentURL();
            $this->assertStringNotContainsString('currency', $url);
            $this->assertStringNotContainsString('min_price', $url);
            $this->assertStringNotContainsString('max_price', $url);
            $this->assertStringNotContainsString('sort=', $url);
        });
    }

    public function test_removing_currency_resets_title_order(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['currency' => 'PEN']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index', ['currency' => 'PEN', 'sort' => 'title']))
                ->waitFor('[aria-label="Ordenar propiedades"]')
                ->assertSeeIn('[aria-label="Ordenar propiedades"]', 'Título A–Z');

            $this->openFiltersPanel($browser);
            $this->selectOption($browser, '[role="dialog"] [aria-label="Moneda"]', 'Todas las monedas');
            $browser->press('Mostrar resultados')
                ->waitUntilMissing('[role="dialog"]')
                ->assertSeeIn('[aria-label="Ordenar propiedades"]', 'Más recientes');
            $this->assertStringNotContainsString('sort=', $browser->driver->getCurrentURL());
        });
    }

    public function test_negative_prices_keep_the_draft_and_show_an_error(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['currency' => 'PEN']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index', ['currency' => 'PEN']))
                ->waitFor('[aria-label="Ordenar propiedades"]');
            $this->openFiltersPanel($browser);

            foreach (['min_price', 'max_price'] as $field) {
                $browser->type("[name=\"{$field}\"]", '-1')
                    ->press('Mostrar resultados')
                    ->waitForText('Los precios deben ser mayores o iguales a cero.')
                    ->assertPresent('[role="dialog"]')
                    ->assertInputValue("[name=\"{$field}\"]", '-1');
                $this->assertStringContainsString('currency=PEN', $browser->driver->getCurrentURL());
                $browser->clear("[name=\"{$field}\"]");
            }
        });
    }

    public function test_districts_with_the_same_name_are_identified_by_their_province_until_one_is_chosen(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['district_code' => '080106']);
        Property::factory()->for($agent)->create(['district_code' => '110111']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index'))
                ->waitFor('[aria-label="Departamento"]');
            $this->openFiltersPanel($browser);

            $browser->click('[role="dialog"] [aria-label="Distrito"]')->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Santiago (Cusco)')
                ->assertSeeIn('[role="listbox"]', 'Santiago (Ica)');

            $this->selectVisibleOption($browser, 'Santiago (Ica)');
            $browser->press('Mostrar resultados')
                ->waitUntilMissing('[role="dialog"]');

            $this->assertStringContainsString('district_code=110111', $browser->driver->getCurrentURL());

            $this->openFiltersPanel($browser);

            $this->selectOption($browser, '[role="dialog"] [aria-label="Provincia"]', 'Ica');
            $browser->click('[role="dialog"] [aria-label="Distrito"]')->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Santiago')
                ->assertDontSeeIn('[role="listbox"]', 'Santiago (Cusco)');
        });
    }

    public function test_search_ignores_case_and_accents_and_offers_to_clear_when_nothing_matches(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en Jesús María',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Departamento en Barranco',
            'district_code' => '150104',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Departamento en Barranco');

            $placeholder = (string) $browser->attribute(
                '#panel-property-search',
                'placeholder',
            );

            $this->assertStringNotContainsString('código', $placeholder);
            $this->assertStringContainsString('título', $placeholder);

            $browser->type('#panel-property-search', 'jesus maria')
                ->keys('#panel-property-search', [WebDriverKeys::ENTER])
                ->waitUntilMissingText('Departamento en Barranco')
                ->assertSee('Casa en Jesús María')
                ->assertCount(self::LIST_SELECTOR, 1);

            $this->assertStringContainsString(
                'search=',
                $browser->driver->getCurrentURL(),
            );

            $browser->type('#panel-property-search', 'playa')
                ->keys('#panel-property-search', [WebDriverKeys::ENTER])
                ->waitForText('No hay propiedades que coincidan')
                ->assertDontSee('Aún no hay propiedades para gestionar')
                ->assertMissing(self::LIST_SELECTOR)
                ->assertMissing(self::DETAIL_SELECTOR);

            $browser->press('Limpiar búsqueda y filtros')
                ->waitForText('Casa en Jesús María')
                ->waitForText('Departamento en Barranco')
                ->assertCount(self::LIST_SELECTOR, 2)
                ->assertInputValue('#panel-property-search', '');

            $this->assertStringNotContainsString(
                'search=',
                $browser->driver->getCurrentURL(),
            );
        });
    }

    public function test_clearing_filters_also_clears_a_pending_search(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['title' => 'Casa disponible', 'status' => 'available']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index', ['status' => 'sold']))
                ->waitForText('No hay propiedades que coincidan')
                ->type('#panel-property-search', 'pendiente')
                ->press('Limpiar búsqueda y filtros')
                ->waitForText('Casa disponible')
                ->assertInputValue('#panel-property-search', '');

            $browser->press('Disponibles')
                ->waitUntil("window.location.search.includes('status=available')")
                ->assertSee('Casa disponible');
            $this->assertStringNotContainsString('search=', $browser->driver->getCurrentURL());
        });
    }

    public function test_location_filters_from_the_url_are_applied_and_shown_in_the_controls(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Lima',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Yanahuara',
            'district_code' => '040126',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index', [
                'department_code' => '04',
                'province_code' => '0401',
                'district_code' => '040126',
            ]))
                ->waitForText('Casa en Yanahuara')
                ->assertDontSee('Casa en Lima')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertSeeIn('[aria-label="Departamento"]', 'Arequipa');

            $this->openFiltersPanel($browser);

            $browser->assertSeeIn('[role="dialog"] [aria-label="Departamento"]', 'Arequipa')
                ->assertSeeIn('[role="dialog"] [aria-label="Provincia"]', 'Arequipa')
                ->assertSeeIn('[role="dialog"] [aria-label="Distrito"]', 'Yanahuara');
        });
    }

    public function test_an_incompatible_location_from_the_url_is_cleared_and_not_applied(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Lima',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Yanahuara',
            'district_code' => '040126',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $url = route('panel.properties.index', [
                'department_code' => '15',
                'province_code' => '0401',
                'district_code' => '040126',
            ]);

            $browser->loginAs($agent)->visit($url)
                ->waitForText('Casa en Lima')
                ->assertDontSee('Casa en Yanahuara')
                ->assertCount(self::LIST_SELECTOR, 1);

            $this->openFiltersPanel($browser);

            $browser->assertSeeIn('[role="dialog"] [aria-label="Departamento"]', 'Lima')
                ->assertSeeIn('[role="dialog"] [aria-label="Provincia"]', 'Todas las provincias')
                ->assertSeeIn('[role="dialog"] [aria-label="Distrito"]', 'Todos los distritos');

            $browser->click('[role="dialog"] [aria-label="Distrito"]')->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Miraflores (Lima)')
                ->assertDontSeeIn('[role="listbox"]', 'Yanahuara');

            $this->selectVisibleOption($browser, 'Todos los distritos');
            $browser->press('Cerrar')->waitUntilMissing('[role="dialog"]');

            $browser->refresh()
                ->waitForText('Casa en Lima')
                ->assertDontSee('Casa en Yanahuara')
                ->assertCount(self::LIST_SELECTOR, 1);
        });
    }

    public function test_the_department_limits_the_provinces_and_districts_and_clears_incompatible_choices(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Barranco',
            'district_code' => '150104',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Yanahuara',
            'district_code' => '040126',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa en Yanahuara');

            $this->selectOption($browser, '[aria-label="Departamento"]', 'Lima');

            $browser->waitUntilMissingText('Casa en Yanahuara')
                ->assertCount(self::LIST_SELECTOR, 2);

            $this->openFiltersPanel($browser);

            $this->selectOption($browser, '[role="dialog"] [aria-label="Provincia"]', 'Lima');
            $this->selectOption($browser, '[role="dialog"] [aria-label="Distrito"]', 'Barranco');

            $browser->press('Mostrar resultados')
                ->waitUntilMissingText('Casa en Miraflores')
                ->assertSee('Casa en Barranco')
                ->assertCount(self::LIST_SELECTOR, 1);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('department_code=15', $url);
            $this->assertStringContainsString('province_code=1501', $url);
            $this->assertStringContainsString('district_code=150104', $url);

            $this->openFiltersPanel($browser);
            $this->selectOption($browser, '[role="dialog"] [aria-label="Departamento"]', 'Arequipa');

            $browser->assertSeeIn('[role="dialog"] [aria-label="Provincia"]', 'Todas las provincias')
                ->assertSeeIn('[role="dialog"] [aria-label="Distrito"]', 'Todos los distritos');

            $browser->click('[role="dialog"] [aria-label="Provincia"]')->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Arequipa')
                ->assertDontSeeIn('[role="listbox"]', 'Lima');

            $this->selectVisibleOption($browser, 'Arequipa');
            $browser->click('[role="dialog"] [aria-label="Distrito"]')->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Yanahuara')
                ->assertDontSeeIn('[role="listbox"]', 'Barranco');

            $this->selectVisibleOption($browser, 'Yanahuara');
            $browser->press('Mostrar resultados')
                ->waitUntilMissing('[role="dialog"]')
                ->assertSee('Casa en Yanahuara')
                ->assertCount(self::LIST_SELECTOR, 1);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('department_code=04', $url);
            $this->assertStringContainsString('district_code=040126', $url);
            $this->assertStringNotContainsString('district_code=150104', $url);

            $this->selectOption($browser, '[aria-label="Departamento"]', 'Lima');

            $browser->waitUntilMissingText('Casa en Yanahuara')
                ->assertCount(self::LIST_SELECTOR, 2);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('department_code=15', $url);
            $this->assertStringNotContainsString('province_code=', $url);
            $this->assertStringNotContainsString('district_code=', $url);
        });
    }

    public function test_pagination_shows_fifteen_properties_and_a_filter_change_returns_to_the_first_page(): void
    {
        $agent = User::factory()->agent()->create();

        foreach (range(1, 16) as $index) {
            Property::factory()->for($agent)->create([
                'title' => "Casa en Lima {$index}",
                'district_code' => '150122',
            ]);
        }

        Property::factory()->for($agent)->create([
            'title' => 'Casa en Cusco',
            'district_code' => '080106',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Mostrando 1 a 15 de 17 propiedades')
                ->assertCount(self::LIST_SELECTOR, 15);

            $browser->click('nav[aria-label="Paginación de propiedades"] a[href*="page=2"]')
                ->waitForText('Mostrando 16 a 17 de 17 propiedades')
                ->assertCount(self::LIST_SELECTOR, 2);

            $browser->refresh()
                ->waitForText('Mostrando 16 a 17 de 17 propiedades')
                ->assertCount(self::LIST_SELECTOR, 2);

            $this->selectOption($browser, '[aria-label="Departamento"]', 'Lima');

            $browser->waitUntilMissingText('Casa en Cusco')
                ->waitForText('Mostrando 1 a 15 de 16 propiedades')
                ->assertCount(self::LIST_SELECTOR, 15);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('department_code=15', $url);
            $this->assertStringNotContainsString('page=', $url);
        });
    }

    public function test_search_finds_properties_by_location_names_ignoring_case_and_accents(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create([
            'title' => 'Lote uno',
            'district_code' => '040126',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Lote dos',
            'district_code' => '150502',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index'))
                ->waitForText('Lote dos');

            foreach ([
                'yanahuara' => 'Lote uno',
                'AREQUIPA' => 'Lote uno',
                'CAÑETE' => 'Lote dos',
            ] as $term => $expectedTitle) {
                $browser->visit(route('panel.properties.index', ['search' => $term]))
                    ->waitForText($expectedTitle)
                    ->assertCount(self::LIST_SELECTOR, 1);
            }
        });
    }

    public function test_filtered_paginated_url_survives_reload_and_respects_each_agents_permissions(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        foreach (range(1, 16) as $number) {
            foreach ([$agent, $otherAgent] as $owner) {
                $label = $owner->is($agent) ? 'propia' : 'ajena';
                Property::factory()->for($owner)->create([
                    'title' => sprintf('Casa filtro %s %02d', $label, $number),
                    'operation' => 'sale', 'type' => 'house', 'status' => 'available',
                    'district_code' => '150122', 'currency' => 'PEN', 'price' => 1000 + $number,
                ]);
            }
        }
        foreach ([['title' => 'Sin coincidencia'], ['operation' => 'rent'], ['type' => 'apartment'], ['district_code' => '040126'], ['currency' => 'USD']] as $mismatch) {
            Property::factory()->for($agent)->create(array_merge([
                'title' => 'Casa filtro descartada', 'operation' => 'sale', 'type' => 'house',
                'status' => 'available', 'district_code' => '150122', 'currency' => 'PEN', 'price' => 10,
            ], $mismatch));
        }
        $url = route('panel.properties.index', [
            'search' => 'Casa filtro', 'operation' => 'sale', 'type' => 'house',
            'department_code' => '15', 'district_code' => '150122', 'currency' => 'PEN', 'sort' => 'price-asc', 'page' => 2,
        ]);

        $this->browse(function (Browser $browser) use ($agent, $otherAgent, $url): void {
            $browser->loginAs($agent)->visit($url)
                ->waitForText('Casa filtro propia 16')
                ->assertDontSee('Casa filtro ajena')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertInputValue('#panel-property-search', 'Casa filtro')
                ->assertSeeIn('[aria-label="Operación"]', 'Venta')
                ->assertSeeIn('[aria-label="Departamento"]', 'Lima')
                ->assertSeeIn('[aria-label="Ordenar propiedades"]', 'Precio: menor primero');
            $beforeReload = $browser->driver->getCurrentURL();
            $browser->refresh()->waitForText('Casa filtro propia 16')
                ->assertDontSee('Casa filtro ajena')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertInputValue('#panel-property-search', 'Casa filtro')
                ->assertSeeIn('[aria-label="Ordenar propiedades"]', 'Precio: menor primero');
            $this->assertSame($beforeReload, $browser->driver->getCurrentURL());
            $browser->loginAs($otherAgent)->visit($url)
                ->waitForText('Casa filtro ajena 16')
                ->assertDontSee('Casa filtro propia')
                ->assertCount(self::LIST_SELECTOR, 1);
        });
    }

    public function test_edit_and_archive_actions_work_from_filtered_results(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa filtrada original', 'status' => 'available', 'operation' => 'sale',
        ]);
        $photo = PropertyPhoto::factory()->for($property)->create(['path' => 'properties/test-photo.png']);
        Storage::disk('public')->put($photo->path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/a9sAAAAASUVORK5CYII='));
        Property::factory()->for($agent)->create(['title' => 'Otro inmueble']);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index', ['search' => 'Casa filtrada']))
                ->waitForText('Casa filtrada original')
                ->assertDontSee('Otro inmueble')
                ->press('Editar propiedad')
                ->waitFor('[role="dialog"] #title')
                ->type('[role="dialog"] #title', 'Casa filtrada editada')
                ->press('Guardar cambios')
                ->waitForLocation(parse_url(route('properties.show', $property), PHP_URL_PATH))
                ->waitForText('Casa filtrada editada')
                ->visit(route('panel.properties.index', ['search' => 'Casa filtrada']))
                ->waitForText('Casa filtrada editada')
                ->press('Archivar propiedad')
                ->waitFor('[role="dialog"]')
                ->press('Confirmar')
                ->waitForText('Otro inmueble')
                ->assertDontSee('Casa filtrada editada');
        });

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'title' => 'Casa filtrada editada', 'status' => 'archived']);
    }

    public function test_the_detail_falls_back_to_the_first_visible_property_and_disappears_without_results(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa antigua',
            'district_code' => '150140',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa reciente',
            'district_code' => '150122',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa antigua')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Casa reciente');

            $browser->click('ul[aria-label="Propiedades"] li:nth-child(2) button')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Casa antigua');

            $browser->type('#panel-property-search', 'reciente')
                ->keys('#panel-property-search', [WebDriverKeys::ENTER])
                ->waitUntilMissingText('Casa antigua')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Casa reciente');

            $browser->keys('#panel-property-search', [WebDriverKeys::CONTROL, 'a'], WebDriverKeys::BACKSPACE)
                ->waitForText('Casa antigua')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Casa reciente');

            $browser->type('#panel-property-search', 'playa')
                ->keys('#panel-property-search', [WebDriverKeys::ENTER])
                ->waitForText('No hay propiedades que coincidan')
                ->assertMissing(self::DETAIL_SELECTOR);
        });
    }

    /**
     * Open the side panel and wait until it stops sliding in, so the controls
     * inside can be clicked at a stable position.
     */
    private function openFiltersPanel(Browser $browser): void
    {
        $browser->press('Filtros')
            ->waitFor('[role="dialog"]')
            ->waitUntil(
                'document.querySelector(\'[role="dialog"]\').getBoundingClientRect().right <= window.innerWidth + 1',
            );
    }

    /**
     * Choose an option from a Radix select rendered by the panel.
     */
    private function selectOption(Browser $browser, string $triggerSelector, string $optionLabel): void
    {
        $browser->click($triggerSelector)
            ->waitFor('[role="listbox"]');

        $this->selectVisibleOption($browser, $optionLabel);
    }

    /**
     * Click an option of the listbox that is already open.
     */
    private function selectVisibleOption(Browser $browser, string $optionLabel): void
    {
        $browser->driver
            ->findElement(WebDriverBy::xpath(
                "//div[@role='option'][normalize-space(.)='{$optionLabel}']",
            ))
            ->click();

        $browser->waitUntilMissing('[role="listbox"]');
    }
}
