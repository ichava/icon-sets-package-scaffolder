<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Support;

use InvalidArgumentException;

/**
 * A destination directory, normalised and known-absolute.
 *
 * Passing a validated object instead of a `string $path` means the generating
 * classes below cannot be handed a relative path, an empty string, or a
 * trailing-slash variant that makes `$path . '/src'` produce a double slash.
 * The rules ran inline in the command before, so nothing enforced them when
 * generation was reached by any other route.
 *
 * Deliberately not in `Domain/`: resolving a relative path needs a base
 * directory from the host application, which is a framework fact.
 */
final readonly class TargetPath
{
    private function __construct(public string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param string $path absolute, or relative to $basePath
     * @param string|null $basePath host root; required when $path is relative
     */
    public static function resolve(string $path, ?string $basePath = null): self
    {
        $path = trim($path);

        if ($path === '') {
            throw new InvalidArgumentException('A destination path is required.');
        }

        $path = rtrim($path, '/\\');

        // rtrim eats the root's own separator: "/" becomes "", and on Windows
        // "C:\" becomes "C:". Neither is empty-as-in-missing and neither is
        // relative, so both have to be refused here -- "C:" in particular would
        // otherwise fail the absolute check below and be re-based, scaffolding
        // a package into "<base>/C:".
        if ($path === '' || preg_match('/^[A-Za-z]:$/', $path) === 1) {
            throw new InvalidArgumentException('A destination path cannot be the filesystem root.');
        }

        if (! self::isAbsolute($path)) {
            if ($basePath === null) {
                throw new InvalidArgumentException(
                    sprintf('"%s" is relative and no base path was supplied to resolve it against.', $path),
                );
            }

            $path = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $path;
        }

        return new self($path);
    }

    /** Already-absolute paths only; skips the base-path requirement. */
    public static function absolute(string $path): self
    {
        return self::resolve($path);
    }

    public function join(string ...$segments): string
    {
        return $this->value . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
    }

    public function parent(): string
    {
        return dirname($this->value);
    }

    public function basename(): string
    {
        return basename($this->value);
    }

    /**
     * Unix `/x`, Windows `C:\x` and `C:/x`, and UNC `\\server\share`.
     *
     * Checked without touching the filesystem, so this stays usable for a
     * destination that does not exist yet -- which is every scaffold.
     */
    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }
}
