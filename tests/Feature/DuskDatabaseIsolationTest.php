<?php

namespace Tests\Feature;

use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\DuskArtifacts;

class DuskDatabaseIsolationTest extends TestCase
{
    public function test_failure_artifacts_are_preserved_in_unique_directories(): void
    {
        $root = sys_get_temp_dir().'/dusk-artifacts-test-'.bin2hex(random_bytes(16));
        mkdir($root, 0700);
        foreach (['screenshots', 'console', 'source'] as $kind) {
            mkdir($root.'/project/tests/Browser/'.$kind, 0700, true);
            file_put_contents($root.'/project/tests/Browser/'.$kind.'/failure.txt', $kind);
        }

        try {
            $first = DuskArtifacts::preserve($root.'/project', $root);
            $second = DuskArtifacts::preserve($root.'/project', $root);
            $this->assertNotSame($first, $second);
            foreach (['screenshots', 'console', 'source'] as $kind) {
                $this->assertSame($kind, file_get_contents($first.'/'.$kind.'/failure.txt'));
                $this->assertSame($kind, file_get_contents($second.'/'.$kind.'/failure.txt'));
            }
        } finally {
            (new Filesystem)->remove($root);
        }
    }

    public function test_direct_dusk_command_does_not_replace_environment_or_delete_existing_artifacts(): void
    {
        $root = sys_get_temp_dir().'/dusk-command-test-'.bin2hex(random_bytes(16));
        mkdir($root, 0700);
        copy(dirname(__DIR__, 2).'/artisan', $root.'/artisan');
        copy(dirname(__DIR__, 2).'/composer.json', $root.'/composer.json');
        mkdir($root.'/bootstrap/cache', 0700, true);
        copy(dirname(__DIR__, 2).'/bootstrap/app.php', $root.'/bootstrap/app.php');
        copy(dirname(__DIR__, 2).'/bootstrap/providers.php', $root.'/bootstrap/providers.php');
        foreach (['config', 'routes', 'vendor', 'app'] as $directory) {
            symlink(dirname(__DIR__, 2).'/'.$directory, $root.'/'.$directory);
        }
        mkdir($root.'/tests/Browser/screenshots', 0700, true);
        mkdir($root.'/storage/logs', 0700, true);
        file_put_contents($root.'/.env', "APP_ENV=local\nAPP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=\n");
        file_put_contents($root.'/.env.dusk.local', "APP_ENV=dusk.local\nAPP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=\n");
        file_put_contents($root.'/.env.backup', 'existing backup');
        file_put_contents($root.'/tests/Browser/screenshots/failure-existing.png', 'existing artifact');

        try {
            foreach ([['dusk'], ['--env', 'local', 'dusk'], ['dusk:fails']] as $arguments) {
                $process = new Process([PHP_BINARY, 'artisan', ...$arguments, '--without-tty'], $root, [
                    'APP_ENV' => 'local', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '',
                ]);
                $process->setTimeout(30);
                $process->run();

                $this->assertSame('existing backup', @file_get_contents($root.'/.env.backup'));
                $this->assertSame('existing artifact', @file_get_contents($root.'/tests/Browser/screenshots/failure-existing.png'));
                $this->assertNotSame(0, $process->getExitCode());
                $this->assertStringContainsString('Use composer dusk', $process->getErrorOutput().$process->getOutput());
            }

            $other = new Process([PHP_BINARY, 'artisan', 'du', '--without-tty'], $root, [
                'APP_ENV' => 'local', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '',
            ]);
            $other->setTimeout(30);
            $other->run();

            $this->assertStringNotContainsString('Use composer dusk', $other->getErrorOutput().$other->getOutput());
        } finally {
            (new Filesystem)->remove($root);
        }
    }

    public function test_guard_rejects_symlinks_hardlinks_and_connection_url_overrides(): void
    {
        $root = sys_get_temp_dir().'/ox-dusk-'.bin2hex(random_bytes(20));
        mkdir($root, 0700);
        mkdir($root.'/project/tests', 0700, true);
        copy(dirname(__DIR__).'/DuskIsolation.php', $root.'/project/tests/DuskIsolation.php');
        $database = $root.'/database.sqlite';
        $sentinel = $root.'/sentinel.sqlite';
        $pdo = new PDO('sqlite:'.$sentinel);
        $pdo->exec("CREATE TABLE sentinel (value TEXT); INSERT INTO sentinel VALUES ('keep')");
        touch($database);
        $script = $root.'/project/probe.php';
        file_put_contents($script, <<<'PHP'
<?php
require $argv[1];
require __DIR__.'/tests/DuskIsolation.php';
$app = new Illuminate\Foundation\Application(__DIR__);
$app->instance('config', new Illuminate\Config\Repository([
    'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => getenv('DUSK_ISOLATED_ROOT').'/database.sqlite', 'url' => $argv[2]]]],
]));
$app->singleton('db', fn ($app) => new Illuminate\Database\DatabaseManager($app, new Illuminate\Database\Connectors\ConnectionFactory($app)));
try { Tests\DuskIsolation::verifyConnection($app); echo 'accepted'; }
catch (Throwable $error) { echo 'rejected'; }
PHP);
        $probe = static function (string $url = '') use ($root, $script): string {
            $process = new Process([PHP_BINARY, $script, dirname(__DIR__, 2).'/vendor/autoload.php', $url], null, ['DUSK_ISOLATED_ROOT' => $root]);
            $process->mustRun();

            return $process->getOutput();
        };

        try {
            $this->assertSame('accepted', $probe());
            $this->assertSame('rejected', $probe('sqlite:///'.$sentinel));
            unlink($database);
            symlink($sentinel, $database);
            $this->assertSame('rejected', $probe());
            unlink($database);
            link($sentinel, $database);
            $this->assertSame('rejected', $probe());
            $this->assertSame('keep', $pdo->query('SELECT value FROM sentinel')->fetchColumn());
        } finally {
            $pdo = null;
            @unlink($database);
            unlink($sentinel);
            unlink($script);
            unlink($root.'/project/tests/DuskIsolation.php');
            rmdir($root.'/project/tests');
            rmdir($root.'/project');
            rmdir($root);
        }
    }

    public function test_unmanaged_database_is_not_migrated(): void
    {
        $directory = sys_get_temp_dir().'/dusk-guard-test-'.bin2hex(random_bytes(16));
        mkdir($directory, 0700);
        $database = $directory.'/sentinel.sqlite';
        $pdo = new PDO('sqlite:'.$database);
        $pdo->exec("CREATE TABLE migrations (id INTEGER PRIMARY KEY, migration TEXT, batch INTEGER); CREATE TABLE sentinel (value TEXT); INSERT INTO sentinel VALUES ('keep')");
        $test = $directory.'/UnsafeDuskTest.php';
        file_put_contents($test, <<<'PHP'
<?php
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\DuskTestCase;
class UnsafeDuskTest extends DuskTestCase {
    use DatabaseMigrations;
    public static function prepare(): void {}
    public function test_probe(): void { $this->assertTrue(true); }
}
PHP);

        try {
            $process = new Process([PHP_BINARY, 'vendor/bin/phpunit', '--no-configuration', '--do-not-cache-result', '--bootstrap', 'vendor/autoload.php', $test], dirname(__DIR__, 2), [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
                'DUSK_ISOLATED_ROOT' => '',
            ]);
            $process->run();

            $this->assertSame('keep', $pdo->query('SELECT value FROM sentinel')->fetchColumn());
            $this->assertNotSame(0, $process->getExitCode());
        } finally {
            $pdo = null;
            unlink($test);
            unlink($database);
            rmdir($directory);
        }
    }
}
