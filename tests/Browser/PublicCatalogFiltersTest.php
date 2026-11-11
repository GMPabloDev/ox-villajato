<?php

namespace Tests\Browser;

use App\Models\Property;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PublicCatalogFiltersTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const CARD_TITLE_SELECTOR = 'h3';

    public function test_unavailable_applied_district_remains_visible_without_becoming_an_option(): void
    {
        Property::factory()->create(['status' => 'sold', 'district_code' => '150122']);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/?district_code=150122')
                ->waitFor('[data-filter-summary="district"]')
                ->assertSeeIn('[data-filter-summary="district"]', 'Miraflores, Lima · Lima');
            $this->openFilterMenu($browser, 'district');
            $browser->assertMissing('input[name="property-district"][value="150122"]');
        });
    }

    public function test_the_district_menu_labels_homonyms_with_province_and_department_and_keeps_the_url(): void
    {
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en Santiago de Cusco',
            'district_code' => '080106',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en Santiago de Ica',
            'district_code' => '110111',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/')
                ->waitForText('Casa en Santiago de Ica');

            $this->openFilterMenu($browser, 'district');

            $browser->assertSeeIn('[data-filter-menu="district"] fieldset', 'Santiago, Cusco · Cusco')
                ->assertSeeIn('[data-filter-menu="district"] fieldset', 'Santiago, Ica · Ica');

            $this->selectDistrict($browser, '110111');

            $browser->waitUntil("window.location.search.includes('district_code=110111')")
                ->waitUntilMissingText('Casa en Santiago de Cusco')
                ->assertSee('Casa en Santiago de Ica')
                ->assertSeeIn('[data-filter-summary="district"]', 'Santiago, Ica · Ica');

            $browser->refresh()
                ->waitForText('Casa en Santiago de Ica')
                ->assertDontSee('Casa en Santiago de Cusco')
                ->assertSeeIn('[data-filter-summary="district"]', 'Santiago, Ica · Ica');
        });
    }

    public function test_the_district_filter_combines_with_operation_and_type(): void
    {
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en venta en Barranco',
            'district_code' => '150104',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler en Barranco',
            'district_code' => '150104',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'apartment',
            'title' => 'Departamento en venta en Barranco',
            'district_code' => '150104',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en venta en Miraflores',
            'district_code' => '150122',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/')
                ->waitForText('Casa en venta en Miraflores');

            $this->selectDistrict($browser, '150104');

            $browser->waitUntil("window.location.search.includes('district_code=150104')")
                ->waitUntilMissingText('Casa en venta en Miraflores')
                ->assertCount(self::CARD_TITLE_SELECTOR, 3);

            $this->selectType($browser, 'house');

            $browser->waitUntil("window.location.search.includes('type=house')")
                ->waitUntilMissingText('Departamento en venta en Barranco')
                ->assertSee('Casa en venta en Barranco');

            $browser->click('a[href*="operation=sale"]')
                ->waitUntil("window.location.search.includes('operation=sale')")
                ->waitUntilMissingText('Casa en alquiler en Barranco')
                ->assertSee('Casa en venta en Barranco')
                ->assertCount(self::CARD_TITLE_SELECTOR, 1);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('district_code=150104', $url);
            $this->assertStringContainsString('type=house', $url);
            $this->assertStringContainsString('operation=sale', $url);
        });
    }

    public function test_an_empty_result_offers_to_clear_the_filters_and_restores_the_catalog(): void
    {
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en venta en Barranco',
            'district_code' => '150104',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit(route('home', ['operation' => 'rent', 'district_code' => '150104']))
                ->waitForText('No encontramos propiedades con esos filtros')
                ->assertSee('Limpiar filtros');

            $browser->press('Limpiar filtros')
                ->waitForText('Casa en venta en Barranco')
                ->waitUntilMissingText('No encontramos propiedades con esos filtros');

            $url = $browser->driver->getCurrentURL();
            $this->assertStringNotContainsString('operation=', $url);
            $this->assertStringNotContainsString('district_code=', $url);
        });
    }

    private function selectDistrict(Browser $browser, string $code): void
    {
        $this->openFilterMenu($browser, 'district');
        $this->selectRadio($browser, 'property-district', $code);
    }

    private function selectType(Browser $browser, string $value): void
    {
        $this->openFilterMenu($browser, 'type');
        $this->selectRadio($browser, 'property-type', $value);
    }

    /**
     * Open a filter menu unless it is already open.
     */
    private function openFilterMenu(Browser $browser, string $id): void
    {
        $isOpen = $browser->script(
            "return document.querySelector('[data-filter-menu=\"{$id}\"]').open;",
        )[0] ?? false;

        if ($isOpen) {
            return;
        }

        $browser->click("[data-filter-summary=\"{$id}\"]")
            ->waitUntil("document.querySelector('[data-filter-menu=\"{$id}\"]').open === true");
    }

    /**
     * Click the visible label of a radio of an open filter menu.
     */
    private function selectRadio(Browser $browser, string $name, string $value): void
    {
        $browser->driver
            ->findElement(WebDriverBy::xpath(
                "//input[@name='{$name}'][@value='{$value}']/following-sibling::span",
            ))
            ->click();
    }
}
