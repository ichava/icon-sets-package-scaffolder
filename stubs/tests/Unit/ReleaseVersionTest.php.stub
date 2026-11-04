<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| The pack's versions agree, and release-please keeps them agreeing
|--------------------------------------------------------------------------
|
| resources/assets/svg/config.json carries the pack's own `package.version`,
| which the icon registry reports. release-please moves the manifest on every
| release; unless config.json is in its `extra-files`, `package.version` stays
| behind at the first release and the registry reports a version that does not
| exist. icon-sets-bundled and icon-sets-metronic reported 1.0.0 for a 0.1.0
| package for exactly this reason.
|
| The coupled ichava packages also share one 0.x minor. The branch-alias series
| and the ichava/core constraint move together in the lockstep pull request, and
| the manifest catches up when the release pull request merges, so between the
| two the series may run one minor ahead of the manifest, never further.
|
| Pest functions only: a PHPUnit class in the same file would stop being
| collected (see PackageMetadataTest.php).
|
*/

function packJson(string $relative): array
{
    $path = dirname(__DIR__, 2) . '/' . $relative;
    expect(is_file($path))->toBeTrue("{$relative} is missing.");

    return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

it('has release-please move config.json package.version with each release', function (): void {
    $config = packJson('release-please-config.json');

    $this->assertContains(
        ['type' => 'json', 'path' => 'resources/assets/svg/config.json', 'jsonpath' => '$.package.version'],
        $config['packages']['.']['extra-files'] ?? [],
        'release-please-config.json must list config.json package.version in extra-files.',
    );
});

it('reports the released version in config.json package.version', function (): void {
    $released = packJson('.release-please-manifest.json')['.'] ?? null;
    $reported = packJson('resources/assets/svg/config.json')['package']['version'] ?? null;

    $this->assertSame($released, $reported, 'config.json package.version must equal the release-please manifest.');
});

it('sits on the family minor line with its branch alias and core constraint', function (): void {
    $composer = packJson('composer.json');
    $released = (string) (packJson('.release-please-manifest.json')['.'] ?? '');

    $alias = (string) ($composer['extra']['branch-alias']['dev-main'] ?? '');
    $this->assertMatchesRegularExpression('/^0\.(\d+)\.x-dev$/', $alias, 'branch-alias is not on a 0.x series.');
    $series = (int) explode('.', $alias)[1];

    preg_match_all('/\^0\.(\d+)/', (string) ($composer['require']['ichava/core'] ?? ''), $arms);
    $this->assertNotEmpty($arms[1], 'ichava/core has no ^0.N constraint arm.');
    $this->assertSame($series, max(array_map('intval', $arms[1])), 'ichava/core and branch-alias must name the same minor.');

    $this->assertMatchesRegularExpression('/^0\.(\d+)\.\d+$/', $released, 'The manifest is not on a 0.x version.');
    $minor = (int) explode('.', $released)[1];
    $this->assertContains(
        $series - $minor,
        [0, 1],
        "branch-alias 0.{$series} is not the released 0.{$minor} line or the next one.",
    );
});
