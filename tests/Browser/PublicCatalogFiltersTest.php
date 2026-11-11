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

    public function test_the_location_filters_cascade_and_narrow_the_catalog_at_each_level(): void
    {
        Property::factory()->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Barranco',
            'district_code' => '150104',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Asia',
            'district_code' => '150502',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Yanahuara',
            'district_code' => '040126',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/')
                ->waitForText('Casa en Yanahuara')
                ->assertCount(self::CARD_TITLE_SELECTOR, 4);

            $this->openFilterMenu($browser, 'department');
            $browser->assertSeeIn('[data-filter-menu="department"] fieldset', 'Lima')
                ->assertSeeIn('[data-filter-menu="department"] fieldset', 'Arequipa')
                ->assertCount('input[name="property-department"]', 3);

            $this->selectDepartment($browser, '15');

            $browser->waitUntil("window.location.search.includes('department_code=15')")
                ->waitUntilMissingText('Casa en Yanahuara')
                ->assertCount(self::CARD_TITLE_SELECTOR, 3)
                ->assertSeeIn('[data-filter-summary="department"]', 'Lima')
                ->assertSeeIn('[data-filter-summary="province"]', 'Todas')
                ->assertSeeIn('[data-filter-summary="district"]', 'Todos');

            $this->openFilterMenu($browser, 'province');
            $browser->assertSeeIn('[data-filter-menu="province"] fieldset', 'Cañete')
                ->assertDontSeeIn('[data-filter-menu="province"] fieldset', 'Arequipa')
                ->assertCount('input[name="property-province"]', 3);

            $this->openFilterMenu($browser, 'district');
            $browser->assertSeeIn('[data-filter-menu="district"] fieldset', 'Elige una provincia para ver sus distritos.')
                ->assertCount('input[name="property-district"]', 1);

            $this->selectProvince($browser, '1501');

            $browser->waitUntil("window.location.search.includes('province_code=1501')")
                ->waitUntilMissingText('Casa en Asia')
                ->assertCount(self::CARD_TITLE_SELECTOR, 2)
                ->assertSeeIn('[data-filter-summary="province"]', 'Lima');

            $this->openFilterMenu($browser, 'district');
            $browser->assertSeeIn('[data-filter-menu="district"] fieldset', 'Miraflores, Lima · Lima')
                ->assertSeeIn('[data-filter-menu="district"] fieldset', 'Barranco, Lima · Lima')
                ->assertDontSeeIn('[data-filter-menu="district"] fieldset', 'Asia');

            $this->selectDistrict($browser, '150122');

            $browser->waitUntil("window.location.search.includes('district_code=150122')")
                ->waitUntilMissingText('Casa en Barranco')
                ->assertCount(self::CARD_TITLE_SELECTOR, 1)
                ->assertSee('Casa en Miraflores')
                ->assertSeeIn('[data-filter-summary="district"]', 'Miraflores, Lima · Lima');
        });
    }

    public function test_changing_or_clearing_a_parent_location_drops_the_levels_below_it(): void
    {
        Property::factory()->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Yanahuara',
            'district_code' => '040126',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit(route('home', [
                'department_code' => '15',
                'province_code' => '1501',
                'district_code' => '150122',
            ]))
                ->waitForText('Casa en Miraflores')
                ->assertDontSee('Casa en Yanahuara')
                ->assertSeeIn('[data-filter-summary="district"]', 'Miraflores, Lima · Lima');

            $this->selectDepartment($browser, '04');

            $browser->waitUntil("window.location.search.includes('department_code=04')")
                ->waitUntil("!window.location.search.includes('province_code=')")
                ->waitUntilMissingText('Casa en Miraflores')
                ->assertSee('Casa en Yanahuara')
                ->assertSeeIn('[data-filter-summary="department"]', 'Arequipa')
                ->assertSeeIn('[data-filter-summary="province"]', 'Todas')
                ->assertSeeIn('[data-filter-summary="district"]', 'Todos');

            $url = $browser->driver->getCurrentURL();
            $this->assertStringNotContainsString('province_code=', $url);
            $this->assertStringNotContainsString('district_code=', $url);

            $this->openFilterMenu($browser, 'province');
            $browser->assertSeeIn('[data-filter-menu="province"] fieldset', 'Arequipa')
                ->assertDontSeeIn('[data-filter-menu="province"] fieldset', 'Lima');

            $this->selectDepartment($browser, 'all');

            $browser->waitUntil("window.location.search === ''")
                ->waitForText('Casa en Miraflores')
                ->assertSee('Casa en Yanahuara')
                ->assertCount(self::CARD_TITLE_SELECTOR, 2)
                ->assertSeeIn('[data-filter-summary="department"]', 'Todos')
                ->assertSeeIn('[data-filter-summary="province"]', 'Todas')
                ->assertSeeIn('[data-filter-summary="district"]', 'Todos');
        });
    }

    public function test_operation_type_and_reload_keep_the_location_selection(): void
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

            $this->selectDepartment($browser, '15');
            $this->selectProvince($browser, '1501');
            $this->selectDistrict($browser, '150104');

            $browser->waitUntil("window.location.search.includes('district_code=150104')")
                ->waitUntilMissingText('Casa en venta en Miraflores')
                ->assertCount(self::CARD_TITLE_SELECTOR, 3);

            $this->selectType($browser, 'house');

            $browser->waitUntil("window.location.search.includes('type=house')")
                ->waitUntilMissingText('Departamento en venta en Barranco')
                ->assertSeeIn('[data-filter-summary="district"]', 'Barranco, Lima · Lima');

            $browser->click('a[href*="operation=sale"]')
                ->waitUntil("window.location.search.includes('operation=sale')")
                ->waitUntilMissingText('Casa en alquiler en Barranco')
                ->assertSee('Casa en venta en Barranco')
                ->assertCount(self::CARD_TITLE_SELECTOR, 1);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('department_code=15', $url);
            $this->assertStringContainsString('province_code=1501', $url);
            $this->assertStringContainsString('district_code=150104', $url);
            $this->assertStringContainsString('type=house', $url);
            $this->assertStringContainsString('operation=sale', $url);

            $browser->refresh()
                ->waitForText('Casa en venta en Barranco')
                ->assertDontSee('Casa en alquiler en Barranco')
                ->assertSeeIn('[data-filter-summary="department"]', 'Lima')
                ->assertSeeIn('[data-filter-summary="province"]', 'Lima')
                ->assertSeeIn('[data-filter-summary="district"]', 'Barranco, Lima · Lima');
        });
    }

    public function test_a_shared_url_restores_the_three_location_selections(): void
    {
        Property::factory()->create([
            'title' => 'Casa en Barranco',
            'district_code' => '150104',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit(route('home', [
                'department_code' => '15',
                'province_code' => '1501',
                'district_code' => '150104',
            ]))
                ->waitForText('Casa en Barranco')
                ->assertDontSee('Casa en Miraflores')
                ->assertSeeIn('[data-filter-summary="department"]', 'Lima')
                ->assertSeeIn('[data-filter-summary="province"]', 'Lima')
                ->assertSeeIn('[data-filter-summary="district"]', 'Barranco, Lima · Lima');

            $this->openFilterMenu($browser, 'department');
            $browser->assertChecked('input[name="property-department"][value="15"]')
                ->assertNotChecked('input[name="property-department"][value="all"]');

            $this->openFilterMenu($browser, 'province');
            $browser->assertChecked('input[name="property-province"][value="1501"]');

            $this->openFilterMenu($browser, 'district');
            $browser->assertChecked('input[name="property-district"][value="150104"]');
        });
    }

    public function test_an_existing_district_link_fills_its_province_and_department_and_keeps_homonyms_apart(): void
    {
        Property::factory()->create([
            'title' => 'Casa en Santiago de Cusco',
            'district_code' => '080106',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Santiago de Ica',
            'district_code' => '110111',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/?district_code=110111')
                ->waitForText('Casa en Santiago de Ica')
                ->assertDontSee('Casa en Santiago de Cusco')
                ->assertSeeIn('[data-filter-summary="department"]', 'Ica')
                ->assertSeeIn('[data-filter-summary="province"]', 'Ica')
                ->assertSeeIn('[data-filter-summary="district"]', 'Santiago, Ica · Ica');

            $this->openFilterMenu($browser, 'department');
            $browser->assertChecked('input[name="property-department"][value="11"]');

            $this->openFilterMenu($browser, 'district');
            $browser->assertSeeIn('[data-filter-menu="district"] fieldset', 'Santiago, Ica · Ica')
                ->assertDontSeeIn('[data-filter-menu="district"] fieldset', 'Santiago, Cusco · Cusco')
                ->assertChecked('input[name="property-district"][value="110111"]');
        });
    }

    public function test_an_applied_location_without_available_properties_stays_visible_and_clearing_restores_the_catalog(): void
    {
        Property::factory()->create([
            'status' => 'sold',
            'title' => 'Casa vendida en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'title' => 'Casa disponible en Yanahuara',
            'district_code' => '040126',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/?district_code=150122')
                ->waitForText('No encontramos propiedades con esos filtros')
                ->assertDontSee('Casa vendida en Miraflores')
                ->assertSeeIn('[data-filter-summary="department"]', 'Lima')
                ->assertSeeIn('[data-filter-summary="province"]', 'Lima')
                ->assertSeeIn('[data-filter-summary="district"]', 'Miraflores, Lima · Lima');

            $this->openFilterMenu($browser, 'district');
            $browser->assertSeeIn('[data-filter-menu="district"] fieldset', 'Miraflores, Lima · Lima (filtro actual)')
                ->assertChecked('input[name="property-district"][value="150122"]')
                ->assertDisabled('input[name="property-district"][value="150122"]');

            $browser->press('Limpiar filtros')
                ->waitForText('Casa disponible en Yanahuara')
                ->waitUntilMissingText('No encontramos propiedades con esos filtros')
                ->assertSeeIn('[data-filter-summary="department"]', 'Todos')
                ->assertSeeIn('[data-filter-summary="province"]', 'Todas')
                ->assertSeeIn('[data-filter-summary="district"]', 'Todos');

            $url = $browser->driver->getCurrentURL();
            $this->assertStringNotContainsString('department_code=', $url);
            $this->assertStringNotContainsString('province_code=', $url);
            $this->assertStringNotContainsString('district_code=', $url);
        });
    }

    private function selectDepartment(Browser $browser, string $code): void
    {
        $this->openFilterMenu($browser, 'department');
        $this->selectRadio($browser, 'property-department', $code);
    }

    private function selectProvince(Browser $browser, string $code): void
    {
        $this->openFilterMenu($browser, 'province');
        $this->selectRadio($browser, 'property-province', $code);
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
