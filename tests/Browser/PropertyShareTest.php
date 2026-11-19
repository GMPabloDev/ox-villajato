<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\User;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PropertyShareTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_share_from_desktop_and_mobile(): void
    {
        $property = Property::factory()->for(User::factory()->agent())->create(['title' => 'Casa & jardín #1']);
        $url = route('properties.show', $property);

        $this->browse(function (Browser $browser) use ($property, $url): void {
            foreach ([[1440, 900], [390, 844]] as [$width, $height]) {
                $browser->resize($width, $height)
                    ->visit($url.'?origen=busqueda#consulta')
                    ->waitForText($property->title)
                    ->click('button[aria-haspopup="dialog"]')
                    ->waitForText('Copiar enlace')
                    ->assertSeeIn('[role="dialog"]', 'Compartir propiedad');

                $links = $browser->script("return Array.from(document.querySelectorAll('[role=dialog] a')).map(a => ({text: a.textContent.trim(), href: a.getAttribute('href'), target: a.target, rel: a.rel}));")[0];
                $this->assertCount(4, $links);
                parse_str(parse_url($links[0]['href'], PHP_URL_QUERY), $mail);
                $this->assertSame(['subject' => $property->title, 'body' => $url], $mail);
                parse_str(parse_url($links[1]['href'], PHP_URL_QUERY), $whatsapp);
                $this->assertSame($property->title."\n".$url, $whatsapp['text']);
                parse_str(parse_url($links[2]['href'], PHP_URL_QUERY), $facebook);
                $this->assertSame($url, $facebook['u']);
                parse_str(parse_url($links[3]['href'], PHP_URL_QUERY), $x);
                $this->assertSame(['text' => $property->title, 'url' => $url], $x);
                $this->assertStringStartsWith('https://wa.me/', $links[1]['href']);
                $this->assertStringStartsWith('https://www.facebook.com/sharer/', $links[2]['href']);
                $this->assertStringStartsWith('https://twitter.com/intent/tweet', $links[3]['href']);
                foreach (array_slice($links, 1) as $link) {
                    $this->assertSame('_blank', $link['target']);
                    $this->assertStringContainsString('noopener', $link['rel']);
                }
                $browser->keys('a[href^="mailto:"]', WebDriverKeys::ESCAPE)
                    ->waitUntilMissing('[role="dialog"]')
                    ->assertVisible('#consulta');
                $this->assertSame('Compartir', $browser->script('return document.activeElement.textContent.trim();')[0]);
            }
        });
    }

    public function test_copy_success_failure_and_keyboard_access(): void
    {
        $property = Property::factory()->for(User::factory()->agent())->create();
        $url = route('properties.show', $property);

        $this->browse(function (Browser $browser) use ($url): void {
            $browser->visit($url)->waitForText('Compartir');
            $browser->script("Object.defineProperty(navigator, 'clipboard', {configurable: true, value: {writeText: text => { window.copiedLink = text; return Promise.resolve(); }}});");
            $browser->keys('button[aria-haspopup="dialog"]', WebDriverKeys::ENTER)
                ->waitForText('Copiar enlace');
            $browser->keys('a[href^="mailto:"]', WebDriverKeys::TAB);
            $this->assertSame('WhatsApp', $browser->script('return document.activeElement.textContent.trim();')[0]);
            $browser->press('Copiar enlace')
                ->waitForText('Enlace copiado.');
            $this->assertSame($url, $browser->script('return window.copiedLink;')[0]);
            $browser->script("Object.defineProperty(navigator, 'clipboard', {configurable: true, value: {writeText: () => Promise.reject(new Error('denied'))}});");
            $browser->press('Copiar enlace')->waitForText('No se pudo copiar el enlace.')
                ->assertValue('input[aria-label="Enlace de la propiedad"]', $url);
            $browser->click('input[aria-label="Enlace de la propiedad"]');
            $this->assertSame($url, $browser->script('return window.getSelection().toString();')[0]);
            $browser->keys('input[aria-label="Enlace de la propiedad"]', WebDriverKeys::ESCAPE)
                ->waitUntilMissing('[role="dialog"]');
            $browser->script("Object.defineProperty(navigator, 'clipboard', {configurable: true, value: undefined});");
            $browser->keys('button[aria-haspopup="dialog"]', WebDriverKeys::ENTER)
                ->waitForText('Copiar enlace')->press('Copiar enlace')
                ->waitForText('No se pudo copiar el enlace.')
                ->assertValue('input[aria-label="Enlace de la propiedad"]', $url);
        });
    }
}
