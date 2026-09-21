<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services;

use RuntimeException;
use Illuminate\Filesystem\Filesystem;

/**
 * Substitutes tokens and writes the result.
 *
 * The only class here that touches the filesystem for content. Kept apart from
 * `StubLocator` so "where do stubs come from" and "what happens to their text"
 * stay independently testable -- the render step can be exercised against a
 * string with no directory at all.
 */
final readonly class StubRenderer
{
    public function __construct(private Filesystem $files) {}

    /**
     * @param array<string, string> $tokens
     */
    public function render(string $contents, array $tokens): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $contents);
    }

    /**
     * Read a stub, substitute, write to $destination, creating parents.
     *
     * @param array<string, string> $tokens
     */
    public function renderTo(string $stubPath, string $destination, array $tokens): void
    {
        if (! $this->files->exists($stubPath)) {
            throw new RuntimeException(sprintf('Stub file not found: %s', $stubPath));
        }

        $this->files->ensureDirectoryExists(dirname($destination));
        $this->files->put($destination, $this->render($this->files->get($stubPath), $tokens));
    }
}
