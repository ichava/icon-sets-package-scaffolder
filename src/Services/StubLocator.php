<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services;

use RuntimeException;
use Symfony\Component\Finder\Finder;

/**
 * Finds stub files and maps each to its destination inside a generated package.
 *
 * Separate from `StubRenderer` because the two change for different reasons:
 * this changes when the stub tree gains a directory, the renderer when a token
 * is added. They lived in one 737-line class before, so touching either meant
 * reading both.
 *
 * Knows nothing about packages -- it walks a directory and rewrites paths.
 */
final readonly class StubLocator
{
    public function __construct(private string $stubsRoot) {}

    /**
     * Every stub, absolute, sorted.
     *
     * `ignoreDotFiles(false)` is load-bearing: the tree ships `.gitignore.stub`,
     * `.gitattributes.stub` and four workflows under `.github/`, all of which
     * Finder skips by default. A pack generated without them looks fine until
     * someone notices it has no CI.
     *
     * `ignoreVCS(true)` is safe alongside that -- Finder's VCS list is `.git`,
     * `.svn`, `.hg` and friends matched as whole directory names, so `.github`
     * is untouched.
     *
     * @return list<string>
     */
    public function all(): array
    {
        if (! is_dir($this->stubsRoot)) {
            throw new RuntimeException(sprintf('Stub directory not found: %s', $this->stubsRoot));
        }

        $finder = Finder::create()
            ->in($this->stubsRoot)
            ->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->notName(['.DS_Store', 'Thumbs.db', 'desktop.ini'])
            ->sortByName();

        $paths = [];

        foreach ($finder as $file) {
            $paths[] = $file->getPathname();
        }

        return $paths;
    }

    /** Stubs-root-relative, forward slashes regardless of host OS. */
    public function relativePath(string $absolutePath): string
    {
        $relative = ltrim(substr($absolutePath, strlen($this->stubsRoot)), '/\\');

        return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    }

    /**
     * A stub's destination path: the trailing `.stub` removed, and any
     * `{{token}}` in a path segment substituted.
     *
     * Filename tokens use the same mustache form as content tokens -- one
     * convention, no separate filename grammar. The braces are legal filename
     * characters everywhere supported, and only ever appear inside `stubs/`.
     *
     * @param array<string, string> $tokens
     */
    public function destinationFor(string $relativeStubPath, array $tokens): string
    {
        $destination = preg_replace('/\.stub$/', '', $relativeStubPath) ?? $relativeStubPath;

        return str_replace(array_keys($tokens), array_values($tokens), $destination);
    }

    public function root(): string
    {
        return $this->stubsRoot;
    }
}
