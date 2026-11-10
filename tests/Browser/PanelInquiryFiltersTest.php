<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\User;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PanelInquiryFiltersTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function refreshTestDatabase(): void
    {
        $this->artisan('migrate', ['--force' => true]);
    }

    private const DETAIL_SELECTOR = 'section[aria-label="Detalle de la consulta seleccionada"]';

    private const LIST_SELECTOR = 'ul[aria-label="Consultas recibidas"] > li';

    private const RESULTS_SELECTOR = 'p[aria-live="polite"]';

    private const COUNT_SELECTOR = 'section[aria-label="Consultas recibidas"] header h2 span';

    public function test_search_waits_a_pause_and_the_header_keeps_the_accessible_total(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);

        PropertyInquiry::factory()->for($property)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@example.com',
            'message' => 'Quisiera coordinar una visita en Jesús María.',
            'created_at' => '2026-10-28 10:00:00',
        ]);
        PropertyInquiry::factory()->for($property)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis.rojas@example.com',
            'message' => 'Me interesa el jardín interior.',
            'created_at' => '2026-10-28 11:00:00',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.inquiries.index'))
                ->waitForText('Ana Pérez')
                ->assertCount(self::LIST_SELECTOR, 2)
                ->assertSeeIn(self::COUNT_SELECTOR, '2')
                ->assertSeeIn(self::RESULTS_SELECTOR, '2 consultas en total');

            $browser->type('#panel-inquiry-search', 'jesus maria')
                ->pause(100);
            $this->assertStringNotContainsString('search=', $browser->driver->getCurrentURL());
            $browser->assertCount(self::LIST_SELECTOR, 2)
                ->waitUntilMissingText('Luis Rojas')
                ->assertSee('Ana Pérez')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertSeeIn(self::COUNT_SELECTOR, '2')
                ->assertSeeIn(self::RESULTS_SELECTOR, '1 coincidencia de 2 consultas')
                ->assertSeeIn('li:has(a[href="/panel/inquiries"]) [data-sidebar="menu-badge"]', '2');

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('search=', $url);

            $beforeReload = $url;
            $browser->refresh()
                ->waitForText('Ana Pérez')
                ->assertDontSee('Luis Rojas')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertInputValue('#panel-inquiry-search', 'jesus maria')
                ->assertSeeIn(self::RESULTS_SELECTOR, '1 coincidencia de 2 consultas');

            $this->assertSame($beforeReload, $browser->driver->getCurrentURL());
        });
    }

    public function test_the_property_selector_and_order_apply_at_once_and_survive_a_reload(): void
    {
        $agent = User::factory()->agent()->create();
        $firstCasa = Property::factory()->for($agent)->create([
            'title' => 'Casa familiar',
        ]);
        $secondCasa = Property::factory()->for($agent)->create([
            'title' => 'Casa familiar',
        ]);
        $barranco = Property::factory()->for($agent)->create([
            'title' => 'Departamento en Barranco',
        ]);

        PropertyInquiry::factory()->for($firstCasa)->create([
            'name' => 'Ana Antigua',
            'email' => 'ana.antigua@example.com',
            'message' => 'Primera consulta recibida.',
            'created_at' => '2026-10-28 10:00:00',
        ]);
        PropertyInquiry::factory()->for($secondCasa)->create([
            'name' => 'Luis Nuevo',
            'email' => 'luis.nuevo@example.com',
            'message' => 'Segunda consulta recibida.',
            'created_at' => '2026-10-28 11:00:00',
        ]);
        PropertyInquiry::factory()->for($barranco)->create([
            'name' => 'Carla Reciente',
            'email' => 'carla.reciente@example.com',
            'message' => 'Tercera consulta recibida.',
            'created_at' => '2026-10-28 12:00:00',
        ]);

        $this->browse(function (Browser $browser) use ($agent, $firstCasa, $secondCasa): void {
            $browser->loginAs($agent)
                ->visit(route('panel.inquiries.index'))
                ->waitForText('Ana Antigua')
                ->assertSeeIn(self::LIST_SELECTOR.':nth-child(1)', 'Carla Reciente');

            $this->selectOption($browser, '[aria-label="Ordenar consultas"]', 'Más antiguas');

            $browser->waitUntil("window.location.search.includes('sort=oldest')")
                ->waitForTextIn(self::LIST_SELECTOR.':nth-child(1)', 'Ana Antigua')
                ->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas');

            $browser->click('[aria-label="Filtrar por propiedad"]')
                ->waitFor('[role="listbox"]')
                ->assertSeeIn('[role="listbox"]', "Casa familiar (#{$firstCasa->id})")
                ->assertSeeIn('[role="listbox"]', "Casa familiar (#{$secondCasa->id})");

            $this->selectVisibleOption($browser, "Casa familiar (#{$secondCasa->id})");

            $browser->waitUntilMissingText('Ana Antigua')
                ->waitUntilMissingText('Carla Reciente')
                ->assertSee('Luis Nuevo')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertSeeIn('[aria-label="Filtrar por propiedad"]', "Casa familiar (#{$secondCasa->id})");

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString("property={$secondCasa->id}", $url);
            $this->assertStringContainsString('sort=oldest', $url);

            $beforeReload = $url;

            $browser->refresh()
                ->waitForText('Luis Nuevo')
                ->assertDontSee('Ana Antigua')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertSeeIn('[aria-label="Filtrar por propiedad"]', "Casa familiar (#{$secondCasa->id})")
                ->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas');

            $this->assertSame($beforeReload, $browser->driver->getCurrentURL());

            $browser->back()
                ->waitForText('Ana Antigua')
                ->assertSeeIn('[aria-label="Filtrar por propiedad"]', 'Todas las propiedades')
                ->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas');
            $browser->forward()
                ->waitUntilMissingText('Ana Antigua')
                ->assertSeeIn('[aria-label="Filtrar por propiedad"]', "Casa familiar (#{$secondCasa->id})")
                ->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas');
        });
    }

    public function test_changing_a_filter_returns_to_the_first_page_and_pagination_keeps_the_filters(): void
    {
        $agent = User::factory()->agent()->create();
        $miraflores = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);
        $surco = Property::factory()->for($agent)->create([
            'title' => 'Casa en Surco',
        ]);

        foreach (range(1, 16) as $index) {
            PropertyInquiry::factory()->for($miraflores)->create([
                'name' => "Ana {$index}",
                'email' => "ana{$index}@example.com",
                'message' => "Consulta número {$index}.",
                'created_at' => sprintf('2026-10-28 09:%02d:00', $index),
            ]);
        }

        PropertyInquiry::factory()->for($surco)->create([
            'name' => 'Luis Único',
            'email' => 'luis.unico@example.com',
            'message' => 'Otra consulta.',
            'created_at' => '2026-10-28 13:00:00',
        ]);

        $this->browse(function (Browser $browser) use ($agent, $miraflores): void {
            $browser->loginAs($agent)
                ->visit(route('panel.inquiries.index'))
                ->waitForText('Mostrando 1 a 15 de 17 consultas')
                ->assertCount(self::LIST_SELECTOR, 15);

            $browser->click('nav[aria-label="Paginación de consultas"] a[href*="page=2"]')
                ->waitForText('Mostrando 16 a 17 de 17 consultas')
                ->assertCount(self::LIST_SELECTOR, 2);

            $this->selectOption($browser, '[aria-label="Filtrar por propiedad"]', 'Casa en Miraflores');

            $browser->waitUntilMissingText('Luis Único')
                ->waitForText('Mostrando 1 a 15 de 16 coincidencias')
                ->assertCount(self::LIST_SELECTOR, 15);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString("property={$miraflores->id}", $url);
            $this->assertStringNotContainsString('page=', $url);

            $browser->click('nav[aria-label="Paginación de consultas"] a[href*="page=2"]')
                ->waitForText('Mostrando 16 a 16 de 16 coincidencias')
                ->assertCount(self::LIST_SELECTOR, 1);

            $filteredPageUrl = $browser->driver->getCurrentURL();
            $this->assertStringContainsString("property={$miraflores->id}", $filteredPageUrl);
            $this->assertStringContainsString('page=2', $filteredPageUrl);

            $browser->type('#panel-inquiry-search', 'Ana')
                ->waitForText('Mostrando 1 a 15 de 16 coincidencias');
            $this->assertStringNotContainsString('page=', $browser->driver->getCurrentURL());
            $browser->click('nav[aria-label="Paginación de consultas"] a[href*="page=2"]')
                ->waitForText('Mostrando 16 a 16 de 16 coincidencias');
            $this->selectOption($browser, '[aria-label="Ordenar consultas"]', 'Más antiguas');
            $browser->waitUntil("window.location.search.includes('sort=oldest')")
                ->waitForText('Mostrando 1 a 15 de 16 coincidencias');
            $this->assertStringNotContainsString('page=', $browser->driver->getCurrentURL());
        });
    }

    public function test_the_panel_offers_to_clear_a_filter_without_hiding_the_role_empty_state(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);

        PropertyInquiry::factory()->for($property)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@example.com',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        $agentWithoutInquiries = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent, $agentWithoutInquiries, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.inquiries.index'))
                ->waitForText('Ana Pérez');
            $this->selectOption($browser, '[aria-label="Filtrar por propiedad"]', 'Casa en Miraflores');
            $browser->waitUntil("window.location.search.includes('property=')")
                ->type('#panel-inquiry-search', 'playa')
                ->waitForText('No hay consultas que coincidan')
                ->assertDontSee('Aún no tienes consultas')
                ->assertMissing(self::LIST_SELECTOR)
                ->assertMissing(self::DETAIL_SELECTOR)
                ->assertSeeIn(self::COUNT_SELECTOR, '1')
                ->assertSeeIn(self::RESULTS_SELECTOR, '0 coincidencias de 1 consulta')
                ->assertSeeIn('section[aria-label="Consultas recibidas"] footer', 'Sin coincidencias');

            $browser->press('Limpiar búsqueda y filtros')
                ->waitForText('Ana Pérez')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertInputValue('#panel-inquiry-search', '')
                ->assertSeeIn(self::RESULTS_SELECTOR, '1 consulta en total');

            $this->assertStringNotContainsString(
                'search=',
                $browser->driver->getCurrentURL(),
            );

            $this->assertStringNotContainsString('property=', $browser->driver->getCurrentURL());

            $browser->loginAs($agentWithoutInquiries)
                ->visit(route('panel.inquiries.index', ['property' => $property->id]))
                ->waitForText('Aún no tienes consultas')
                ->assertDontSee('No hay consultas que coincidan')
                ->assertDontSee('Limpiar búsqueda y filtros')
                ->assertSeeIn('section[aria-label="Consultas recibidas"] footer', 'Sin consultas por ahora')
                ->assertMissing(self::DETAIL_SELECTOR)
                ->assertSeeIn(
                    '[aria-label="Filtrar por propiedad"]',
                    "Propiedad #{$property->id}",
                );
        });
    }

    public function test_admin_without_inquiries_sees_the_admin_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin)->visit(route('panel.inquiries.index'))
                ->waitForText('Aún no hay consultas')
                ->assertDontSee('Limpiar búsqueda y filtros')
                ->assertSeeIn('section[aria-label="Consultas recibidas"] footer', 'Sin consultas por ahora');
        });
    }

    public function test_rapid_control_changes_keep_the_pending_selection(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create(['title' => 'Casa pendiente']);
        PropertyInquiry::factory()->for($property)->create(['name' => 'Ana Pérez']);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)->visit(route('panel.inquiries.index'))->waitForText('Ana Pérez');
            $browser->script("window.pendingVisits = []; document.addEventListener('inertia:before', event => { window.pendingVisits.push(event.detail.visit.url.toString()); event.preventDefault(); });");
            $this->selectOption($browser, '[aria-label="Ordenar consultas"]', 'Más antiguas');
            $this->selectOption($browser, '[aria-label="Filtrar por propiedad"]', 'Casa pendiente');
            $visits = $browser->script('return window.pendingVisits');
            $this->assertCount(2, $visits[0]);
            $this->assertStringContainsString('sort=oldest', $visits[0][1]);
            $this->assertStringContainsString("property={$property->id}", $visits[0][1]);
        });
    }

    public function test_clearing_filters_keeps_a_pending_order(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create();
        PropertyInquiry::factory()->for($property)->create(['name' => 'Ana Pérez']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.inquiries.index', ['search' => 'sin resultados']))
                ->waitForText('No hay consultas que coincidan');
            $browser->script("window.pendingVisits = []; document.addEventListener('inertia:before', event => { window.pendingVisits.push(event.detail.visit.url.toString()); if (window.pendingVisits.length === 1) event.preventDefault(); });");
            $this->selectOption($browser, '[aria-label="Ordenar consultas"]', 'Más antiguas');
            $browser->press('Limpiar búsqueda y filtros')->waitForText('Ana Pérez');
            $visits = $browser->script('return window.pendingVisits');
            $this->assertCount(2, $visits[0]);
            $this->assertStringContainsString('sort=oldest', $visits[0][1]);
            $this->assertStringContainsString('sort=oldest', $browser->driver->getCurrentURL());
            $browser->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas');
        });
    }

    public function test_opening_a_url_preserves_all_filters_and_the_visible_page(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create(['title' => 'Casa filtrada']);
        $other = Property::factory()->for($agent)->create(['title' => 'Otra casa']);
        foreach (range(1, 16) as $index) {
            PropertyInquiry::factory()->for($property)->create([
                'name' => "Ana {$index}",
                'created_at' => sprintf('2026-10-28 09:%02d:00', $index),
            ]);
        }
        PropertyInquiry::factory()->for($other)->create(['name' => 'Ana otra propiedad']);
        PropertyInquiry::factory()->for($property)->create(['name' => 'Luis no coincide', 'message' => 'Sin término']);

        $url = route('panel.inquiries.index', ['page' => 2, 'property' => $property->id, 'search' => 'Ana', 'sort' => 'oldest']);
        $this->browse(function (Browser $browser) use ($agent, $url): void {
            $browser->loginAs($agent)->visit($url)
                ->waitForText('Mostrando 16 a 16 de 16 coincidencias')
                ->assertInputValue('#panel-inquiry-search', 'Ana')
                ->assertSeeIn('[aria-label="Filtrar por propiedad"]', 'Casa filtrada')
                ->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertSeeIn(self::LIST_SELECTOR, 'Ana 16')
                ->assertSeeIn(self::DETAIL_SELECTOR, 'Ana 16')
                ->assertDontSee('Ana otra propiedad')
                ->assertDontSee('Luis no coincide');
            $this->assertSame($url, $browser->driver->getCurrentURL());
            $browser->refresh()->waitForTextIn(self::DETAIL_SELECTOR, 'Ana 16')
                ->assertInputValue('#panel-inquiry-search', 'Ana')
                ->assertSeeIn('[aria-label="Filtrar por propiedad"]', 'Casa filtrada')
                ->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas')
                ->assertCount(self::LIST_SELECTOR, 1)
                ->assertSeeIn(self::LIST_SELECTOR, 'Ana 16');
            $this->assertSame($url, $browser->driver->getCurrentURL());
        });
    }

    public function test_clearing_search_keeps_the_selected_order(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create();
        PropertyInquiry::factory()->for($property)->create(['name' => 'Ana Pérez']);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)->visit(route('panel.inquiries.index'))->waitForText('Ana Pérez');
            $this->selectOption($browser, '[aria-label="Ordenar consultas"]', 'Más antiguas');
            $browser->waitUntil("window.location.search.includes('sort=oldest')")
                ->type('#panel-inquiry-search', 'sin coincidencias xyz')
                ->waitForText('No hay consultas que coincidan')
                ->press('Limpiar búsqueda y filtros')
                ->waitForText('Ana Pérez')
                ->assertSeeIn('[aria-label="Ordenar consultas"]', 'Más antiguas');
            $this->assertStringContainsString('sort=oldest', $browser->driver->getCurrentURL());
        });
    }

    public function test_the_detail_falls_back_to_the_first_visible_inquiry_and_disappears_without_matches(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);

        PropertyInquiry::factory()->for($property)->create([
            'name' => 'Ana Reciente',
            'email' => 'ana.reciente@example.com',
            'message' => 'Consulta reciente.',
            'created_at' => '2026-10-28 11:00:00',
        ]);
        PropertyInquiry::factory()->for($property)->create([
            'name' => 'Luis Antiguo',
            'email' => 'luis.antiguo@example.com',
            'message' => 'Consulta antigua.',
            'created_at' => '2026-10-28 10:00:00',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.inquiries.index'))
                ->waitForText('Luis Antiguo')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Ana Reciente');

            $browser->click(self::LIST_SELECTOR.':nth-child(2) button')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Luis Antiguo');

            $browser->type('#panel-inquiry-search', 'reciente')
                ->waitUntilMissingText('Luis Antiguo')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Ana Reciente');

            $browser->keys('#panel-inquiry-search', [WebDriverKeys::CONTROL, 'a'], WebDriverKeys::BACKSPACE)
                ->waitForText('Luis Antiguo')
                ->waitForTextIn(self::DETAIL_SELECTOR, 'Ana Reciente');

            $browser->type('#panel-inquiry-search', 'playa')
                ->waitForText('No hay consultas que coincidan')
                ->assertMissing(self::DETAIL_SELECTOR);
        });
    }

    public function test_the_slash_key_focuses_the_search_only_outside_other_fields(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);

        PropertyInquiry::factory()->for($property)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@example.com',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.inquiries.index'))
                ->waitForText('Ana Pérez')
                ->click('h1');

            $browser->driver->switchTo()->activeElement()->sendKeys('/');

            $browser->assertFocused('#panel-inquiry-search')
                ->keys('#panel-inquiry-search', '/')
                ->assertInputValue('#panel-inquiry-search', '/');
        });
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
