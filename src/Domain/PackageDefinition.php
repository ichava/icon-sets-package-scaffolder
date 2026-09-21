<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain;

use Illuminate\Support\Str;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * Everything the scaffolder needs to know, validated once, on construction.
 *
 * This is the seam the package is built around. Generation used to be
 * reachable only through `handle()`, so exercising it meant driving interactive
 * prompts; with a definition as an argument, every generating class below is
 * callable from a test in one line.
 *
 * Derived names live here rather than in the token map because they are facts
 * about the package, not about the stub tree -- `$definition->packageName()` is
 * the same string whether it is being written into a `composer.json`, a
 * namespace, or a log line.
 */
final readonly class PackageDefinition
{
    /** @param list<IconVariant> $variants */
    private function __construct(
        public string $studlyName,
        public string $kebabName,
        public string $vendor,
        public string $email,
        public string $prefix,
        public IconSetType $type,
        public array $variants,
    ) {}

    /**
     * @param list<string> $variants raw variant names; ignored for single-set packs
     */
    public static function create(
        string $name,
        string $vendor,
        string $email,
        ?string $prefix = null,
        string|IconSetType $type = IconSetType::Single,
        array $variants = [],
    ): self {
        $name = trim($name);
        $vendor = trim($vendor);
        $email = trim($email);

        if ($name === '') {
            throw InvalidPackageDefinition::emptyName();
        }

        if (mb_strlen($name) < 2) {
            throw InvalidPackageDefinition::nameTooShort($name);
        }

        if ($vendor === '') {
            throw InvalidPackageDefinition::emptyVendor();
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw InvalidPackageDefinition::invalidEmail($email);
        }

        $type = $type instanceof IconSetType ? $type : IconSetType::fromInput($type);
        $kebabName = Str::kebab($name);

        return new self(
            studlyName: Str::studly($name),
            kebabName: $kebabName,
            vendor: $vendor,
            email: $email,
            // An empty prefix falls back to the kebab name rather than producing
            // `<x--icon />`, which is what an empty `--prefix=` used to emit.
            prefix: trim((string) $prefix) !== '' ? trim((string) $prefix) : $kebabName,
            type: $type,
            variants: $type->hasVariantDirectories() ? self::buildVariants($variants) : [],
        );
    }

    /** `acme/hero-icons` -- the composer name. */
    public function packageName(): string
    {
        return $this->vendorKebab() . '/' . $this->bladeNamespace();
    }

    /** `hero-icons` -- the Blade component namespace and the pack's short name. */
    public function bladeNamespace(): string
    {
        return $this->kebabName . '-icons';
    }

    public function vendorKebab(): string
    {
        return Str::kebab($this->vendor);
    }

    public function vendorStudly(): string
    {
        return Str::studly($this->vendor);
    }

    /**
     * `Acme\HeroIcons` -- the PSR-4 root of the generated package.
     *
     * The `Icons` suffix is appended here rather than expected in the typed
     * name, which is why the convention is to type `Hero`, not `HeroIcons`:
     * the latter yields `HeroIconsIcons` and the composer name
     * `acme/hero-icons-icons`. That is inherited behaviour, pinned by a test
     * below, and is tracked separately -- changing it silently would rename
     * every class in a pack scaffolded before the change.
     */
    public function namespace(): string
    {
        return $this->vendorStudly() . '\\' . $this->studlyName . 'Icons';
    }

    /** `Hero Icons` -- for descriptions and headings. */
    public function humanName(): string
    {
        return ucwords(str_replace('-', ' ', $this->kebabName));
    }

    /** `ichava::hero-icons.update` -- `V59`, never a bare `ichava:` name. */
    public function updateCommandName(): string
    {
        return 'ichava::' . $this->bladeNamespace() . '.update';
    }

    /** The `variants` map `core`'s JsonConfigConstants reads, already ordered. */
    /** @return array<string, array<string, scalar|object>> */
    public function variantsConfig(): array
    {
        $config = [];

        foreach ($this->variants as $variant) {
            $config[$variant->slug] = $variant->toConfigArray();
        }

        return $config;
    }

    /**
     * @param list<string> $raw
     *
     * @return list<IconVariant>
     */
    private static function buildVariants(array $raw): array
    {
        $raw = array_values(array_filter(array_map('trim', $raw), static fn (string $v): bool => $v !== ''));

        if ($raw === []) {
            throw InvalidPackageDefinition::multiSetWithoutVariants();
        }

        $variants = [];
        $seen = [];

        foreach ($raw as $index => $name) {
            $variant = IconVariant::fromInput($name, $index + 1);

            // Two spellings can collapse to one slug ("Solid" and "solid"), and a
            // duplicate slug would silently overwrite the earlier variant's entry
            // in the config map, losing it without an error.
            if (isset($seen[$variant->slug])) {
                throw InvalidPackageDefinition::duplicateVariant($variant->slug);
            }

            $seen[$variant->slug] = true;
            $variants[] = $variant;
        }

        return $variants;
    }
}
