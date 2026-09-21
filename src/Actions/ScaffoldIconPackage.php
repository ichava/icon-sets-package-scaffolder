<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Actions;

use RuntimeException;
use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain\ScaffoldResult;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain\PackageDefinition;

/**
 * Scaffolds an icon package. The one use case this package exists for, and its
 * public API.
 *
 * A caller with no terminal -- a test, a CI job, a future browser UI -- does:
 *
 *     ($scaffold)($definition, TargetPath::absolute($path));
 *
 * and gets a `ScaffoldResult` back. Before this seam existed, generation was
 * reachable only through a command's `handle()`, so the only way to exercise
 * it was to drive interactive prompts.
 *
 * One public method, per the layering rule: the moment an action grows a second
 * entry point it has become a service, and services are what accreted into the
 * 39-method classes this design is a reaction to.
 */
final readonly class ScaffoldIconPackage
{
    public function __construct(
        private Filesystem $files,
        private RenderStubTree $renderStubs,
        private CreateIconDirectories $createDirectories,
    ) {}

    public function __invoke(PackageDefinition $definition, TargetPath $path, bool $force = false): ScaffoldResult
    {
        $this->guardDestination($path, $force);

        $this->files->ensureDirectoryExists($path->value);

        $files = ($this->renderStubs)($definition, $path);
        $directories = ($this->createDirectories)($definition, $path);

        return ScaffoldResult::of($definition, $path->value, $files, $directories);
    }

    /**
     * Refuse to write into a non-empty directory unless told to.
     *
     * Scaffolding is bulk file creation against a path a human typed, so the
     * failure mode is overwriting a real package. `$force` is the caller's
     * explicit acceptance of that, not a default.
     */
    private function guardDestination(TargetPath $path, bool $force): void
    {
        if ($force || ! $this->files->isDirectory($path->value)) {
            return;
        }

        // scandir returns `.` and `..` for an empty directory.
        $entries = array_diff((array) scandir($path->value), ['.', '..']);

        if ($entries !== []) {
            throw new RuntimeException(
                sprintf('Destination "%s" already exists and is not empty. Pass force to overwrite.', $path->value),
            );
        }
    }
}
