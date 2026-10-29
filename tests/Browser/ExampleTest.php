<?php

namespace Tests\Browser;

use App\Models\Property;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_guest_browses_the_catalog_and_opens_a_property(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'price' => 250000,
            'currency' => 'PEN',
            'city' => 'Lima',
            'zone' => 'Miraflores',
        ]);

        $url = route('properties.show', $property);

        $this->browse(function (Browser $browser) use ($property, $url): void {
            $browser->visit('/')
                ->waitForText($property->title)
                ->assertSee('Casas y departamentos en venta y alquiler.')
                ->assertSee('Lima · Miraflores')
                ->assertSee('S/ 250,000')
                ->click('a[href="'.$url.'"]')
                ->waitForLocation(parse_url($url, PHP_URL_PATH))
                ->assertSee($property->title)
                ->assertSee('Consulta esta propiedad');
        });
    }
}
