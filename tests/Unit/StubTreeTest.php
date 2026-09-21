<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\StubLocator;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\StubRenderer;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\TokenMapFactory;

function bundledStubs(): StubLocator
{
    return new StubLocator(dirname(__DIR__, 2) . '/stubs');
}

it('discovers dotfiles and the workflow tree', function (): void {
    // Finder skips dotfiles by default, and the stub tree is four workflows,
    // a .gitignore and a .gitattributes deep in exactly that blind spot. A pack
    // generated without them looks complete and has no CI.
    $relative = array_map(bundledStubs()->relativePath(...), bundledStubs()->all());

    expect($relative)->toContain('.gitignore.stub')
        ->toContain('.gitattributes.stub')
        ->toContain('.github/workflows/tests.yml.stub')
        ->toContain('.github/workflows/code-quality.yml.stub')
        ->toContain('.github/workflows/release.yml.stub')
        ->toContain('.github/workflows/sync-upstream.yml.stub')
        ->toContain('composer.json.stub')
        ->toContain('src/Providers/IconsServiceProvider.php.stub');
});

it('finds every stub on disk, with nothing filtered out silently', function (): void {
    $onDisk = [];

    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(bundledStubs()->root(), FilesystemIterator::SKIP_DOTS),
    ) as $file) {
        if ($file->isFile() && ! in_array($file->getFilename(), ['.DS_Store', 'Thumbs.db', 'desktop.ini'], true)) {
            $onDisk[] = $file->getPathname();
        }
    }

    expect(bundledStubs()->all())->toHaveCount(count($onDisk));
});

it('strips the .stub suffix and substitutes tokens in path segments', function (): void {
    $tokens = ['{{studlyName}}' => 'Hero', '{{kebabName}}' => 'hero'];

    expect(bundledStubs()->destinationFor('src/Constants/IconsConstants.php.stub', $tokens))
        ->toBe('src/Constants/IconsConstants.php')
        ->and(bundledStubs()->destinationFor('.github/workflows/tests.yml.stub', $tokens))
        ->toBe('.github/workflows/tests.yml')
        ->and(bundledStubs()->destinationFor('src/{{studlyName}}/{{kebabName}}.php.stub', $tokens))
        ->toBe('src/Hero/hero.php');
});

it('refuses a stubs root that does not exist', function (): void {
    (new StubLocator('/nope/stubs'))->all();
})->throws(RuntimeException::class);

it('builds every token a stub references', function (): void {
    $definition = PackageDefinition::create(
        name: 'Hero',
        vendor: 'Acme',
        email: 'dev@example.com',
        type: 'multi',
        variants: ['outline', 'solid'],
    );

    $tokens = (new TokenMapFactory)->for($definition);

    // Measured against the tree rather than listed by hand: a stub that starts
    // using a token nobody produces would otherwise ship the literal
    // `{{token}}` into a generated package, and nothing would notice.
    $referenced = [];

    foreach (bundledStubs()->all() as $stub) {
        preg_match_all('/\{\{[a-zA-Z]+\}\}/', (string) file_get_contents($stub), $matches);
        $referenced = [...$referenced, ...$matches[0]];
    }

    expect(array_values(array_unique(array_diff($referenced, array_keys($tokens)))))->toBe([]);
});

it('produces the values core produced, so an extracted pack is unchanged', function (): void {
    $tokens = (new TokenMapFactory)->for(PackageDefinition::create(
        name: 'Hero',
        vendor: 'Acme Labs',
        email: 'dev@example.com',
    ));

    expect($tokens)
        ->toHaveKey('{{vendorStudly}}', 'AcmeLabs')
        ->toHaveKey('{{vendorKebab}}', 'acme-labs')
        ->toHaveKey('{{vendorSnake}}', 'acme_labs')
        ->toHaveKey('{{namespace}}', 'AcmeLabs\\HeroIcons')
        ->toHaveKey('{{namespaceEscaped}}', 'AcmeLabs\\\\HeroIcons')
        ->toHaveKey('{{packageName}}', 'acme-labs/hero-icons')
        ->toHaveKey('{{bladeNamespace}}', 'hero-icons')
        ->toHaveKey('{{email}}', 'dev@example.com')
        ->toHaveKey('{{iconSetType}}', 'single')
        ->toHaveKey('{{variantsJson}}', '{}');
});

it('renders a single-set pack with an empty variants object, not an array', function (): void {
    // json_encode turns an empty PHP array into `[]`, and config.json declares
    // `variants` as an object. A pack shipping `"variants": []` reads back as
    // the wrong type the moment anything iterates it as a map.
    $tokens = (new TokenMapFactory)->for(PackageDefinition::create(
        name: 'Hero',
        vendor: 'Acme',
        email: 'dev@example.com',
        type: 'multi',
        variants: ['outline'],
    ));

    $decoded = json_decode('{"variants": ' . $tokens['{{variantsJson}}'] . '}', true);

    expect($decoded['variants'])->toHaveKey('outline')
        ->and($decoded['variants']['outline']['attributes'])->toBe([])
        ->and($tokens['{{variantsJson}}'])->toContain('"attributes": {}');
});

it('substitutes content tokens and creates parent directories', function (): void {
    $root = scratchDirectory('renderer');
    $stub = $root . '/source.stub';
    file_put_contents($stub, 'namespace {{namespace}};');

    (new StubRenderer(new Filesystem))->renderTo(
        $stub,
        $root . '/deep/nested/Out.php',
        ['{{namespace}}' => 'Acme\\HeroIcons'],
    );

    expect(file_get_contents($root . '/deep/nested/Out.php'))->toBe('namespace Acme\\HeroIcons;');

    removeDirectory($root);
});

it('refuses to render a stub that is not there', function (): void {
    (new StubRenderer(new Filesystem))->renderTo('/nope.stub', '/tmp/out.php', []);
})->throws(RuntimeException::class);
