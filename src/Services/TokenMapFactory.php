<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services;

use Illuminate\Support\Str;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconAxis;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;

/**
 * Turns a definition into the `{{token}} => value` map the stubs substitute.
 *
 * Every derived name it does not own is asked of the definition, so
 * `packageName()` has one implementation rather than one here and another
 * wherever else a package name is needed. What this class owns is the mapping
 * from domain concept to stub token -- which is a property of the stub tree,
 * not of the package.
 */
final readonly class TokenMapFactory
{
    /** @return array<string, string> */
    public function for(PackageDefinition $definition): array
    {
        return [
            '{{vendor}}'       => $definition->vendor,
            '{{vendorStudly}}' => $definition->vendorStudly(),
            '{{vendorLower}}'  => Str::lower($definition->vendor),
            '{{vendorKebab}}'  => $definition->vendorKebab(),
            '{{vendorSnake}}'  => Str::snake($definition->vendor),

            '{{studlyName}}'     => $definition->studlyName,
            '{{camelName}}'      => Str::camel($definition->studlyName),
            '{{kebabName}}'      => $definition->kebabName,
            '{{snakeName}}'      => Str::snake($definition->studlyName),
            '{{snakeNameUpper}}' => Str::upper(Str::snake($definition->studlyName)),
            '{{humanName}}'      => $definition->humanName(),

            '{{namespace}}' => $definition->namespace(),
            // PHP source stubs sit inside a double-quoted context in places, and
            // JSON needs every separator escaped, so the escaped spelling is a
            // distinct token rather than something each stub re-escapes.
            '{{namespaceEscaped}}' => str_replace('\\', '\\\\', $definition->namespace()),
            '{{packageName}}'      => $definition->packageName(),
            '{{bladeNamespace}}'   => $definition->bladeNamespace(),

            '{{prefix}}'      => $definition->prefix,
            '{{email}}'       => $definition->email,
            '{{iconSetType}}' => $definition->type->value,

            // The taxonomy axis, distinct from iconSetType above: that one is
            // filesystem layout, these name the enum class, the docs page and
            // the translation groups a pack ships.
            '{{axis}}'             => $definition->axis->value,
            '{{axisStudly}}'       => $definition->axis->studly(),
            '{{axisPlural}}'       => $definition->axis->plural(),
            '{{axisStudlyPlural}}' => $definition->axis->studlyPlural(),
            '{{axisDescriptions}}' => $definition->axis->descriptionsKey(),
            '{{variantsJson}}'     => $this->axisJson($definition, IconAxis::Variant),
            '{{categoriesJson}}'   => $this->axisJson($definition, IconAxis::Category),

            '{{year}}' => date('Y'),
            '{{date}}' => date('Y-m-d'),
        ];
    }

    /**
     * One `metadata.data` block for `resources/assets/svg/config.json`.
     *
     * Both `variants` and `categories` are always emitted, because every pack
     * in the estate ships both and `JsonConfigConstants` reads each by its
     * literal name -- `getVariants()` would return an empty array against a
     * config that renamed the key to match its axis. The axis decides which
     * of the two carries the values; the other stays empty.
     *
     * `{}` for the empty one, not `[]`: the consumer reads this as a map, and
     * an empty PHP array encodes to a JSON array, which is the wrong type.
     */
    private function axisJson(PackageDefinition $definition, IconAxis $slot): string
    {
        $config = $definition->axis === $slot ? $definition->variantsConfig() : [];

        if ($config === []) {
            return '{}';
        }

        return (string) json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
