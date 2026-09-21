<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;

/**
 * A scaffolded pack must be born with the names the estate actually uses.
 *
 * The estate renamed to the `icon-sets-` PREFIX on 2026-09-21 -- directory,
 * GitHub repo and composer name together. The scaffolder kept deriving an
 * `-icons` SUFFIX, so a pack generated today got a composer name, config key,
 * component tag and command name matching nothing that exists.
 *
 * Measured on the real packs rather than assumed:
 *   composer  ichava/icon-sets-flag
 *   config    config/icon-sets-flag.php
 *   component <x-icon-sets-flag-icon>
 */
function estateDefinition(array $overrides = []): PackageDefinition
{
    return PackageDefinition::create(...array_merge([
        'name'   => 'Flag',
        'vendor' => 'Ichava',
        'email'  => 'dev@example.com',
    ], $overrides));
}

it('derives the names the estate uses', function (): void {
    $d = estateDefinition();

    expect($d->bladeNamespace())->toBe('icon-sets-flag')
        ->and($d->packageName())->toBe('ichava/icon-sets-flag')
        ->and($d->updateCommandName())->toBe('ichava::icon-sets-flag.update');
});

it('does not double the prefix when the name already carries it', function (): void {
    // `emoji-sets` became `icon-sets-emoji`, not `icon-sets-emoji-sets`. Someone
    // typing the full slug must not get `icon-sets-icon-sets-flag`.
    expect(estateDefinition(['name' => 'icon-sets-flag'])->bladeNamespace())
        ->toBe('icon-sets-flag')
        ->and(estateDefinition(['name' => 'IconSetsFlag'])->bladeNamespace())
        ->toBe('icon-sets-flag');
});

it('leaves no stub reconstructing the namespace by hand', function (): void {
    // `{{bladeNamespace}}` is the single derivation. Ten stub sites spelled
    // `{{kebabName}}-icons` instead, so the rebrand reached the packages and
    // not what the scaffolder emits. A token exists; stubs must use it.
    $stubs = dirname(__DIR__, 2) . '/stubs';

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($stubs, FilesystemIterator::SKIP_DOTS),
    );

    $offenders = [];
    $scanned = 0;
    foreach ($files as $file) {
        if ($file->isDir()) {
            continue;
        }
        $scanned++;
        $body = (string) file_get_contents($file->getPathname());
        if (str_contains($body, '{{kebabName}}-icons')) {
            $offenders[] = str_replace($stubs . '/', '', $file->getPathname());
        }
    }

    expect($scanned)->toBeGreaterThan(0, 'the stub scan matched no files');
    expect($offenders)->toBe([]);
});

it('ships the config file under the name its provider declares', function (): void {
    // `V39`: package-tools resolves a config file BY the name passed to
    // hasConfigFile(). The stub tree shipped `config/config.php` while the
    // generated provider declared `hasConfigFile('icon-sets-<name>')`, so a
    // scaffolded pack's entire config was inert -- the same defect, and the
    // same silence, that cost this estate months in `ichava/core`.
    //
    // Asserted on the stub filename because that is where the mismatch lives;
    // `destinationFor()` substitutes path tokens, so the rendered name follows.
    $stubs = dirname(__DIR__, 2) . '/stubs/config';

    expect(glob($stubs . '/config.php.stub'))->toBe([], 'stubs/config/config.php.stub is a name no provider declares')
        ->and(glob($stubs . '/{{bladeNamespace}}.php.stub'))->not->toBeEmpty();
});
