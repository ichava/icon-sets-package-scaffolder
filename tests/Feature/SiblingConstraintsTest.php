<?php

declare(strict_types=1);

use Composer\Semver\Semver;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| Every ichava/* constraint admits 0.1.0, here and in a scaffolded pack
|--------------------------------------------------------------------------
|
| At release every ichava repository becomes one root commit tagged v0.1.0, so
| 0.1.0 is the only version of any sibling that exists. A constraint that
| excludes it, such as a floor raised to ^0.1.2, cannot resolve once released.
|
| This package requires no ichava/* package itself, so the constraint that
| matters is the one stubs/composer.json.stub gives every generated pack. It is
| read from a RENDERED pack, after token substitution, rather than from the
| stub, together with this package's own composer.json.
|
*/

/**
 * @param array<string, mixed> $composer
 *
 * @return array<string, string> "label.section.package" => constraint
 */
function scaffolderSiblingConstraints(string $label, array $composer): array
{
    $found = [];
    foreach (['require', 'require-dev'] as $section) {
        foreach ($composer[$section] ?? [] as $package => $constraint) {
            if (str_starts_with((string) $package, 'ichava/')) {
                $found["{$label}.{$section}.{$package}"] = (string) $constraint;
            }
        }
    }

    return $found;
}

it('admits 0.1.0 in every ichava/* constraint, its own and a scaffolded pack\'s', function (): void {
    $root = scratchDirectory('siblings');

    try {
        app(ScaffoldIconPackage::class)(
            PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'dev@example.com'),
            TargetPath::absolute($root),
            force: true,
        );

        $read = static fn (string $path): array => json_decode(
            (string) file_get_contents($path),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $constraints = [
            ...scaffolderSiblingConstraints('composer.json', $read(dirname(__DIR__, 2) . '/composer.json')),
            ...scaffolderSiblingConstraints('stubs/composer.json.stub', $read($root . '/composer.json')),
        ];
    } finally {
        removeDirectory($root);
    }

    $this->assertNotEmpty($constraints, 'no ichava/* constraint was found, so nothing was checked.');

    foreach ($constraints as $where => $constraint) {
        $this->assertTrue(
            Semver::satisfies('0.1.0', $constraint),
            "{$where} is '{$constraint}', which the released 0.1.0 does not satisfy.",
        );
    }
});
