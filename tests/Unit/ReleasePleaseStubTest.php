<?php

declare(strict_types=1);

/*
 * A scaffolded pack ships release-please, like every estate pack. Its first
 * release is the version its CHANGELOG already describes: release-please starts
 * from `initial-version` when the manifest is empty, and the workflow's
 * changelog close leaves a branch alone once the version's section exists. If the
 * two versions disagree, the first release pull request finds no section and no
 * [Unreleased] to close, and the workflow fails on the pack's first release.
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

it('starts the first release at the version the scaffolded changelog describes', function (): void {
    $config = json_decode(releaseStub('release-please-config.json.stub'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(releaseStub('.release-please-manifest.json.stub'), true, flags: JSON_THROW_ON_ERROR);

    preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', releaseStub('CHANGELOG.md.stub'), $m);
    $this->assertNotEmpty($m, 'CHANGELOG.md.stub has no "## [x.y.z]" heading.');

    $this->assertSame([], $manifest, 'An empty manifest is what makes release-please use initial-version.');
    $this->assertSame(
        $m[1],
        $config['initial-version'] ?? null,
        "initial-version must equal the scaffolded CHANGELOG's first version ({$m[1]}).",
    );
    $this->assertTrue($config['skip-changelog'] ?? false, 'Release notes stay hand-written.');
    $this->assertSame('{{packageName}}', $config['packages']['.']['package-name'] ?? null);
});
