<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * A throwaway directory under the system temp root.
 *
 * Generation writes real files, so every test that scaffolds needs somewhere to
 * put them that is not the repository. Tests clean up after themselves; the
 * unique suffix means a crashed run leaves one directory rather than corrupting
 * the next run's expectations.
 */
function scratchDirectory(string $label): string
{
    $path = sys_get_temp_dir() . '/ichava-scaffolder-' . $label . '-' . bin2hex(random_bytes(6));

    mkdir($path, 0o777, true);

    return $path;
}

function removeDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($path);
}
