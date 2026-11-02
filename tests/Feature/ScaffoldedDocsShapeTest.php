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
        // Settled 2026-09-22: the index link is a FOOTER, and the H1 is the
        // first line. That is the standard's page template and what every
        // `laranail` docs page does -- measured across `package-management`
        // and `console`, not inferred.
        //
        // This assertion used to require the breadcrumb ABOVE the H1. The two
        // shapes were adopted in different repos within the same hour, and the
        // estate ended up with the link twice on sixteen pages: once at the
        // top, once in the footer it already had. Asserting the count is what
        // catches that, because a page with both satisfies "has a footer" and
        // "opens with a breadcrumb" at the same time.
        expect(str_starts_with($body, '# '))
            ->toBeTrue("{$name} does not open with its H1");

        expect(substr_count($body, '[← Docs index](../README.md#documentation)'))
            ->toBe(1, "{$name} should carry the index link exactly once, as its footer");

        expect(rtrim($body))->toEndWith('[← Docs index](../README.md#documentation)');

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

    // The five concern pages, by name.
    //
    // `StubEstateParityTest` already compares the scaffolded docs against
    // whatever `icon-sets-flag` ships, and that comparison is SYMMETRIC:
    // delete a page from the pack and from the stub together and it stays
    // green. It pins agreement between two things, not the decision they are
    // both meant to encode, so the decision is named here instead.
    //
    // Settled 2026-09-22 on measurement rather than taste:
    // `laranail/authkit-ldap` is one source file and 45 lines and ships all
    // five, and across 67 laranail repositories the twenty thinnest average
    // 3.6 of 5. A thin package writes shorter pages, not fewer.
    foreach ([
        'installation.md',
        'getting-started.md',
        'configuration.md',
        'architecture.md',
        'release.md',
    ] as $concern) {
        expect(is_file($root . '/docs/' . $concern))
            ->toBeTrue("scaffolded packages are missing docs/{$concern}, which the standard requires");
    }

    // The generated pack has to carry its own guard, or the shape above holds
    // only until someone edits a page in the new repository. `docs.yml` is the
    // other half: tests.yml skips `**.md`, so without it a markdown-only pull
    // request in a scaffolded pack runs nothing at all.
    expect(is_file($root . '/tests/Unit/DocsShapeTest.php'))
        ->toBeTrue('scaffolded packages ship no docs guard of their own');
    expect(is_file($root . '/.github/workflows/docs.yml'))
        ->toBeTrue('scaffolded packages ship no workflow that runs the docs guard');

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
