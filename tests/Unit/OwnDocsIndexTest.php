<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| This package's own docs are reachable from its own index
|--------------------------------------------------------------------------
|
| The standard makes the README's `## Documentation` section the index -- there
| is deliberately no `docs/README.md`. A page in `docs/` that the section does
| not list is therefore unreachable by the route the standard defines, and
| nothing else notices: link checkers verify the links that exist, never the
| pages nobody linked.
|
| Two of eleven were orphaned that way, including `creating-icon-packages.md`,
| at 364 lines the most substantial guide here.
|
*/

function docsRoot(): string
{
    return dirname(__DIR__, 2) . '/docs';
}

/** @return list<string> every page, repo-relative */
function docsPages(): array
{
    $pages = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(docsRoot(), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (str_ends_with($file->getFilename(), '.md')) {
            $pages[] = 'docs/' . str_replace(docsRoot() . '/', '', $file->getPathname());
        }
    }

    sort($pages);

    return $pages;
}

it('lists every docs page in the README index', function () {
    $pages = docsPages();

    // Non-vacuous: a glob that matched nothing would pass every assertion below.
    expect($pages)->not->toBeEmpty('the docs scan matched no pages');

    $readme = (string) file_get_contents(dirname(__DIR__, 2) . '/README.md');
    $section = (string) (preg_split('/^## /m', $readme)[0] ?? '');

    // Only the Documentation section counts as the index.
    preg_match('/^## <a name="documentation"><\/a>Documentation$(.*?)(?=^## )/ms', $readme, $m);
    expect($m)->not->toBeEmpty('the README has no anchored Documentation section');
    $index = $m[1];

    $missing = array_values(array_filter(
        $pages,
        static fn (string $page): bool => ! str_contains($index, '(' . $page . ')'),
    ));

    expect($missing)->toBe([], 'pages in docs/ that the README index does not list');
});

it('closes each page with exactly one horizontal rule before the footer', function () {
    $offenders = [];

    foreach (docsPages() as $page) {
        $lines = file(dirname(__DIR__, 2) . '/' . $page, FILE_IGNORE_NEW_LINES);
        $tail = array_slice($lines ?: [], -6);

        $rules = count(array_filter($tail, static fn (string $l): bool => trim($l) === '---'));

        if ($rules !== 1) {
            $offenders[] = "{$page} ({$rules} rules)";
        }
    }

    expect($offenders)->toBe([]);
});
