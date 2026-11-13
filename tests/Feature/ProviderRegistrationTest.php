<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\StubLocator;

it('publishes under vendor-namespaced tags', function (): void {
    // A publish tag is a key in a host-owned flat map, same as a command name.
    // A bare `stubs` or `config` here would be claimed by whichever package
    // registered last, and vendor:publish would copy the wrong tree.
    expect(array_keys(ServiceProvider::$publishGroups))
        ->toContain('ichava::icon-sets-package-scaffolder-config')
        ->toContain('ichava::icon-sets-package-scaffolder-stubs')
        ->not->toContain('stubs')
        ->not->toContain('config')
        ->not->toContain('icon-sets-package-scaffolder');
});

it('merges config at the key the code reads', function (): void {
    // V39: package-tools appends the config FILENAME to the namespace whenever
    // it differs from the package short name, so a mismatched filename merges
    // the whole file one level deeper than every read site looks -- silently.
    expect(config('ichava.icon-sets-package-scaffolder'))->toBeArray()
        ->and(config()->has('ichava.icon-sets-package-scaffolder.stubs_path'))->toBeTrue()
        ->and(config('ichava.icon-sets-package-scaffolder.icon-sets-package-scaffolder'))->toBeNull();
});

it('resolves the stub locator against the bundled tree by default', function (): void {
    $locator = app(StubLocator::class);

    expect($locator->root())->toBe(dirname(__DIR__, 2) . '/stubs')
        ->and($locator->all())->not->toBeEmpty();
});

it('honours a configured stubs path', function (): void {
    $custom = scratchDirectory('custom-stubs');
    file_put_contents($custom . '/README.md.stub', '# {{packageName}}');

    config()->set('ichava.icon-sets-package-scaffolder.stubs_path', $custom);

    // Resolved lazily rather than at boot, so a host can swap the tree at
    // runtime and a test can swap it without rebuilding the container.
    expect(app(StubLocator::class)->root())->toBe($custom)
        ->and(app(StubLocator::class)->all())->toHaveCount(1);

    removeDirectory($custom);
});
