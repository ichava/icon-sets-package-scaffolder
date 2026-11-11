<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services;

/**
 * Orders the top-level `use` imports of a rendered PHP file the way the shared
 * Pint config does: `ordered_imports` with `sort_algorithm: length`, ties broken
 * case-insensitively.
 *
 * This cannot be done in the stubs. A stub mixes fixed imports
 * (`Simtabi\Laranail\Ichava\...`) with imports under the pack's own namespace
 * (`{{namespace}}\...`), and where a pack import sorts depends on how long the
 * vendor and package names are. Any order written into a stub is correct for
 * some names and wrong for others, so the order is settled after the tokens are
 * substituted, when the lengths are known.
 *
 * Only a contiguous run of unindented `use X;` lines is reordered. A trait's
 * `use` inside a class body is indented and never touched.
 */
final readonly class PhpImportOrderer
{
    public function order(string $php): string
    {
        $lines = explode("\n", $php);
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            if (! $this->isImport($lines[$i])) {
                continue;
            }

            $end = $i;

            while ($end + 1 < $count && $this->isImport($lines[$end + 1])) {
                $end++;
            }

            $run = array_slice($lines, $i, $end - $i + 1);
            usort($run, $this->compare(...));
            array_splice($lines, $i, count($run), $run);

            $i = $end;
        }

        return implode("\n", $lines);
    }

    private function isImport(string $line): bool
    {
        return preg_match('/^use [^;{]+;$/', $line) === 1;
    }

    /**
     * Mirrors php-cs-fixer's `OrderedImportsFixer::sortByLength()`: the imported
     * text (alias included) by length, then `strcasecmp` on a tie.
     */
    private function compare(string $first, string $second): int
    {
        $a = $this->importedText($first);
        $b = $this->importedText($second);

        if (strlen($a) === strlen($b)) {
            return strcasecmp($a, $b);
        }

        return strlen($a) <=> strlen($b);
    }

    private function importedText(string $line): string
    {
        return trim(substr($line, 4, -1));
    }
}
