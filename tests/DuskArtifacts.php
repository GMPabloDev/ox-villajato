<?php

namespace Tests;

use RuntimeException;

final class DuskArtifacts
{
    public static function preserve(string $project, string $destination): ?string
    {
        $files = [];
        foreach (['screenshots', 'console', 'source'] as $kind) {
            $directory = $project.'/tests/Browser/'.$kind;
            if (! is_dir($directory)) {
                continue;
            }
            foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $entry) {
                if ($entry->isLink() || ! $entry->isFile()) {
                    throw new RuntimeException('Dusk artifact is not a regular file: '.$entry->getPathname());
                }
                $files[$kind][] = $entry->getPathname();
            }
        }
        if ($files === []) {
            return null;
        }

        $target = $destination.'/ox-dusk-artifacts-'.bin2hex(random_bytes(20));
        if (! mkdir($target, 0700)) {
            throw new RuntimeException('Could not create the Dusk artifacts directory.');
        }
        foreach ($files as $kind => $paths) {
            if (! mkdir($target.'/'.$kind, 0700)) {
                throw new RuntimeException('Could not create the Dusk artifacts subdirectory.');
            }
            foreach ($paths as $path) {
                if (! copy($path, $target.'/'.$kind.'/'.basename($path))) {
                    throw new RuntimeException('Could not preserve Dusk artifact: '.$path);
                }
            }
        }

        return $target;
    }
}
