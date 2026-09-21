<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Domain;

/**
 * What a scaffold produced.
 *
 * Returned instead of an exit code so a non-console caller learns what happened
 * without parsing output. The command turns this into its "next steps" block;
 * a test asserts on it directly.
 */
final readonly class ScaffoldResult
{
    /**
     * @param list<string> $files written, relative to the package root
     * @param list<string> $directories created, relative to the package root
     */
    private function __construct(
        public PackageDefinition $definition,
        public string $path,
        public array $files,
        public array $directories,
    ) {}

    /**
     * @param list<string> $files
     * @param list<string> $directories
     */
    public static function of(PackageDefinition $definition, string $path, array $files, array $directories): self
    {
        sort($files);
        sort($directories);

        return new self($definition, $path, $files, $directories);
    }

    public function fileCount(): int
    {
        return count($this->files);
    }
}
