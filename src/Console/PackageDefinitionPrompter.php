<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Console;

use Illuminate\Support\Str;

use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;

use Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain\IconSetType;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain\PackageDefinition;

/**
 * Asks for whatever the caller did not supply, then builds a definition.
 *
 * The only class in this package that imports a prompt library, so the rest
 * stays usable headlessly and the library is swappable in one file. Core has
 * ten files importing `Laravel\Prompts` directly; concentrating it here is the
 * shape that avoids repeating that.
 *
 * Validation is deliberately NOT here. It lives on `PackageDefinition`, so the
 * rules hold whichever way the command is driven -- previously they were
 * closures in `validate:` arguments, which meant a run driven by options (every
 * test, every CI invocation, every `--force` scaffold) enforced nothing.
 */
final readonly class PackageDefinitionPrompter
{
    /**
     * @param array<string, mixed> $supplied values already known from arguments/options
     * @param bool $interactive false when there is nobody to ask
     */
    public function prompt(array $supplied, bool $interactive = true): PackageDefinition
    {
        $name = $this->filled($supplied, 'name') ?? $this->ask($interactive, fn (): string => text(
            label: 'Package name',
            placeholder: 'e.g. Hero, FontAwesome, Feather',
            required: 'Package name is required',
            hint: 'Type the bare noun -- `Hero`, not `HeroIcons`; the Icons suffix is added for you.',
        ));

        $vendor = $this->filled($supplied, 'vendor') ?? $this->ask($interactive, fn (): string => text(
            label: 'Vendor name',
            placeholder: 'e.g. YourCompany, MyOrg',
            default: 'YourVendor',
            required: 'Vendor name is required',
            hint: 'Used in the namespace and the composer package name',
        ));

        $email = $this->filled($supplied, 'email') ?? $this->ask($interactive, fn (): string => text(
            label: 'Author email address',
            placeholder: 'you@example.com',
            required: 'Email address is required',
            hint: 'Used in the composer.json author field',
        ));

        $prefix = $this->filled($supplied, 'prefix') ?? $this->ask($interactive, fn (): string => text(
            label: 'Blade component prefix',
            placeholder: Str::kebab((string) $name),
            default: Str::kebab((string) $name),
            hint: 'Used for Blade components: <x-{prefix}-icon name="..." />',
        ));

        $type = $this->filled($supplied, 'type') ?? $this->ask($interactive, fn (): string => select(
            label: 'Icon set type',
            options: [
                IconSetType::Single->value => 'Single set (one flat collection of icons)',
                IconSetType::Multi->value  => 'Multi variant (outline, solid, duotone, ...)',
            ],
            default: IconSetType::Single->value,
        ), IconSetType::Single->value);

        $variants = $this->variants($supplied, IconSetType::fromInput((string) $type), $interactive);

        // A missing required answer arrives here as an empty string rather than
        // a prompt, so the failure is `InvalidPackageDefinition` naming the
        // field -- which is a better answer for a scripted run than a process
        // that blocks forever on a TTY that is not there.
        return PackageDefinition::create(
            name: (string) $name,
            vendor: (string) $vendor,
            email: (string) $email,
            prefix: $prefix === null ? null : (string) $prefix,
            type: (string) $type,
            variants: $variants,
        );
    }

    /**
     * @param array<string, mixed> $supplied
     *
     * @return list<string>
     */
    private function variants(array $supplied, IconSetType $type, bool $interactive): array
    {
        if (! $type->hasVariantDirectories()) {
            return [];
        }

        $supplied = $supplied['variants'] ?? null;

        if (is_array($supplied) && $supplied !== []) {
            return array_values(array_map('strval', $supplied));
        }

        if (is_string($supplied) && trim($supplied) !== '') {
            return $this->split($supplied);
        }

        if (! $interactive) {
            // An empty list against a multi-variant type is refused by
            // PackageDefinition, which is the answer a script should get.
            return [];
        }

        $answer = text(
            label: 'Variants',
            placeholder: 'outline, solid, duotone',
            default: 'outline, solid',
            required: 'A multi-variant package needs at least one variant',
            hint: 'Comma separated. The first is the default, and its icon files carry no suffix.',
        );

        $variants = $this->split($answer);

        // Stated rather than left to be discovered when the default icons do
        // not resolve: the first variant owns the unsuffixed filenames.
        confirm(
            label: sprintf('"%s" will be the default variant. Continue?', $variants[0] ?? ''),
            default: true,
        ) || exit(1);

        return $variants;
    }

    /** @return list<string> */
    private function split(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $v): bool => $v !== '',
        ));
    }

    /**
     * Run a prompt, or fall back when there is no one at the keyboard.
     *
     * Laravel Prompts blocks on a TTY that a queued job, a CI step or a test
     * does not have. Deciding up front is better than discovering it as a hang.
     *
     * @param callable(): string $prompt
     */
    private function ask(bool $interactive, callable $prompt, ?string $fallback = null): ?string
    {
        return $interactive ? $prompt() : $fallback;
    }

    /** @param array<string, mixed> $supplied */
    private function filled(array $supplied, string $key): mixed
    {
        $value = $supplied[$key] ?? null;

        return is_string($value) && trim($value) === '' ? null : $value;
    }
}
