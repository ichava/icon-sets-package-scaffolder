<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\StubLocator;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\ScaffoldResult;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

function scaffold(array $overrides, string $label): array
{
    $root = scratchDirectory($label);
    $path = TargetPath::absolute($root . '/pack');

    $definition = PackageDefinition::create(...array_merge([
        'name'   => 'Hero',
        'vendor' => 'Acme',
        'email'  => 'dev@example.com',
    ], $overrides));

    $result = app(ScaffoldIconPackage::class)($definition, $path);

    return [$result, $root, $path->value];
}

it('writes every stub, with no token left behind', function (): void {
    [$result, $root, $path] = scaffold([], 'single');

    // Counted from the tree rather than written as a literal: a stub added or
    // removed should not need this number edited, and a literal would go stale
    // silently in the direction that matters -- a stub that stopped being
    // written still satisfies a count nobody updated.
    $stubCount = count((new StubLocator(dirname(__DIR__, 2) . '/stubs/icon-package'))->all());

    expect($result)->toBeInstanceOf(ScaffoldResult::class)
        ->and($result->fileCount())->toBe($stubCount)
        ->and($result->files)->toContain('composer.json', '.github/workflows/tests.yml')
        ->and($result->files)->not->toContain('docs/README.md');

    foreach ($result->files as $file) {
        $contents = (string) file_get_contents($path . '/' . $file);

        // The failure this catches is a generated package that looks right and
        // carries a literal {{token}} in its composer name or namespace.
        expect($contents)->not->toMatch('/\{\{[a-zA-Z]+\}\}/', $file);
    }

    removeDirectory($root);
});

it('produces a package composer can parse and a PHP file php can parse', function (): void {
    [$result, $root, $path] = scaffold([], 'valid');

    $composer = json_decode((string) file_get_contents($path . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['name'])->toBe('acme/hero-icons')
        ->and($composer['authors'][0]['email'])->toBe('dev@example.com')
        ->and($composer['autoload']['psr-4'])->toHaveKey('Acme\\HeroIcons\\')
        ->and($composer['extra']['laravel']['providers'][0])
        ->toBe('Acme\\HeroIcons\\Providers\\IconsServiceProvider');

    // A stub that substitutes a namespace into PHP is one escaping mistake away
    // from a parse error, and a parse error in a generated package surfaces on
    // the consumer's machine, not here.
    foreach ($result->files as $file) {
        if (str_ends_with($file, '.php')) {
            exec(sprintf('php -d opcache.enable_cli=0 -l %s 2>&1', escapeshellarg($path . '/' . $file)), $output, $status);
            expect($status)->toBe(0, $file . ': ' . implode("\n", $output));
        }
    }

    json_decode((string) file_get_contents($path . '/resources/assets/svg/config.json'), true, flags: JSON_THROW_ON_ERROR);

    removeDirectory($root);
});

it('creates one icon directory for a single-set pack', function (): void {
    [$result, $root, $path] = scaffold([], 'dirs-single');

    expect($result->directories)->toBe(['resources/assets/svg/files'])
        ->and(is_file($path . '/resources/assets/svg/files/.gitkeep'))->toBeTrue();

    removeDirectory($root);
});

it('creates one icon directory per variant for a multi-set pack', function (): void {
    [$result, $root, $path] = scaffold(
        ['type' => 'multi', 'variants' => ['outline', 'solid', 'duotone']],
        'dirs-multi',
    );

    expect($result->directories)->toBe([
        'resources/assets/svg/files/duotone',
        'resources/assets/svg/files/outline',
        'resources/assets/svg/files/solid',
    ]);

    // .gitkeep is what makes the layout survive the first commit: git does not
    // track empty directories, and a freshly scaffolded pack has no icons.
    foreach (['outline', 'solid', 'duotone'] as $variant) {
        expect(is_file($path . '/resources/assets/svg/files/' . $variant . '/.gitkeep'))->toBeTrue();
    }

    $config = json_decode(
        (string) file_get_contents($path . '/resources/assets/svg/config.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    // Ordered, because the first variant owns the unsuffixed icon filenames:
    // reorder them and a pack's default icons resolve to nothing.
    expect(array_keys($config['metadata']['data']['variants']))->toBe(['outline', 'solid', 'duotone'])
        ->and($config['metadata']['data']['variants']['outline']['default'] ?? null)->toBeTrue();

    removeDirectory($root);
});

it('refuses a destination that already holds something', function (): void {
    $root = scratchDirectory('occupied');
    mkdir($root . '/pack');
    file_put_contents($root . '/pack/composer.json', '{"name":"someone/else"}');

    $definition = PackageDefinition::create(name: 'Hero', vendor: 'Acme', email: 'dev@example.com');

    try {
        app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root . '/pack'));
        $thrown = false;
    } catch (RuntimeException) {
        $thrown = true;
    }

    // Bulk file creation against a path a human typed. Overwriting somebody's
    // package has to be asked for, not defaulted to.
    expect($thrown)->toBeTrue()
        ->and(file_get_contents($root . '/pack/composer.json'))->toBe('{"name":"someone/else"}');

    removeDirectory($root);
});

it('overwrites when forced', function (): void {
    $root = scratchDirectory('forced');
    mkdir($root . '/pack');
    file_put_contents($root . '/pack/composer.json', '{"name":"someone/else"}');

    $definition = PackageDefinition::create(name: 'Hero', vendor: 'Acme', email: 'dev@example.com');
    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root . '/pack'), force: true);

    expect(json_decode((string) file_get_contents($root . '/pack/composer.json'), true)['name'])
        ->toBe('acme/hero-icons');

    removeDirectory($root);
});

it('is reachable without a terminal', function (): void {
    // The point of the action seam. Before it existed, generation was reachable
    // only through a command's handle(), so exercising it meant driving prompts.
    $scaffold = new ScaffoldIconPackage(
        new Filesystem,
        app(Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\RenderStubTree::class),
        app(Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\CreateIconDirectories::class),
    );

    $root = scratchDirectory('headless');
    $result = $scaffold(
        PackageDefinition::create(name: 'Hero', vendor: 'Acme', email: 'dev@example.com'),
        TargetPath::absolute($root . '/pack'),
    );

    expect($result->fileCount())->toBeGreaterThan(0);

    removeDirectory($root);
});
