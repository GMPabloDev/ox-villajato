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

    public function test_special_city_names_render_and_keep_legitimate_zones(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['title' => 'Casa Lima', 'city' => 'Lima']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index', ['city' => 'constructor']))
                ->waitForText('No hay propiedades que coincidan')
                ->assertSee('No hay propiedades que coincidan');
        });

        Property::factory()->for($agent)->create(['title' => 'Casa especial', 'city' => 'constructor', 'zone' => 'Centro']);
        $this->browse(function (Browser $browser): void {
            $browser->visit(route('panel.properties.index', ['city' => 'constructor']))
                ->waitForText('Casa especial')
                ->assertSee('Casa especial');
            $this->openFiltersPanel($browser);
            $browser->click('[role="dialog"] [aria-label="Zona"]')->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Centro');
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

    public function test_zones_shared_by_cities_appear_only_once_without_a_city_filter(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['city' => 'Lima', 'zone' => 'Centro']);
        Property::factory()->for($agent)->create(['city' => 'Arequipa', 'zone' => 'Centro']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index'))
                ->waitFor('[aria-label="Ciudad"]');
            $this->openFiltersPanel($browser);
            $browser->click('[role="dialog"] [aria-label="Zona"]')->waitFor('[role="listbox"]');
            $options = $browser->driver->findElements(WebDriverBy::xpath("//div[@role='option'][normalize-space(.)='Centro']"));
            $this->assertCount(1, $options);
        });
    }

    public function test_search_ignores_case_and_accents_and_offers_to_clear_when_nothing_matches(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en Jesús María',
            'city' => 'Lima',
            'zone' => 'Jesús María',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Departamento en Barranco',
            'city' => 'Lima',
            'zone' => 'Barranco',
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

    public function test_location_controls_accept_all_and_any_as_real_names(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['title' => 'Propiedad especial', 'city' => 'all', 'zone' => 'any']);
        Property::factory()->for($agent)->create(['title' => 'Otra propiedad', 'city' => 'Lima', 'zone' => 'Surco']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index'))->waitForText('Otra propiedad');
            $this->selectOption($browser, '[aria-label="Ciudad"]', 'all');
            $browser->waitUntilMissingText('Otra propiedad')->assertSee('Propiedad especial');
            $this->openFiltersPanel($browser);
            $this->selectOption($browser, '[role="dialog"] [aria-label="Zona"]', 'any');
            $browser->press('Mostrar resultados')->waitUntilMissing('[role="dialog"]')->assertSee('Propiedad especial');
            $this->assertStringContainsString('city=all', $browser->driver->getCurrentURL());
            $this->assertStringContainsString('zone=any', $browser->driver->getCurrentURL());
        });
    }

    public function test_incompatible_zone_from_url_is_not_offered_for_the_city(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['city' => 'Lima', 'zone' => 'Surco']);
        Property::factory()->for($agent)->create(['city' => 'Arequipa', 'zone' => 'Yanahuara']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.properties.index', ['city' => 'Lima', 'zone' => 'Yanahuara']))
                ->waitForText('No hay propiedades que coincidan');
            $this->openFiltersPanel($browser);
            $browser->click('[role="dialog"] [aria-label="Zona"]')->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Surco')
                ->assertDontSeeIn('[role="listbox"]', 'Yanahuara');
        });
    }

    public function test_the_city_limits_the_zones_and_clears_a_zone_from_another_city(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
            'city' => 'Lima',
            'zone' => 'Miraflores',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Surco',
            'city' => 'Lima',
            'zone' => 'Surco',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Yanahuara',
            'city' => 'Arequipa',
            'zone' => 'Yanahuara',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa en Yanahuara');

            $this->selectOption($browser, '[aria-label="Ciudad"]', 'Lima');

            $browser->waitUntilMissingText('Casa en Yanahuara')
                ->assertCount(self::LIST_SELECTOR, 2);

            $this->openFiltersPanel($browser);

            $this->selectOption($browser, '[role="dialog"] [aria-label="Zona"]', 'Miraflores');

            $browser->press('Mostrar resultados')
                ->waitUntilMissingText('Casa en Surco')
                ->assertSee('Casa en Miraflores')
                ->assertCount(self::LIST_SELECTOR, 1);

            $this->selectOption($browser, '[aria-label="Ciudad"]', 'Arequipa');

            $browser->waitUntilMissingText('Casa en Miraflores')
                ->assertSee('Casa en Yanahuara');

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('city=Arequipa', $url);
            $this->assertStringNotContainsString('zone=', $url);

            $this->openFiltersPanel($browser);

            $this->selectOption($browser, '[role="dialog"] [aria-label="Ciudad"]', 'Lima');

            $browser->assertSeeIn('[role="dialog"] [aria-label="Zona"]', 'Todas las zonas');

            $this->selectOption($browser, '[role="dialog"] [aria-label="Zona"]', 'Miraflores');

            $browser->assertSeeIn('[role="dialog"] [aria-label="Zona"]', 'Miraflores');

            $this->selectOption($browser, '[role="dialog"] [aria-label="Ciudad"]', 'Arequipa');

            $browser->assertSeeIn('[role="dialog"] [aria-label="Zona"]', 'Todas las zonas');

            $browser->click('[role="dialog"] [aria-label="Zona"]')
                ->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', 'Yanahuara')
                ->assertDontSeeIn('[role="listbox"]', 'Miraflores');

            $this->selectVisibleOption($browser, 'Todas las zonas');

            $browser->press('Cerrar')
                ->waitUntilMissing('[role="dialog"]')
                ->assertSee('Casa en Yanahuara')
                ->assertCount(self::LIST_SELECTOR, 1);

            $this->assertStringContainsString(
                'city=Arequipa',
                $browser->driver->getCurrentURL(),
            );
        });
    }

    public function test_pagination_shows_fifteen_properties_and_a_filter_change_returns_to_the_first_page(): void
    {
        $agent = User::factory()->agent()->create();

        foreach (range(1, 16) as $index) {
            Property::factory()->for($agent)->create([
                'title' => "Casa en Lima {$index}",
                'city' => 'Lima',
                'zone' => 'Miraflores',
            ]);
        }

        Property::factory()->for($agent)->create([
            'title' => 'Casa en Cusco',
            'city' => 'Cusco',
            'zone' => 'Wanchaq',
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

            $this->selectOption($browser, '[aria-label="Ciudad"]', 'Lima');

            $browser->waitUntilMissingText('Casa en Cusco')
                ->waitForText('Mostrando 1 a 15 de 16 propiedades')
                ->assertCount(self::LIST_SELECTOR, 15);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('city=Lima', $url);
            $this->assertStringNotContainsString('page=', $url);
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
                    'city' => 'Lima', 'currency' => 'PEN', 'price' => 1000 + $number,
                ]);
            }
        }
        foreach ([['title' => 'Sin coincidencia'], ['operation' => 'rent'], ['type' => 'apartment'], ['city' => 'Cusco'], ['currency' => 'USD']] as $mismatch) {
            Property::factory()->for($agent)->create(array_merge([
                'title' => 'Casa filtro descartada', 'operation' => 'sale', 'type' => 'house',
                'status' => 'available', 'city' => 'Lima', 'currency' => 'PEN', 'price' => 10,
            ], $mismatch));
        }
        $url = route('panel.properties.index', [
            'search' => 'Casa filtro', 'operation' => 'sale', 'type' => 'house',
            'city' => 'Lima', 'currency' => 'PEN', 'sort' => 'price-asc', 'page' => 2,
        ]);

        $this->browse(function (Browser $browser) use ($agent, $otherAgent, $url): void {
            $browser->loginAs($agent)->visit($url)
                ->waitForText('Casa filtro propia 16')
                ->assertDontSee('Casa filtro ajena')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertInputValue('#panel-property-search', 'Casa filtro')
                ->assertSeeIn('[aria-label="Operación"]', 'Venta')
                ->assertSeeIn('[aria-label="Ciudad"]', 'Lima')
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
            'city' => 'Lima',
            'zone' => 'Surco',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa reciente',
            'city' => 'Lima',
            'zone' => 'Miraflores',
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
