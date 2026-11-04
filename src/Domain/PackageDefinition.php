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
    /** The estate-wide slug prefix every icon pack carries. */
    private const string SLUG_PREFIX = 'icon-sets-';

    /**
     * PSR-4 roots that differ from the studly vendor, keyed by kebab vendor.
     * Every real ichava pack is `Simtabi\Laranail\Ichava\IconSets<Slug>`.
     */
    private const array NAMESPACE_ROOTS = [
        'ichava' => 'Simtabi\\Laranail\\Ichava',
    ];

    /**
     * @param list<IconVariant> $variants
     * @param list<KindRule> $kindRules
     */
    private function __construct(
        public string $studlyName,
        public string $kebabName,
        public string $vendor,
        public string $email,
        public string $prefix,
        public IconSetType $type,
        public array $variants,
        public IconAxis $axis = IconAxis::Variant,
        public IconKind $kind = IconKind::Icon,
        public array $kindRules = [],
    ) {}

    /**
     * @param list<string> $variants raw variant names; ignored for single-set packs
     *
     * `$axis` defaults to Variant so every existing caller keeps its meaning.
     * That default is a decision, not an accident -- it is pinned by a test,
     * because silently changing it would rename the enum class, the docs page
     * and the translation groups of every pack scaffolded afterwards.
     *
     * `$kind` defaults to Icon for the same reason: every pack in the estate
     * declares `icon`, and a caller that predates kinds must keep getting it.
     *
     * `$kindRules` are `kinds.rules` entries, as `<glob>=<kind>` strings or
     * {@see KindRule}s, in the order the first match should win. Empty by
     * default, which renders the empty list the stub always carried.
     * @param list<string|KindRule> $kindRules
     */
    public static function create(
        string $name,
        string $vendor,
        string $email,
        ?string $prefix = null,
        string|IconSetType $type = IconSetType::Single,
        array $variants = [],
        string|IconAxis $axis = IconAxis::Variant,
        string|IconKind $kind = IconKind::Icon,
        array $kindRules = [],
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
            axis: is_string($axis) ? IconAxis::fromInput($axis) : $axis,
            kind: is_string($kind) ? IconKind::fromInput($kind) : $kind,
            kindRules: array_values(array_map(
                static fn (string|KindRule $rule): KindRule => $rule instanceof KindRule ? $rule : KindRule::fromInput($rule),
                $kindRules,
            )),
        );
    }

    /** `acme/hero-icons` -- the composer name. */
    public function packageName(): string
    {
        return $this->vendorKebab() . '/' . $this->bladeNamespace();
    }

    /**
     * `icon-sets-hero` -- the Blade component namespace and the pack's short name.
     *
     * The estate renamed to the `icon-sets-` PREFIX on 2026-09-21: directory,
     * GitHub repository and composer name moved together. This used to append
     * an `-icons` SUFFIX, so a pack scaffolded after the rename got a composer
     * name, config key, component tag and Artisan command matching nothing
     * that exists. Measured against the real packs: `ichava/icon-sets-flag`,
     * `config/icon-sets-flag.php`, `<x-icon-sets-flag-icon>`.
     *
     * Idempotent, because `emoji-sets` became `icon-sets-emoji` rather than
     * `icon-sets-emoji-sets`: someone typing the full slug must not get
     * `icon-sets-icon-sets-flag`.
     *
     * This is the one place the name is derived. Stubs reference it through
     * the `{{bladeNamespace}}` token -- twenty-one sites used to rebuild it by
     * hand as `{{kebabName}}-icons`, which is why the rebrand reached the
     * packages and not what the scaffolder emits.
     */
    public function bladeNamespace(): string
    {
        return str_starts_with($this->kebabName, self::SLUG_PREFIX)
            ? $this->kebabName
            : self::SLUG_PREFIX . $this->kebabName;
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
     * `Acme\IconSetsHero` -- the PSR-4 root of the generated package.
     *
     * Derived from bladeNamespace() so the class root and the composer name
     * cannot drift apart. The real packs are
     * `Simtabi\Laranail\Ichava\IconSetsFlag`, studly of the same slug, so the
     * `ichava` vendor takes the estate root; every other vendor keeps
     * `<Vendor>\<Pack>`, as before.
     */
    public function namespace(): string
    {
        $root = self::NAMESPACE_ROOTS[$this->vendorKebab()] ?? $this->vendorStudly();

        return $root . '\\' . Str::studly($this->bladeNamespace());
    }

    /** `Hero Icons` -- for descriptions and headings. */
    public function humanName(): string
    {
        return ucwords(str_replace('-', ' ', $this->kebabName));
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
     * The `kinds.rules` list core's `PackConfigData::kindRules()` reads, in order.
     *
     * @return list<array{match: string, kind: string}>
     */
    public function kindRulesConfig(): array
    {
        return array_map(static fn (KindRule $rule): array => $rule->toConfigArray(), $this->kindRules);
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
