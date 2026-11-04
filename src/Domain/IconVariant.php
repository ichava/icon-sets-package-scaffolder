<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain;

use Illuminate\Support\Str;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * One named icon style within a multi-variant pack -- "outline", "solid", "duotone".
 *
 * Carries its own position because the schema `core`'s `JsonConfigConstants`
 * reads treats the first variant as the default and derives `icon_suffix` from
 * that: the default variant's files are unsuffixed, every other variant's carry
 * `-<slug>`. Getting that wrong produces a pack whose default icons resolve to
 * nothing, so the rule lives on the object rather than in the loop that built
 * the JSON.
 *
 * `Str` is the one Illuminate import in `Domain/`. It is a pure string helper
 * with no container, config or I/O behind it, and reimplementing `slug()` and
 * `title()` to claim purity would be worse code for a rule nobody enforces.
 */
final readonly class IconVariant
{
    private function __construct(
        public string $slug,
        public string $label,
        public string $description,
        public int $displayOrder,
        public bool $isDefault,
    ) {}

    public static function fromInput(string $input, int $displayOrder): self
    {
        $trimmed = trim($input);

        if ($trimmed === '') {
            throw InvalidPackageDefinition::emptyVariant();
        }

        $label = Str::title($trimmed);

        return new self(
            slug: Str::slug($trimmed),
            label: $label,
            description: $label . ' icon style',
            displayOrder: $displayOrder,
            isDefault: $displayOrder === 1,
        );
    }

    /**
     * The suffix this variant's icon files carry. Empty for the default, so
     * `home.svg` belongs to it and `home-solid.svg` to the "solid" variant.
     */
    public function iconSuffix(): string
    {
        return $this->isDefault ? '' : '-' . $this->slug;
    }

    /**
     * The shape `core`'s `JsonConfigConstants::getDefaultVariant()` understands.
     *
     * `attributes` is an object rather than an array so it survives a
     * `json_encode` round trip as `{}` and not `[]`; the consumer reads it as a
     * map, and an empty PHP array encodes to the wrong JSON type.
     */
    /** @return array<string, scalar|object> */
    public function toConfigArray(): array
    {
        return [
            'name'          => $this->label,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'default'       => $this->isDefault,
            'icon_suffix'   => $this->iconSuffix(),
            'display_order' => $this->displayOrder,
            'attributes'    => (object) [],
            'preview_icon'  => 'home',
            'color_scheme'  => 'adaptive',
        ];
    }
}
