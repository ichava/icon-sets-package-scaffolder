<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services;

use Illuminate\Support\Str;
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

            '{{prefix}}'       => $definition->prefix,
            '{{email}}'        => $definition->email,
            '{{iconSetType}}'  => $definition->type->value,
            '{{variantsJson}}' => $this->variantsJson($definition),

            '{{year}}' => date('Y'),
            '{{date}}' => date('Y-m-d'),
        ];
    }

    /**
     * The `variants` block for `resources/assets/svg/config.json`.
     *
     * `{}` for a single-set pack, not `[]`: the consumer reads this as a map,
     * and an empty PHP array encodes to a JSON array, which is the wrong type.
     */
    private function variantsJson(PackageDefinition $definition): string
    {
        $config = $definition->variantsConfig();

        if ($config === []) {
            return '{}';
        }

        return (string) json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
