<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Actions;

use Simtabi\Laranail\Ichava\IconPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Services\StubLocator;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Services\StubRenderer;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Services\TokenMapFactory;

/**
 * Renders every stub into the destination.
 *
 * Auto-discovery rather than a manifest: adding a file to `stubs/` is the whole
 * change, with nothing to register. That property is worth protecting -- the
 * moment a list exists somewhere, a stub gets added and not listed, and the
 * omission is silent.
 */
final readonly class RenderStubTree
{
    public function __construct(
        private StubLocator $locator,
        private StubRenderer $renderer,
        private TokenMapFactory $tokens,
    ) {}

    /** @return list<string> files written, package-root-relative */
    public function __invoke(PackageDefinition $definition, TargetPath $path): array
    {
        $tokens = $this->tokens->for($definition);
        $written = [];

        foreach ($this->locator->all() as $stub) {
            $relative = $this->locator->relativePath($stub);
            $destination = $this->locator->destinationFor($relative, $tokens);

            $this->renderer->renderTo($stub, $path->join(...explode('/', $destination)), $tokens);

            $written[] = $destination;
        }

        return $written;
    }
}
