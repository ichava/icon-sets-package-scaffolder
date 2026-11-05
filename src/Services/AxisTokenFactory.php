<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services;

use Illuminate\Support\Str;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconAxis;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconKind;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;

/**
 * The tokens that spell out a pack's axis cases in generated PHP.
 *
 * The enum, its labels and the tests that pin them all list the same cases, so
 * they are written from one list, {@see PackageDefinition::axisCases()}. Every
 * value here is concrete source text with no `{{token}}` inside it, because the
 * renderer substitutes tokens in one pass and would leave a nested one behind.
 *
 * The shapes are the estate packs': a flat pack is icon-sets-lucide (one
 * `DEFAULT` case at the `files/` root), a multi-set pack is icon-sets-tabler
 * and icon-sets-carbon (one case and one folder per variant).
 */
final readonly class AxisTokenFactory
{
    /** @return array<string, string> */
    public function for(PackageDefinition $definition): array
    {
        $cases = $definition->axisCases();
        $enum = $definition->axis->studly();
        $default = $cases[0]['case'];

        return [
            '{{axisCases}}' => implode("\n", array_map(
                static fn (array $case): string => sprintf("    case %s = '%s';", $case['case'], $case['value']),
                $cases,
            )),
            '{{axisDefaultCase}}'      => $default,
            '{{axisEnumSummary}}'      => $this->enumSummary($definition, $cases),
            '{{axisPathMethod}}'       => $this->pathMethod($definition),
            '{{axisLabels}}'           => $this->aligned(array_column($cases, 'label', 'value')),
            '{{axisDescriptionLines}}' => $this->aligned(array_column($cases, 'description', 'value')),
            '{{axisValuesPhp}}'        => '[' . implode(', ', array_map(
                static fn (array $case): string => "'" . $case['value'] . "'",
                $cases,
            )) . ']',
            '{{axisCaseValueTests}}'            => $this->caseValueTests($definition, $cases),
            '{{axisTryFromChain}}'              => $this->tryFromChain($enum, $cases),
            '{{axisDefaultVariantExpectation}}' => $definition->axis === IconAxis::Variant && ! $definition->isFlat()
                ? "->toBe('" . $cases[0]['value'] . "')"
                : '->toBeNull()',
            '{{axisClassExpectations}}' => $this->classExpectations($definition, $enum, $cases),
            '{{axisDefaultTest}}'       => $this->defaultTest($definition, $enum, $cases),
            '{{axisLayoutTest}}'        => $this->layoutTest($definition, $enum),
            '{{kindIllustrationSlugs}}' => $definition->kind === IconKind::Illustration
                ? 'array_keys(' . Str::snake($definition->studlyName) . '_resolved_kinds())'
                : '[]',
        ];
    }

    /** @param list<array{case: string, value: string, label: string, description: string}> $cases */
    private function enumSummary(PackageDefinition $definition, array $cases): string
    {
        $subject = sprintf('Type-safe %s selection for %s Icons', $definition->axis->value, $definition->studlyName);

        if ($definition->isFlat()) {
            return $subject . '. The pack ships one style, flat under `files/`.';
        }

        return $subject . ' (' . implode(' / ', array_column($cases, 'value')) . ').';
    }

    private function pathMethod(PackageDefinition $definition): string
    {
        if ($definition->isFlat()) {
            return <<<PHP
                /**
                 * The pack is flat: its icons sit directly under `files/`, so the one
                 * {$definition->axis->value} has no subfolder.
                 */
                public function getPath(): string
                {
                    return IconsConstants::getSvgPath();
                }
            PHP;
        }

        return <<<'PHP'
                public function getPath(): string
                {
                    return IconsConstants::getSvgPath($this->value);
                }
            PHP;
    }

    /**
     * `'outline' => 'Outline',` lines, with the arrows aligned as Pint aligns them.
     *
     * @param array<string, string> $entries
     */
    private function aligned(array $entries): string
    {
        $width = max(0, ...array_map(static fn (string $key): int => strlen($key) + 2, array_keys($entries)));
        $lines = [];

        foreach ($entries as $key => $value) {
            $lines[] = sprintf("        %-{$width}s => %s,", "'" . $key . "'", var_export($value, true));
        }

        return implode("\n", $lines);
    }

    /** @param list<array{case: string, value: string, label: string, description: string}> $cases */
    private function caseValueTests(PackageDefinition $definition, array $cases): string
    {
        $enum = $definition->axis->studly();
        $blocks = [];

        foreach ($cases as $case) {
            $what = $definition->isFlat() ? 'the one flat case value' : sprintf('the %s case value', $case['value']);
            $blocks[] = sprintf(
                "it(description: 'exposes %s', closure: function () {\n    expect(%s::%s->getValue())->toBe('%s');\n});\n",
                $what,
                $enum,
                $case['case'],
                $case['value'],
            );
        }

        return implode("\n", $blocks);
    }

    /** @param list<array{case: string, value: string, label: string, description: string}> $cases */
    private function tryFromChain(string $enum, array $cases): string
    {
        $parts = [];

        foreach ($cases as $index => $case) {
            $call = sprintf("%s::tryFromValue('%s'))->toBe(%s::%s)", $enum, $case['value'], $enum, $case['case']);
            $parts[] = $index === 0 ? $call : '        ->and(' . $call;
        }

        return implode("\n", $parts);
    }

    /** @param list<array{case: string, value: string, label: string, description: string}> $cases */
    private function classExpectations(PackageDefinition $definition, string $enum, array $cases): string
    {
        $parts = [];

        foreach ($cases as $index => $case) {
            $call = sprintf("%s::%s->getClass())->toBe('%s-%s')", $enum, $case['case'], $definition->prefix, $case['value']);
            $parts[] = $index === 0 ? $call : '        ->and(' . $call;
        }

        return implode("\n", $parts);
    }

    /** @param list<array{case: string, value: string, label: string, description: string}> $cases */
    private function defaultTest(PackageDefinition $definition, string $enum, array $cases): string
    {
        if ($definition->isFlat()) {
            return <<<PHP
            it(description: 'treats the one flat {$definition->axis->value} as the default, at the files root', closure: function () {
                expect({$enum}::default())->toBe({$enum}::DEFAULT)
                    ->and({$enum}::DEFAULT->isDefault())->toBeTrue()
                    ->and({$enum}::DEFAULT->getPath())->toBe(IconsConstants::getSvgPath())
                    ->and(is_dir({$enum}::DEFAULT->getPath()))->toBeTrue();
            });
            PHP;
        }

        $lines = [sprintf('    expect(%s::default())->toBe(%s::%s)', $enum, $enum, $cases[0]['case'])];

        foreach ($cases as $index => $case) {
            $lines[] = sprintf(
                '        ->and(%s::%s->isDefault())->%s()',
                $enum,
                $case['case'],
                $index === 0 ? 'toBeTrue' : 'toBeFalse',
            );
        }

        return sprintf(
            "it(description: 'matches the default %s to config json', closure: function () {\n%s;\n});",
            $definition->axis->value,
            implode("\n", $lines),
        );
    }

    private function layoutTest(PackageDefinition $definition, string $enum): string
    {
        if ($definition->isFlat()) {
            return <<<'PHP'
            it(description: 'ships every icon flat under files/', closure: function () {
                // Add the measured icon count once the icons are vendored, as every
                // estate pack does, so a silently shrunk or grown set fails.
                $files = dirname(__DIR__, 2) . '/resources/assets/svg/files';

                expect(glob($files . '/*', GLOB_ONLYDIR) ?: [])->toBe([], 'a flat pack has no folders under files/');
            });
            PHP;
        }

        $plural = $definition->axis->value;

        return <<<PHP
        it(description: 'ships one directory per {$plural}', closure: function () {
            // Add the measured icon count per directory once the icons are vendored,
            // as every estate pack does, so a silently shrunk or grown {$plural} fails.
            \$files = dirname(__DIR__, 2) . '/resources/assets/svg/files';
            \$actual = array_map('basename', glob(\$files . '/*', GLOB_ONLYDIR) ?: []);

            expect(\$actual)->toEqualCanonicalizing({$enum}::values());
        });
        PHP;
    }
}
