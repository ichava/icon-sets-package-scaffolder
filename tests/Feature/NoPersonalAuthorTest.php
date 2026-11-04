<?php

declare(strict_types=1);

/*
 * The package, and every pack it scaffolds, is published under Simtabi LLC
 * with the role address ichava@simtabi.com; no personal name or email ships.
 * The stub tree is scanned too, so a generated pack is not born with one.
 */
it('publishes no personal author name or email, here or in a scaffolded pack', function (): void {
    $root = dirname(__DIR__, 2);
    $files = [];

    foreach (['composer.json', 'README.md'] as $file) {
        $this->assertFileExists("{$root}/{$file}");
        $files[] = "{$root}/{$file}";
    }

    foreach (['docs', 'src', 'stubs'] as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
    }

    // A walk that stops matching would scan nothing and pass.
    $this->assertGreaterThan(60, count($files), 'The scan found too few files to mean anything.');

    $offenders = [];

    // The one stub that must spell the pattern: the generated pack's own
    // NoPersonalContactTest is the regex that fails on a personal name, so it
    // cannot avoid containing it. Exempted by exact path, and it has to exist,
    // so a renamed stub cannot leave the exemption covering nothing.
    $guardStub = $root . '/stubs/tests/Unit/NoPersonalContactTest.php.stub';
    $this->assertFileExists($guardStub);

    foreach ($files as $path) {
        if ($path === $guardStub) {
            continue;
        }

        if (preg_match('/imani@|manyara/i', (string) file_get_contents($path)) === 1) {
            $offenders[] = substr($path, strlen($root) + 1);
        }
    }

    $this->assertSame([], $offenders, 'Name Simtabi LLC (ichava@simtabi.com), not a person.');
});
