<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain;

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * One entry of `kinds.rules` in a pack's `config.json`: an item-path glob and
 * the kind every matching item gets (D6 row 3, ichava/core's `KindGlob`).
 *
 * This package does not depend on core, so it carries the glob's validity
 * rules and not its matcher. A pattern is refused here for the same reasons
 * core drops it and doctor reports `kind.invalid_glob`: a reserved character
 * (`[ ] { } \`), an empty pattern, a leading `/`, a `..` or empty segment, or
 * a `**` that is not a whole segment.
 *
 * Rules are ordered, because the first match wins; the definition keeps them
 * in the order they were given.
 */
final readonly class KindRule
{
    public function __construct(
        public string $match,
        public IconKind $kind,
    ) {
        if (! self::isValidGlob($match)) {
            throw InvalidPackageDefinition::invalidKindGlob($match);
        }
    }

    /**
     * Parses `<glob>=<kind>`, as `--kind-rule` takes it. Splits on the last
     * `=`, which no kind contains.
     */
    public static function fromInput(string $spec): self
    {
        $at = strrpos($spec, '=');

        if ($at === false) {
            throw InvalidPackageDefinition::malformedKindRule($spec);
        }

        return new self(trim(substr($spec, 0, $at)), IconKind::fromInput(substr($spec, $at + 1)));
    }

    public static function isValidGlob(string $pattern): bool
    {
        if ($pattern === '' || $pattern[0] === '/' || strpbrk($pattern, '[]{}\\') !== false) {
            return false;
        }

        foreach (explode('/', $pattern) as $segment) {
            if ($segment === '' || $segment === '..') {
                return false;
            }

            if (str_contains($segment, '**') && $segment !== '**') {
                return false;
            }
        }

        return true;
    }

    /** @return array{match: string, kind: string} */
    public function toConfigArray(): array
    {
        return ['match' => $this->match, 'kind' => $this->kind->value];
    }
}
