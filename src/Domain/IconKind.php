<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain;

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * What a pack's SVGs are: icons or illustrations.
 *
 * Written into the generated `config.json` as `package.kind`, the pack-wide
 * default ichava/core resolves for every category that declares none of its
 * own. The values are core's `Enums\IconKind` values; this package does not
 * depend on core, so it carries its own two cases rather than importing them.
 *
 * Kind is independent of {@see IconSetType} (directory layout) and
 * {@see IconAxis} (taxonomy), and of colour: emoji and flags are multicolour
 * icons, not illustrations.
 */
enum IconKind: string
{
    case Icon = 'icon';
    case Illustration = 'illustration';

    public static function fromInput(string $value): self
    {
        return self::tryFrom(mb_strtolower(trim($value)))
            ?? throw InvalidPackageDefinition::unknownIconKind($value);
    }
}
