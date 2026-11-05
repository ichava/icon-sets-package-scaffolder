<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| A pack depends on ichava/core, and on nothing else
|--------------------------------------------------------------------------
|
| Core alone uses the laranail/* packages. A pack requires `php` and
| `ichava/core`, extends core's IconPackServiceProvider, and names no class
| from any other Simtabi\Laranail namespace, so a laranail change reaches the
| packs through core and nowhere else.
|
| `Simtabi\Laranail\Ichava\` is core's namespace, and this pack's own sits
| under it. `Simtabi\Laranail\Ichava\IconBrowser\` is ichava/icon-browser's,
| which a pack must not depend on either.
|
| Reads the committed composer.json. CI aliases core's main into it for the
| install and restores the file before the tests run.
|
| Pest functions only: a PHPUnit class in the same file would stop being
| collected.
|
*/

/** @return array<string, mixed> */
function coreOnlyComposerJson(): array
{
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    return is_array($composer) ? $composer : [];
}

it('requires php and ichava/core, and nothing else', function (): void {
    $require = array_keys(coreOnlyComposerJson()['require'] ?? []);
    sort($require);

    expect($require)->toBe(['ichava/core', 'php']);
});

it('declares the composer type core discovers packs by', function (): void {
    expect(coreOnlyComposerJson()['type'] ?? null)->toBe('ichava-icon-set');
});

it('imports no laranail namespace but ichava/core from src/', function (): void {
    $inspected = 0;
    $offenders = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src', FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $inspected++;
        $source = (string) file_get_contents($file->getPathname());

        // Every spelling: a `use` line, a fully qualified name, or a string with
        // escaped backslashes.
        preg_match_all('/Simtabi\\\\+Laranail\\\\+(\w+)(?:\\\\+(\w+))?/', $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($match[1] !== 'Ichava' || ($match[2] ?? '') === 'IconBrowser') {
                $offenders[] = $file->getFilename() . ': ' . $match[0];
            }
        }
    }

    // Every pack has a provider, a constants class, an enum and a component. A
    // walk that stops finding them must fail, not pass over an empty tree.
    expect($inspected)->toBeGreaterThanOrEqual(4)
        ->and($offenders)->toBe([]);
});
