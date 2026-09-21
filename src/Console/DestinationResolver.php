<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Console;

use RuntimeException;

use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain\PackageDefinition;

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
            throw new RuntimeException('A destination path is required. Pass --path when running non-interactively.');
        }

        if ($supplied === null || trim($supplied) === '') {
            $supplied = text(
                label: 'Destination path for the icon package',
                placeholder: $suggestion,
                default: $this->basePath() . DIRECTORY_SEPARATOR . $suggestion,
                required: 'A destination path is required',
                hint: sprintf('Absolute, or relative to the project root. The ecosystem convention is `%s`.', $suggestion),
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
                label: sprintf('Parent directory "%s" does not exist. Create it?', $parent),
                default: true,
            );

            if (! $create) {
                throw new RuntimeException('Cannot create a package without a valid parent directory.');
            }

            $this->files->ensureDirectoryExists($parent);
        }

        if (! $this->files->isWritable($parent)) {
            throw new RuntimeException(sprintf('Parent directory is not writable: %s', $parent));
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
