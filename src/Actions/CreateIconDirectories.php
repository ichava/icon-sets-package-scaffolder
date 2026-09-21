<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Actions;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain\PackageDefinition;

/**
 * Creates the SVG directory layout a pack's icons live in.
 *
 * One directory for a single-set pack, one per variant for a multi-variant
 * one. Each gets a `.gitkeep`, because git does not track empty directories
 * and a freshly scaffolded pack has no icons yet -- without it the layout the
 * pack's config declares would not survive the first commit.
 */
final readonly class CreateIconDirectories
{
    public function __construct(private Filesystem $files) {}

    /** @return list<string> directories created, package-root-relative */
    public function __invoke(PackageDefinition $definition, TargetPath $path): array
    {
        $root = 'resources/assets/svg/files';
        $created = [];

        if (! $definition->type->hasVariantDirectories()) {
            $this->makeDirectory($path->join($root), 'Place your SVG icons here');

            return [$root];
        }

        foreach ($definition->variants as $variant) {
            $relative = $root . '/' . $variant->slug;
            // The slug, not the label: the note repeats the directory name, and
            // the directory is named by the slug.
            $this->makeDirectory($path->join(...explode('/', $relative)), sprintf('Place your %s SVG icons here', $variant->slug));
            $created[] = $relative;
        }

        return $created;
    }

    private function makeDirectory(string $absolute, string $note): void
    {
        $this->files->ensureDirectoryExists($absolute);
        $this->files->put($absolute . DIRECTORY_SEPARATOR . '.gitkeep', '# ' . $note . "\n");
    }
}
