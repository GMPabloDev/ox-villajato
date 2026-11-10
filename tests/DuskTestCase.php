<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    public function createApplication()
    {
        DuskIsolation::databasePath();
        $app = parent::createApplication();
        $identity = DuskIsolation::verifyConnection($app);
        $url = getenv('DUSK_ISOLATED_URL');
        $token = getenv('DUSK_ISOLATED_TOKEN');

        if (! is_string($url) || ! preg_match('~^http://127\.0\.0\.1:[0-9]+$~', $url) ||
            $app['config']->get('app.url') !== $url ||
            ! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/', $token) ||
            @file_get_contents($url.'/_dusk_isolation_probe?token='.$token, false, stream_context_create(['http' => ['timeout' => 2]])) !== $token.':'.$identity) {
            throw new \RuntimeException('Dusk refused an unverified application server.');
        }

        return $app;
    }

    #[BeforeClass]
    public static function prepare(): void
    {
        DuskIsolation::databasePath();
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
