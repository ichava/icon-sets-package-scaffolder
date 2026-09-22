<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconAxis;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconSetType;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/*
|--------------------------------------------------------------------------
| The taxonomy axis is separate from the filesystem layout
|--------------------------------------------------------------------------
|
| `IconSetType` answers "one SVG directory or one per variant?". `IconAxis`
| answers "is this pack organised by variant or by category?". The generator
| had only the first and assumed the answer to the second, which is why it
| could emit only variant packs while three of the five real packs --
| bundled, metronic and emoji -- are category packs.
|
| They are orthogonal, and the estate proves it: bundled is a category pack
| whose SVGs sit in per-set directories.
|
*/

it('names the enum class, the docs page and the translation groups', function () {
    expect(IconAxis::Variant->studly())->toBe('Variant')
        ->and(IconAxis::Variant->plural())->toBe('variants')
        ->and(IconAxis::Variant->descriptionsKey())->toBe('variant_descriptions')
        ->and(IconAxis::Category->studly())->toBe('Category')
        ->and(IconAxis::Category->plural())->toBe('categories')
        ->and(IconAxis::Category->descriptionsKey())->toBe('category_descriptions');
});

it('pluralises category correctly, which a bare s does not', function () {
    // `category` + `s` is `categorys`. Spelled out rather than derived: two
    // literals are a fact, a general pluraliser is a dependency and a guess.
    expect(IconAxis::Category->plural())->not->toBe('categorys');
});

it('rejects an axis it does not have, by name', function () {
    expect(fn () => IconAxis::fromInput('set'))
        ->toThrow(InvalidPackageDefinition::class, 'Unknown icon axis "set"');
});

it('defaults to variant, and that is a decision', function () {
    // Changing this silently renames the enum class, the docs page and the
    // translation groups of every pack scaffolded afterwards.
    expect(PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'd@e.com')->axis)
        ->toBe(IconAxis::Variant);
});

it('accepts an axis independently of the layout type', function () {
    // The orthogonality, asserted rather than asserted-about: a category pack
    // with per-variant directories is exactly `icon-sets-bundled`.
    $definition = PackageDefinition::create(
        name: 'Beacon',
        vendor: 'Acme',
        email: 'd@e.com',
        type: IconSetType::Multi,
        variants: ['outline', 'filled'],
        axis: 'category',
    );

    expect($definition->axis)->toBe(IconAxis::Category)
        ->and($definition->type)->toBe(IconSetType::Multi)
        ->and($definition->variants)->toHaveCount(2);
});
