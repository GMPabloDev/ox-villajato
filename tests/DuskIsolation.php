<?php

namespace Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConfigurationUrlParser;
use RuntimeException;

final class DuskIsolation
{
    public static function databasePath(): string
    {
        $root = getenv('DUSK_ISOLATED_ROOT');
        $rootStat = is_string($root) ? @lstat($root) : false;
        $temporaryDirectory = realpath(sys_get_temp_dir());

        if (! is_string($root) || ! $rootStat || is_link($root) || realpath($root) !== $root ||
            ! str_starts_with($root, $temporaryDirectory.'/ox-dusk-') ||
            ($rootStat['mode'] & 0777) !== 0700 || $rootStat['uid'] !== posix_geteuid() ||
            realpath(dirname(__DIR__)) !== $root.'/project') {
            throw new RuntimeException('Dusk requires its own private temporary project. Run composer dusk.');
        }

        $database = $root.'/database.sqlite';
        $stat = @lstat($database);
        if (! $stat || is_link($database) || ! is_file($database) || realpath($database) !== $database ||
            $stat['nlink'] !== 1 || $stat['uid'] !== posix_geteuid()) {
            throw new RuntimeException('Dusk database is not an isolated regular file.');
        }

        return $database;
    }

    public static function verifyConnection(Application $app): string
    {
        $database = self::databasePath();
        $name = $app['config']->get('database.default');
        $config = (new ConfigurationUrlParser)->parseConfiguration($app['config']->get("database.connections.$name", []));
        if (($config['driver'] ?? null) !== 'sqlite' || ($config['database'] ?? null) !== $database ||
            isset($config['read']) || isset($config['write'])) {
            throw new RuntimeException('Dusk refused an unsafe database connection.');
        }

        $connection = $app['db']->connection();
        $rows = $connection->select('PRAGMA database_list');
        $actual = $rows[0]->file ?? null;
        if ($connection->getDriverName() !== 'sqlite' || $actual !== $database) {
            throw new RuntimeException('Dusk refused the effective database connection.');
        }

        $stat = stat($database);

        return $stat['dev'].':'.$stat['ino'];
    }
}
