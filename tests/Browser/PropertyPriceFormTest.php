<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\User;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PropertyPriceFormTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_publishing_a_rent_labels_the_price_as_monthly_and_keeps_the_amount_and_currency_when_the_operation_changes(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->press('Publicar propiedad')
                ->waitForText('Añade la información para crear tu anuncio.')
                ->assertSeeIn('label[for="price"]', 'Precio')
                ->assertDontSeeIn('label[for="price"]', 'mensual')
                ->type('#title', 'Departamento en alquiler')
                ->type('#description', 'Departamento luminoso con vista al parque.')
                ->type('#price', '1500')
                ->type('#bedrooms', '2')
                ->type('#bathrooms', '1')
                ->type('#area', '80')
                ->click('input[name="has_garage"][value="0"] + span')
                ->click('input[name="is_furnished"][value="1"] + span')
                ->attach('#property-photos', base_path('database/seeders/assets/properties/casa.jpeg'));

            $this->selectCurrency($browser, 'US$ USD');

            $browser->assertInputValue('input[name="currency"]', 'USD')
                ->click('input[name="operation"][value="rent"] + span')
                ->assertRadioSelected('operation', 'rent')
                ->assertSeeIn('label[for="price"]', 'Precio mensual')
                ->assertInputValue('#price', '1500')
                ->assertInputValue('input[name="currency"]', 'USD')
                ->click('input[name="operation"][value="sale"] + span')
                ->assertRadioSelected('operation', 'sale')
                ->assertSeeIn('label[for="price"]', 'Precio')
                ->assertDontSeeIn('label[for="price"]', 'mensual')
                ->assertInputValue('#price', '1500')
                ->assertInputValue('input[name="currency"]', 'USD')
                ->click('input[name="operation"][value="rent"] + span')
                ->assertSeeIn('label[for="price"]', 'Precio mensual');

            $this->selectLocation($browser, '#property-department', 'Lima');
            $this->selectLocation($browser, '#property-province', 'Lima');
            $this->selectLocation($browser, '#property-district', 'Miraflores');

            $browser->press('form button[type="submit"]')
                ->waitForText('Departamento en alquiler');
        });

        $property = Property::sole();

        $this->assertSame('rent', $property->operation->value);
        $this->assertSame('1500.00', $property->price);
        $this->assertSame('USD', $property->currency->value);
    }

    public function test_the_edit_form_preloads_a_rent_price_and_keeps_it_when_the_operation_changes_to_a_sale(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Departamento en alquiler amoblado',
            'operation' => 'rent',
            'price' => 1500,
            'currency' => 'USD',
            'status' => 'available',
        ]);

        $property->photos()->create(['path' => 'properties/cover.jpg', 'position' => 1]);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Departamento en alquiler amoblado')
                ->press('Editar propiedad')
                ->waitForText('Actualiza la información y las fotos del anuncio.')
                ->waitForTextIn('#property-district', 'Lima')
                ->assertRadioSelected('operation', 'rent')
                ->assertSeeIn('label[for="price"]', 'Precio mensual')
                ->assertInputValue('#price', '1500.00')
                ->assertInputValue('input[name="currency"]', 'USD')
                ->click('input[name="operation"][value="sale"] + span')
                ->assertRadioSelected('operation', 'sale')
                ->assertSeeIn('label[for="price"]', 'Precio')
                ->assertDontSeeIn('label[for="price"]', 'mensual')
                ->assertInputValue('#price', '1500.00')
                ->assertInputValue('input[name="currency"]', 'USD')
                ->press('form button[type="submit"]')
                ->waitForLocation(
                    parse_url(route('properties.show', $property), PHP_URL_PATH),
                )
                ->waitForText('Departamento en alquiler amoblado');
        });

        $property->refresh();

        $this->assertSame('sale', $property->operation->value);
        $this->assertSame('1500.00', $property->price);
        $this->assertSame('USD', $property->currency->value);
    }

    public function test_the_edit_form_preloads_a_sale_price_and_keeps_it_when_the_operation_changes_to_a_rent(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa frente al parque',
            'operation' => 'sale',
            'price' => 250000,
            'currency' => 'PEN',
            'status' => 'available',
        ]);

        $property->photos()->create(['path' => 'properties/cover.jpg', 'position' => 1]);

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa frente al parque')
                ->press('Editar propiedad')
                ->waitForText('Actualiza la información y las fotos del anuncio.')
                ->waitForTextIn('#property-district', 'Lima')
                ->assertRadioSelected('operation', 'sale')
                ->assertSeeIn('label[for="price"]', 'Precio')
                ->assertDontSeeIn('label[for="price"]', 'mensual')
                ->assertInputValue('#price', '250000.00')
                ->assertInputValue('input[name="currency"]', 'PEN')
                ->click('input[name="operation"][value="rent"] + span')
                ->assertRadioSelected('operation', 'rent')
                ->assertSeeIn('label[for="price"]', 'Precio mensual')
                ->assertInputValue('#price', '250000.00')
                ->assertInputValue('input[name="currency"]', 'PEN')
                ->press('form button[type="submit"]')
                ->waitForLocation(
                    parse_url(route('properties.show', $property), PHP_URL_PATH),
                )
                ->waitForText('Casa frente al parque');
        });

        $property->refresh();

        $this->assertSame('rent', $property->operation->value);
        $this->assertSame('250000.00', $property->price);
        $this->assertSame('PEN', $property->currency->value);
    }

    private function selectCurrency(Browser $browser, string $optionLabel): void
    {
        $browser->click('[aria-label="Moneda"]')
            ->waitFor('[role="listbox"]');

        $browser->driver
            ->findElement(WebDriverBy::xpath(
                "//div[@role='option'][normalize-space(.)='{$optionLabel}']",
            ))
            ->click();

        $browser->waitUntilMissing('[role="listbox"]');
    }

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
