<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console;

use Illuminate\Support\Str;

use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconAxis;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconSetType;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;

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
            label: Messages::get('prompt.name.label'),
            placeholder: Messages::get('prompt.name.placeholder'),
            required: Messages::get('prompt.name.required'),
            hint: Messages::get('prompt.name.hint'),
        ));

        $vendor = $this->filled($supplied, 'vendor') ?? $this->ask($interactive, fn (): string => text(
            label: Messages::get('prompt.vendor.label'),
            placeholder: Messages::get('prompt.vendor.placeholder'),
            default: 'YourVendor',
            required: Messages::get('prompt.vendor.required'),
            hint: Messages::get('prompt.vendor.hint'),
        ));

        $email = $this->filled($supplied, 'email') ?? $this->ask($interactive, fn (): string => text(
            label: Messages::get('prompt.email.label'),
            placeholder: Messages::get('prompt.email.placeholder'),
            required: Messages::get('prompt.email.required'),
            hint: Messages::get('prompt.email.hint'),
        ));

        $prefix = $this->filled($supplied, 'prefix') ?? $this->ask($interactive, fn (): string => text(
            label: Messages::get('prompt.prefix.label'),
            placeholder: Str::kebab((string) $name),
            default: Str::kebab((string) $name),
            hint: Messages::get('prompt.prefix.hint'),
        ));

        $type = $this->filled($supplied, 'type') ?? $this->ask($interactive, fn (): string => select(
            label: Messages::get('prompt.type.label'),
            options: [
                IconSetType::Single->value => Messages::get('prompt.type.single'),
                IconSetType::Multi->value  => Messages::get('prompt.type.multi'),
            ],
            default: IconSetType::Single->value,
        ), IconSetType::Single->value);

        // Asked separately from the type above, because the two are orthogonal:
        // `icon-sets-bundled` is a category pack whose SVGs sit in per-set
        // directories. One prompt for both would force a false choice.
        $axis = $this->filled($supplied, 'axis') ?? $this->ask($interactive, fn (): string => select(
            label: Messages::get('prompt.axis.label'),
            options: [
                IconAxis::Variant->value  => Messages::get('prompt.axis.variant'),
                IconAxis::Category->value => Messages::get('prompt.axis.category'),
            ],
            default: IconAxis::Variant->value,
        ), IconAxis::Variant->value);

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
            axis: (string) $axis,
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
            label: Messages::get('prompt.variants.label'),
            placeholder: Messages::get('prompt.variants.placeholder'),
            default: 'outline, solid',
            required: Messages::get('prompt.variants.required'),
            hint: Messages::get('prompt.variants.hint'),
        );

        $variants = $this->split($answer);

        // Stated rather than left to be discovered when the default icons do
        // not resolve: the first variant owns the unsuffixed filenames.
        confirm(
            label: Messages::get('prompt.variants.confirm_default', ['variant' => $variants[0] ?? '']),
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
