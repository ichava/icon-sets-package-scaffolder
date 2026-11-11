<?php

declare(strict_types=1);

/*
 * S-06. An action is one intent with one public entry point, handle(), as
 * in core (C-13). Read by reflection, with a floor on what was inspected.
 */

it('gives every action exactly one public method, handle()', function (): void {
    $files = glob(dirname(__DIR__, 2) . '/src/Actions/*.php') ?: [];

    expect(count($files))->toBeGreaterThanOrEqual(3);

    foreach ($files as $file) {
        $class = new ReflectionClass('Simtabi\\Laranail\\Ichava\\IconSetsPackageScaffolder\\Actions\\' . basename($file, '.php'));
        $public = array_values(array_filter(
            array_map(fn (ReflectionMethod $m): string => $m->getName(), $class->getMethods(ReflectionMethod::IS_PUBLIC)),
            fn (string $name): bool => $name !== '__construct',
        ));

        expect($class->isFinal())->toBeTrue()
            ->and($public)->toBe(['handle'], $class->getShortName());
    }
});

it('reaches the container through no app() call in src', function (): void {
    $found = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src', FilesystemIterator::SKIP_DOTS)) as $file) {
        $code = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', (string) file_get_contents($file->getPathname()));

        if (preg_match('/\bapp\(/', (string) $code) === 1) {
            $found[] = $file->getFilename();
        }
    }

    expect($found)->toBe([]);
});
