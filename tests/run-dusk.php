<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\DuskArtifacts;

require dirname(__DIR__).'/vendor/autoload.php';

$source = dirname(__DIR__);
$root = sys_get_temp_dir().'/ox-dusk-'.bin2hex(random_bytes(20));
if (! mkdir($root, 0700)) {
    throw new RuntimeException('Could not create the Dusk temporary directory.');
}
$root = realpath($root);
$project = $root.'/project';
$server = null;
$driver = null;

if (extension_loaded('pcntl')) {
    pcntl_async_signals(true);
    foreach ([SIGINT, SIGTERM] as $signal) {
        pcntl_signal($signal, static function (): void {
            throw new RuntimeException('Dusk execution interrupted.');
        });
    }
}

try {
    mkdir($project, 0700);
    $archive = $root.'/project.tar';
    $copy = new Process(['tar', '-cf', $archive, '--exclude=database/*.sqlite*', '--exclude=bootstrap/cache', '--exclude=public/hot', '--exclude=public/storage', '--exclude=tests/Browser/screenshots', '--exclude=tests/Browser/console', '--exclude=tests/Browser/source', '-C', $source, 'app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'tests', 'artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.ts', 'tsconfig.json']);
    $copy->setTimeout(180)->mustRun();
    (new Process(['tar', '-xf', $archive, '-C', $project]))->setTimeout(180)->mustRun();
    (new Process(['cp', '-a', '--reflink=auto', $source.'/vendor', $project.'/vendor']))->setTimeout(180)->mustRun();
    (new Process(['cp', '-a', '--reflink=auto', $source.'/node_modules', $project.'/node_modules']))->setTimeout(180)->mustRun();
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($project, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $entry) {
        if ($entry->isLink()) {
            $target = realpath($entry->getPathname());
            if ($target === false || ! str_starts_with($target, $project.'/')) {
                throw new RuntimeException('Dusk refused a link outside its temporary project: '.$entry->getPathname());
            }
        }
    }
    foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/public', 'tests/Browser/screenshots', 'tests/Browser/console', 'tests/Browser/source'] as $directory) {
        mkdir($project.'/'.$directory, 0700, true);
    }
    symlink($project.'/storage/app/public', $project.'/public/storage');
    $database = $root.'/database.sqlite';
    $file = fopen($database, 'x');
    if ($file === false) {
        throw new RuntimeException('Could not create the Dusk database.');
    }
    fclose($file);

    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (! $socket) {
        throw new RuntimeException($error);
    }
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $url = 'http://127.0.0.1:'.$port;
    $token = bin2hex(random_bytes(32));
    $env = ['env', '-i', 'PATH='.dirname(PHP_BINARY).':/usr/local/bin:/usr/bin:/bin', 'HOME='.$root, 'APP_ENV=local', 'APP_DEBUG=true', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), 'APP_URL='.$url, 'DB_CONNECTION=sqlite', 'DB_DATABASE='.$database, 'DB_URL=', 'SESSION_DRIVER=database', 'CACHE_STORE=array', 'QUEUE_CONNECTION=sync', 'MAIL_MAILER=array', 'FILESYSTEM_DISK=local', 'LOG_CHANNEL=stderr', 'DUSK_ISOLATED_ROOT='.$root, 'DUSK_ISOLATED_URL='.$url, 'DUSK_ISOLATED_TOKEN='.$token];

    $build = new Process([...$env, 'npm', 'run', 'build'], $project);
    $build->setTimeout(null);
    $build->mustRun();

    $server = new Process([...$env, PHP_BINARY, '-S', '127.0.0.1:'.$port, '-t', 'public', 'tests/dusk-router.php'], $project);
    $server->setTimeout(null);
    $server->disableOutput();
    $server->start();
    $ready = false;
    for ($i = 0; $i < 100 && $server->isRunning(); $i++) {
        if (@file_get_contents($url.'/_dusk_isolation_probe?token='.$token) === $token.':'.stat($database)['dev'].':'.stat($database)['ino']) {
            $ready = true;
            break;
        }
        usleep(100000);
    }
    if (! $ready) {
        throw new RuntimeException('The isolated Dusk server did not start or did not use the isolated database.');
    }

    $driver = new Process([...$env, $project.'/vendor/laravel/dusk/bin/chromedriver-linux', '--port=0'], $project);
    $driver->setTimeout(null);
    $driver->start();
    $driverPort = null;
    for ($i = 0; $i < 100 && $driver->isRunning(); $i++) {
        if (preg_match('/ChromeDriver was started successfully on port (\d+)/', $driver->getOutput(), $matches)) {
            $driverPort = $matches[1];
            break;
        }
        usleep(100000);
    }
    if ($driverPort === null) {
        throw new RuntimeException('The isolated ChromeDriver did not start. '.$driver->getErrorOutput());
    }

    $test = new Process([...$env, 'DUSK_DRIVER_URL=http://127.0.0.1:'.$driverPort, PHP_BINARY, 'vendor/bin/phpunit', '--no-configuration', '--do-not-cache-result', '--bootstrap', 'vendor/autoload.php', ...array_slice($argv, 1), 'tests/Browser'], $project);
    $test->setTimeout(300);
    $status = $test->run(static function (string $type, string $data): void {
        fwrite($type === Process::ERR ? STDERR : STDOUT, $data);
    });
} finally {
    $driver?->stop();
    $server?->stop();
    try {
        $artifacts = DuskArtifacts::preserve($project, sys_get_temp_dir());
    } catch (Throwable $error) {
        fwrite(STDERR, "Dusk artifacts could not be preserved; temporary project retained at {$root}.\n");
        throw $error;
    }
    if ($artifacts !== null) {
        fwrite(STDERR, "Dusk artifacts preserved at {$artifacts}.\n");
    }
    (new Filesystem)->remove($root);
}

exit($status);
