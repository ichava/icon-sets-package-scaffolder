<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| A scaffolded pack separates its code from the icons it redistributes
|--------------------------------------------------------------------------
|
| Asserted on the RENDERED pack. Every estate pack shipped "MIT. (c) Simtabi
| LLC." with nothing saying the icons were someone else's, and three appended
| their asset notice to LICENSE, which made licence detection answer
| NOASSERTION. Fixed in the five packs on 2026-10-26; this keeps a new pack
| from being born with either defect.
|
*/

it('scaffolds an ATTRIBUTION.md that claims no authorship of the icons and ships', function (): void {
    $root = sys_get_temp_dir() . '/ichava-attr-' . bin2hex(random_bytes(4));

    $definition = PackageDefinition::create(
        name: 'Beacon',
        vendor: 'Acme',
        email: 'dev@example.com',
    );
    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root), force: true);

    $attribution = (string) file_get_contents($root . '/ATTRIBUTION.md');
    expect($attribution)->toContain('claims no authorship');
    expect($attribution)->toContain('covers ONLY the package code');
    expect($attribution)->toContain('| Acme | MIT');

    $license = (string) file_get_contents($root . '/LICENSE');
    $this->assertStringEndsWith("DEALINGS IN THE\nSOFTWARE.\n", $license, 'a scaffolded LICENSE carries nothing after the MIT text');

    $readme = (string) file_get_contents($root . '/README.md');
    $this->assertStringContainsString('[ATTRIBUTION.md](ATTRIBUTION.md)', $readme);

    foreach (file($root . '/.gitattributes', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (str_contains($line, 'export-ignore')) {
            $this->assertFalse(fnmatch(ltrim(trim(strtok($line, " \t")), '/'), 'ATTRIBUTION.md'), "'{$line}' drops ATTRIBUTION.md from the dist");
        }
    }

    expect(is_file($root . '/tests/Unit/AttributionTest.php'))->toBeTrue();
});
