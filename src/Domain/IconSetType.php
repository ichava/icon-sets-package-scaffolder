<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain;

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * Whether a pack ships one flat icon set or several named variants.
 *
 * A plain backed enum on purpose. `laranail/enumerator` is not used here: this
 * has two cases and no behaviour beyond the directory layout it implies, and
 * the packs' own `Variant` enums answer to core's `IconSetVariantInterface`,
 * which is a domain contract core owns rather than a general enum concern.
 */
enum IconSetType: string
{
    case Single = 'single';
    case Multi = 'multi';

    public static function fromInput(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw InvalidPackageDefinition::unknownIconSetType($value);
    }

    /**
     * Multi-variant packs get one directory per variant under
     * `resources/assets/svg/files/`; single-set packs get the one directory.
     */
    public function hasVariantDirectories(): bool
    {
        return $this === self::Multi;
    }
}
