<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console;

use RuntimeException;

use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;

/**
 * Turns whatever the caller gave for a destination into a usable directory.
 *
 * Separate from `PackageDefinitionPrompter` because it answers a different
 * question and, unlike that class, it writes: creating a missing parent is a
 * side effect, and side effects belong somewhere a reader expects them.
 *
 * `TargetPath` does the string work. What is left here is the part that needs a
 * human -- asking for the path, and asking before creating a parent directory
 * the user probably mistyped.
 */
final readonly class DestinationResolver
{
    public function __construct(private Filesystem $files) {}

    public function resolve(
        PackageDefinition $definition,
        ?string $supplied,
        bool $force,
        bool $interactive = true,
    ): TargetPath {
        $suggestion = $this->suggestion($definition);

        if (($supplied === null || trim($supplied) === '') && ! $interactive) {
            throw new RuntimeException(Messages::get('destination.required_headless'));
        }

        if ($supplied === null || trim($supplied) === '') {
            $supplied = text(
                label: Messages::get('destination.label'),
                placeholder: $suggestion,
                default: $this->basePath() . DIRECTORY_SEPARATOR . $suggestion,
                required: Messages::get('destination.required'),
                hint: Messages::get('destination.hint', ['suggestion' => $suggestion]),
            );
        }

        $path = TargetPath::resolve($supplied, $this->basePath());

        $this->ensureParent($path, $force, $interactive);

        return $path;
    }

    /**
     * The `<vendor>-<name>-icons` convention, e.g. `ichava-flag-icons`.
     *
     * Derived from the definition rather than hardcoded to `ichava-`, so a
     * third party scaffolding their own pack is suggested their own vendor.
     */
    private function suggestion(PackageDefinition $definition): string
    {
        return sprintf('%s-%s-icons', $definition->vendorKebab(), $definition->kebabName);
    }

    private function ensureParent(TargetPath $path, bool $force, bool $interactive): void
    {
        $parent = $path->parent();

        if (! $this->files->isDirectory($parent)) {
            // A non-interactive caller passed the path explicitly, which is the
            // consent the prompt would have been asking for.
            $create = $force || ! $interactive || confirm(
                label: Messages::get('destination.create_parent', ['parent' => $parent]),
                default: true,
            );

            if (! $create) {
                throw new RuntimeException(Messages::get('destination.no_parent'));
            }

            $this->files->ensureDirectoryExists($parent);
        }

        if (! $this->files->isWritable($parent)) {
            throw new RuntimeException(Messages::get('destination.not_writable', ['parent' => $parent]));
        }
    }

    /**
     * The host application's root, when there is one.
     *
     * This package is usable outside Laravel -- `base_path()` is only defined
     * once the framework has booted -- so a plain cwd fallback keeps a
     * standalone or test invocation working.
     */
    private function basePath(): string
    {
        return function_exists('base_path') && app()->bound('path.base')
            ? base_path()
            : (getcwd() ?: '.');
    }
}
