<?php

declare(strict_types=1);

/*
 * A scaffolded pack ships release-please, like every estate pack, and starts
 * where every estate pack does: the manifest already reads the version its
 * CHANGELOG describes, and `v0.1.0` is tagged by hand on that commit.
 *
 * It used to ship an empty manifest and `initial-version`, so release-please
 * would open the first release pull request. The pack's own ReleaseVersionTest,
 * copied from the estate, requires the manifest to equal config.json's version,
 * so every freshly scaffolded pack failed its own suite until the manifest was
 * set by hand -- which is what the packs built on 2026-11-04 all did.
 */

function releaseStub(string $relative): string
{
    $path = dirname(__DIR__, 2) . '/stubs/' . $relative;
    expect(is_file($path))->toBeTrue("stubs/{$relative} is missing.");

    return (string) file_get_contents($path);
}

it('scaffolds the release-please workflow, config and manifest', function (): void {
    expect(releaseStub('.github/workflows/release-please.yml.stub'))
        ->toContain('googleapis/release-please-action@')
        ->toContain('config-file: release-please-config.json')
        ->toContain('manifest-file: .release-please-manifest.json');

    $this->assertStringContainsString(
        'release-please.yml',
        releaseStub('.github/actionlint.yaml.stub'),
        'the scaffolded actionlint config must scope its ignores to release-please.yml',
    );
});

it('starts the manifest at the version the scaffolded changelog describes', function (): void {
    $config = json_decode(releaseStub('release-please-config.json.stub'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(releaseStub('.release-please-manifest.json.stub'), true, flags: JSON_THROW_ON_ERROR);

    preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', releaseStub('CHANGELOG.md.stub'), $m);
    $this->assertNotEmpty($m, 'CHANGELOG.md.stub has no "## [x.y.z]" heading.');

    $this->assertSame(
        ['.' => $m[1]],
        $manifest,
        "The manifest must read the scaffolded CHANGELOG's first version ({$m[1]}), as every estate pack's does.",
    );
    $this->assertArrayNotHasKey(
        'initial-version',
        $config,
        'initial-version is ignored once the manifest names a version; no estate pack carries it.',
    );
    $this->assertTrue($config['skip-changelog'] ?? false, 'Release notes stay hand-written.');
    $this->assertSame('{{packageName}}', $config['packages']['.']['package-name'] ?? null);
});

it('starts a scaffolded pack on the family\'s current minor line', function (): void {
    // The coupled ichava packages share one 0.x minor (core, the packs, the
    // scaffolder...), so `^0.1` means the 0.1 line of the whole family. A new
    // pack joins that line: its first release, its branch-alias series and its
    // ichava/core constraint must all name the same minor.
    $config = json_decode(releaseStub('release-please-config.json.stub'), true, flags: JSON_THROW_ON_ERROR);
    $composer = json_decode(releaseStub('composer.json.stub'), true, flags: JSON_THROW_ON_ERROR);

    $alias = $composer['extra']['branch-alias']['dev-main'] ?? '';
    $this->assertMatchesRegularExpression('/^0\.\d+\.x-dev$/', $alias, 'branch-alias is not on a 0.x series.');
    $series = substr($alias, 0, -strlen('.x-dev'));

    $manifest = json_decode(releaseStub('.release-please-manifest.json.stub'), true, flags: JSON_THROW_ON_ERROR);
    $this->assertSame(
        "{$series}.0",
        $manifest['.'] ?? null,
        "A scaffolded pack's first release must open the family's {$series} line.",
    );
    $this->assertSame(
        "^{$series}",
        $composer['require']['ichava/core'] ?? null,
        "A scaffolded pack must require ichava/core on the family's {$series} line.",
    );

    // resources/assets/svg/config.json carries the pack's own version, which the
    // registry reports; it starts on the same release and moves with it.
    // The stub is not JSON until rendered (unquoted tokens), so read the field
    // out of its "package" object directly.
    preg_match('/"package"\s*:\s*\{.*?"version"\s*:\s*"([^"]+)"/s', releaseStub('resources/assets/svg/config.json.stub'), $v);
    $this->assertSame(
        $manifest['.'],
        $v[1] ?? null,
        'config.json package.version must start at the manifest version.',
    );
    $this->assertContains(
        ['type' => 'json', 'path' => 'resources/assets/svg/config.json', 'jsonpath' => '$.package.version'],
        $config['packages']['.']['extra-files'] ?? [],
        'release-please must move config.json package.version with each release.',
    );
});
