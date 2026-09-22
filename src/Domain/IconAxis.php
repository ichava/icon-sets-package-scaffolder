<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain;

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * How a pack subdivides its icons: by variant, or by category.
 *
 * Deliberately separate from {@see IconSetType}, and the separation is the
 * point. `IconSetType` answers a filesystem question -- one SVG directory, or
 * one per variant. This answers a taxonomy question -- what the subdivision
 * *means*, which determines the enum class, the docs page and the translation
 * groups a pack ships.
 *
 * They are orthogonal, and the estate proves it: `icon-sets-bundled` is a
 * category pack whose SVGs sit in per-set directories, and `icon-sets-flag` is
 * a variant pack with two. Folding them into one four-case enum makes the two
 * questions look like one, which is the conflation that left this generator
 * able to emit only variant packs while three of the five real packs are
 * category packs.
 */
enum IconAxis: string
{
    case Variant = 'variant';
    case Category = 'category';

    public static function fromInput(string $value): self
    {
        return self::tryFrom(mb_strtolower(trim($value)))
            ?? throw InvalidPackageDefinition::unknownIconAxis($value);
    }

    /** `Variant` / `Category` -- the enum class a pack ships. */
    public function studly(): string
    {
        return ucfirst($this->value);
    }

    /**
     * `variants` / `categories` -- the translation group, and the docs page.
     *
     * Spelled out rather than derived: `category` + `s` is `categorys`, and a
     * general pluraliser is a dependency and a guess where two literals are a
     * fact.
     */
    public function plural(): string
    {
        return match ($this) {
            self::Variant  => 'variants',
            self::Category => 'categories',
        };
    }

    /** `Variants` / `Categories` -- prose and headings. */
    public function studlyPlural(): string
    {
        return ucfirst($this->plural());
    }

    /** `variant_descriptions` / `category_descriptions` -- the sibling group. */
    public function descriptionsKey(): string
    {
        return $this->value . '_descriptions';
    }
}
