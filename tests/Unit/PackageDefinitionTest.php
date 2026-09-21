<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconSetType;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * The validation these tests pin used to live in `validate:` closures on the
 * prompts, so it ran only when a human typed the answer. Every option-driven
 * run -- CI, `--force`, a test -- enforced nothing. Asserting on the object is
 * what makes that impossible to regress.
 */
function definition(array $overrides = []): PackageDefinition
{
    return PackageDefinition::create(...array_merge([
        'name'   => 'HeroIcons',
        'vendor' => 'Acme',
        'email'  => 'dev@example.com',
    ], $overrides));
}

it('derives every public name from the package name and vendor', function (): void {
    $definition = definition(['name' => 'Hero']);

    expect($definition->studlyName)->toBe('Hero')
        ->and($definition->kebabName)->toBe('hero')
        ->and($definition->packageName())->toBe('acme/icon-sets-hero')
        ->and($definition->bladeNamespace())->toBe('icon-sets-hero')
        ->and($definition->vendorKebab())->toBe('acme')
        ->and($definition->vendorStudly())->toBe('Acme')
        ->and($definition->namespace())->toBe('Acme\\IconSetsHero')
        ->and($definition->humanName())->toBe('Hero');
});

it('still doubles a name that already carries an Icons suffix', function (): void {
    // Inherited behaviour, pinned rather than endorsed. The convention is to
    // type the bare noun: `Hero`, not `HeroIcons`. The `icon-sets-` prefix is
    // idempotent (see EstateNamingParityTest); this trailing `Icons` is a
    // different string and is not, which is deliberate -- silently stripping a
    // word the author typed is worse than echoing it back.
    $definition = definition(['name' => 'HeroIcons']);

    expect($definition->namespace())->toBe('Acme\\IconSetsHeroIcons')
        ->and($definition->packageName())->toBe('acme/icon-sets-hero-icons');
});

it('namespaces the update command with the vendor and the slug', function (): void {
    // V59: a bare `icons:update` is a generic key in Artisan's flat map, and a
    // second package claiming it replaces the first silently.
    expect(definition(['name' => 'Hero'])->updateCommandName())
        ->toBe('ichava::icon-sets-hero.update')
        ->toMatch('/^[a-z0-9-]+::[a-z0-9-]+\./');
});

it('falls back to the kebab name when no prefix is given', function (): void {
    expect(definition(['name' => 'Hero'])->prefix)->toBe('hero')
        ->and(definition(['name' => 'Hero', 'prefix' => ''])->prefix)->toBe('hero')
        ->and(definition(['name' => 'Hero', 'prefix' => 'hero-ui'])->prefix)->toBe('hero-ui');
});

it('rejects input that would produce a broken package', function (array $overrides): void {
    definition($overrides);
})->throws(InvalidPackageDefinition::class)->with([
    'empty name'         => [['name' => '   ']],
    'one-character name' => [['name' => 'X']],
    'empty vendor'       => [['vendor' => '']],
    'malformed email'    => [['email' => 'not-an-email']],
    'unknown type'       => [['type' => 'triple']],
]);

it('refuses a multi-variant pack with no variants', function (): void {
    definition(['type' => 'multi', 'variants' => []]);
})->throws(InvalidPackageDefinition::class);

it('refuses duplicate variants', function (): void {
    // "Outline" and "outline" slug identically, so the second would overwrite
    // the first in config.json and the pack would ship one fewer variant than
    // the author asked for.
    definition(['type' => 'multi', 'variants' => ['Outline', 'outline']]);
})->throws(InvalidPackageDefinition::class);

it('ignores variants on a single-set pack', function (): void {
    $definition = definition(['type' => 'single', 'variants' => ['outline', 'solid']]);

    expect($definition->type)->toBe(IconSetType::Single)
        ->and($definition->variants)->toBe([]);
});

it('marks only the first variant as default and suffixes the rest', function (): void {
    $definition = definition(['type' => 'multi', 'variants' => ['outline', 'solid', 'duotone']]);

    expect($definition->variants)->toHaveCount(3)
        ->and($definition->variants[0]->isDefault)->toBeTrue()
        ->and($definition->variants[0]->iconSuffix())->toBe('')
        ->and($definition->variants[1]->isDefault)->toBeFalse()
        ->and($definition->variants[1]->iconSuffix())->toBe('-solid')
        ->and($definition->variants[2]->displayOrder)->toBe(3);
});
