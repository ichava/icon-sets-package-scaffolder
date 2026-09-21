<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| A scaffolded pack's docs match the shape every real pack uses
|--------------------------------------------------------------------------
|
| Asserted on the RENDERED pack, never on the stub tree. A stub is not a
| document -- `{{token}}` opens a YAML flow mapping wherever it begins a
| scalar, and a half-substituted link reads fine in the stub and breaks in the
| output.
|
| The anchor assertion exists because the estate shipped the inverse: sixteen
| pages across five packs opened with a back-link to `#pack-specific-docs`
| after that heading had been renamed to `#documentation`, and the footer
| check in place at the time did not look at the top of the file.
|
*/

it('scaffolds docs in the shape every pack in the estate uses', function (): void {
    // Asserted on the RENDERED pack, not the stub tree. A stub is not a
    // document: `{{token}}` opens a YAML flow mapping and a half-substituted
    // link looks fine in the stub and breaks in the output.
    $root = sys_get_temp_dir() . '/ichava-docshape-' . bin2hex(random_bytes(4));

    $definition = PackageDefinition::create(
        name: 'Beacon',
        vendor: 'Acme',
        email: 'dev@example.com',
    );
    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root), force: true);

    $readme = (string) file_get_contents($root . '/README.md');
    $docs = glob($root . '/docs/*.md') ?: [];

    expect($docs)->not->toBeEmpty('the scaffolded pack shipped no docs');

    // The README carries the anchor every page's footer points at.
    expect(str_contains($readme, '## <a name="documentation"></a>Documentation'))
        ->toBeTrue('the README has no anchored Documentation heading for the footers to target');

    foreach ($docs as $page) {
        $body = (string) file_get_contents($page);
        $name = basename($page);

        // `toContain()` is VARIADIC -- a second string argument is another
        // needle, not a failure message. Asserting a boolean is the only way
        // to attach one here, and writing it the other way is how this test
        // first failed on a file that was perfectly correct.
        expect(str_starts_with($body, '# '))
            ->toBeTrue("{$name} does not open with an H1");

        expect(str_contains($body, '[← Docs index](../README.md#documentation)'))
            ->toBeTrue("{$name} has no footer");

        // A scaffolder token, not any `{{`. The convention is mustache with no
        // internal spaces, which is exactly what distinguishes `{{packageName}}`
        // from the Blade a pack's docs legitimately show the reader:
        // `{{ ichava(...) }}` has spaces and `{{-- ... --}}` starts with a dash.
        expect(preg_match('/\{\{[A-Za-z][A-Za-z0-9]*\}\}/', $body))
            ->toBe(0, "{$name} carries an unrendered scaffolder token");

        // Cross-repo references use the hosted docs, not a GitHub blob path
        // that breaks the moment a file moves.
        expect(str_contains($body, 'github.com/ichava/core/blob'))
            ->toBeFalse("{$name} links a blob path");
    }

    // Every page the README advertises actually exists.
    preg_match_all('/\]\(docs\/([A-Za-z0-9_-]+\.md)\)/', $readme, $m);
    expect($m[1])->not->toBeEmpty('the README lists no docs pages');
    foreach ($m[1] as $listed) {
        expect(is_file($root . '/docs/' . $listed))->toBeTrue("README lists docs/{$listed}, which was not scaffolded");
    }

    // Plain PHP: symfony/filesystem is not a dependency of this package, and
    // reaching for one in a teardown is how a green test turns red on a
    // machine that happens not to have it.
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($it as $entry) {
        $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
    }
    @rmdir($root);
});
