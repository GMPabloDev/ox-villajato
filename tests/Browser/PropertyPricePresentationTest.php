<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PropertyPricePresentationTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const CATALOG_CARD = '#propiedades article:nth-of-type';

    private const PANEL_ITEM = 'ul[aria-label="Propiedades"] > li:nth-child';

    private const ARCHIVED_CARD = '[data-test="archived-properties"] > li:nth-child';

    public function test_the_catalog_and_the_public_page_mark_only_rent_prices_as_monthly(): void
    {
        $agent = User::factory()->agent()->create();

        $sale = Property::factory()->for($agent)->create([
            'title' => 'Casa en venta en Miraflores',
            'operation' => 'sale',
            'price' => 250000,
            'currency' => 'PEN',
        ]);
        $rent = Property::factory()->for($agent)->create([
            'title' => 'Departamento en alquiler en Barranco',
            'operation' => 'rent',
            'price' => 1500,
            'currency' => 'PEN',
        ]);
        $dollarRent = Property::factory()->for($agent)->create([
            'title' => 'Departamento en alquiler en dólares',
            'operation' => 'rent',
            'price' => 900,
            'currency' => 'USD',
        ]);

        $this->browse(function (Browser $browser) use ($dollarRent, $rent, $sale): void {
            $browser->resize(1440, 900)
                ->visit(route('home'))
                ->waitForText($dollarRent->title)
                // El catálogo muestra primero la propiedad más reciente.
                ->assertSeeIn(self::CATALOG_CARD.'(1) [data-test="gallery-price"]', 'US$ 900')
                ->assertSeeIn(self::CATALOG_CARD.'(1) [data-test="gallery-price"]', '/ mes')
                ->assertSeeIn(self::CATALOG_CARD.'(2) [data-test="gallery-price"]', 'S/ 1,500')
                ->assertSeeIn(self::CATALOG_CARD.'(2) [data-test="gallery-price"]', '/ mes')
                ->assertSeeIn(self::CATALOG_CARD.'(3) [data-test="gallery-price"]', 'S/ 250,000')
                ->assertDontSeeIn(self::CATALOG_CARD.'(3) [data-test="gallery-price"]', '/ mes')
                ->visit(route('properties.show', $rent))
                ->assertSeeIn('[data-test="property-price"]', 'S/ 1,500')
                ->assertSeeIn('[data-test="property-price"]', '/ mes')
                ->visit(route('properties.show', $sale))
                ->assertSeeIn('[data-test="property-price"]', 'S/ 250,000')
                ->assertDontSeeIn('[data-test="property-price"]', '/ mes')
                ->resize(390, 844)
                ->visit(route('properties.show', $rent))
                ->assertVisible('[data-test="property-mobile-price"]')
                ->assertSeeIn('[data-test="property-mobile-price"]', 'S/ 1,500')
                ->assertSeeIn('[data-test="property-mobile-price"]', '/ mes')
                ->visit(route('properties.show', $sale))
                ->assertVisible('[data-test="property-mobile-price"]')
                ->assertSeeIn('[data-test="property-mobile-price"]', 'S/ 250,000')
                ->assertDontSeeIn('[data-test="property-mobile-price"]', '/ mes');
        });
    }

    public function test_the_panel_list_detail_and_archived_cards_mark_only_rent_prices_as_monthly(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en venta en Miraflores',
            'operation' => 'sale',
            'price' => 250000,
            'currency' => 'PEN',
            'status' => 'available',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Departamento en alquiler en Barranco',
            'operation' => 'rent',
            'price' => 1500,
            'currency' => 'PEN',
            'status' => 'available',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa archivada en venta',
            'operation' => 'sale',
            'price' => 180000,
            'currency' => 'PEN',
            'status' => 'archived',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Departamento archivado en alquiler',
            'operation' => 'rent',
            'price' => 1200,
            'currency' => 'USD',
            'status' => 'archived',
        ]);

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->resize(1440, 900)
                ->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Departamento en alquiler en Barranco')
                // El listado del panel muestra primero la propiedad más reciente.
                ->assertSeeIn(self::PANEL_ITEM.'(1) [data-test="list-price"]', 'S/ 1,500')
                ->assertSeeIn(self::PANEL_ITEM.'(1) [data-test="list-price"]', '/ mes')
                ->assertSeeIn(self::PANEL_ITEM.'(2) [data-test="list-price"]', 'S/ 250,000')
                ->assertDontSeeIn(self::PANEL_ITEM.'(2) [data-test="list-price"]', '/ mes')
                ->assertSeeIn('[data-test="detail-price"]', 'S/ 1,500')
                ->assertSeeIn('[data-test="detail-price"]', '/ mes');

            $this->assertSame('S/ 1,500 / mes', $browser->text('[data-test="detail-price"]'));
            $this->assertStringNotContainsString('PEN', $browser->text(self::PANEL_ITEM.'(1) [data-test="list-price"]'));
            $this->assertStringNotContainsString('PEN', $browser->text(self::PANEL_ITEM.'(2) [data-test="list-price"]'));

            $browser->click(self::PANEL_ITEM.'(2) button')
                ->waitForTextIn('[data-test="detail-price"]', '250,000')
                ->assertSeeIn('[data-test="detail-price"]', 'S/ 250,000')
                ->assertDontSeeIn('[data-test="detail-price"]', '/ mes');

            $this->assertSame('S/ 250,000', $browser->text('[data-test="detail-price"]'));

            $browser->visit(route('panel.properties.archived'))
                ->waitForText('Departamento archivado en alquiler')
                // La lista de archivadas también muestra primero la más reciente.
                ->assertSeeIn(self::ARCHIVED_CARD.'(1) [data-test="archived-price"]', 'US$ 1,200')
                ->assertSeeIn(self::ARCHIVED_CARD.'(1) [data-test="archived-price"]', '/ mes')
                ->assertSeeIn(self::ARCHIVED_CARD.'(2) [data-test="archived-price"]', 'S/ 180,000')
                ->assertDontSeeIn(self::ARCHIVED_CARD.'(2) [data-test="archived-price"]', '/ mes');

            $this->assertStringNotContainsString('USD', $browser->text(self::ARCHIVED_CARD.'(1) [data-test="archived-price"]'));
            $this->assertStringNotContainsString('PEN', $browser->text(self::ARCHIVED_CARD.'(2) [data-test="archived-price"]'));
        });
    }
}
