<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| Every relative link in a scaffolded pack points at a file it actually has
|--------------------------------------------------------------------------
|
| Two shipped dead and nothing noticed. `stubs/README.md.stub` linked both
| `CONTRIBUTING.md` and `SECURITY.md`, and the stub tree contained neither, so
| every package ever scaffolded was born with two 404s in the one section that
| tells a reporter where to send a vulnerability.
|
| No link checker could have caught it, and that is the point worth keeping:
| a checker runs against THIS repository, where `stubs/README.md.stub` is not a
| document anybody renders and `{{token}}` is not a path. The generated package
| -- the only artefact where these links are real -- exists solely inside a
| test. So the assertion has to run over the rendered output.
|
| The estate's standing lesson, stated once more because this is exactly it:
| a sweep aimed at the real packages does not reach the stub tree.
|
*/

it(description: 'links only to files a scaffolded package actually contains', closure: function (): void {
    $root = sys_get_temp_dir() . '/ichava-linktargets-' . bin2hex(random_bytes(4));

    app(ScaffoldIconPackage::class)(
        PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'dev@example.com'),
        TargetPath::absolute($root),
        force: true,
    );

    // RecursiveDirectoryIterator, not glob(): PHP's glob does not treat `**` as
    // "any depth", so a nested page would be silently excluded from both the
    // set of documents checked and the set of files considered to exist.
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    $present = [];
    $markdown = [];
    foreach ($files as $file) {
        $relative = ltrim(str_replace($root, '', $file->getPathname()), '/');
        $present[$relative] = true;
        if (str_ends_with($relative, '.md')) {
            $markdown[$relative] = (string) file_get_contents($file->getPathname());
        }
    }

    // A glob or a walk that matched nothing makes every assertion below vacuous,
    // and the test then reports a clean result over a package it never read.
    expect($markdown)->not->toBeEmpty('the scaffolded package shipped no markdown');
    expect($present)->toHaveKey('README.md');

    $checked = 0;
    foreach ($markdown as $name => $body) {
        // Strip what only LOOKS like a link before matching. A stub teaches the
        // pack author what to write, so `attribution.md.stub` carries a worked
        // example inside an HTML comment:
        //
        //     <!-- Icons from [Beacon](upstream-link) (Beacon licence). -->
        //
        // `upstream-link` is a placeholder for a human to replace, not a path,
        // and a guard that cannot tell an illustration from a live link fails on
        // correct files until somebody deletes the illustration to appease it.
        // Fenced blocks and code spans go for the same reason.
        $prose = (string) preg_replace(
            ['/<!--.*?-->/s', '/^```.*?^```/ms', '/`[^`\n]*`/'],
            '',
            $body,
        );

        preg_match_all('/\]\((?!https?:|#|mailto:)([^)]+)\)/', $prose, $matches);

        foreach ($matches[1] as $link) {
            $target = strtok($link, '#');
            if ($target === false || $target === '') {
                continue; // a bare fragment, resolved within the same page
            }

            // Resolve relative to the linking document, so a `../` from a page
            // under `docs/` is judged from where it is actually written.
            //
            // Collapsed in a LOOP, not a single pass. `preg_replace` resumes
            // after each match it makes, so `docs/tools/../../README.md` loses
            // only its first `../` per call and comes out still containing one.
            // Today every docs page is flat and one pass would do; a page added
            // under `docs/tools/` would then fail this test while being
            // perfectly correct.
            $resolved = dirname($name) . '/' . $target;
            $resolved = str_starts_with($resolved, './') ? substr($resolved, 2) : $resolved;
            do {
                $before = $resolved;
                $resolved = (string) preg_replace(
                    ['#/\./#', '#(?:^|/)[^/]+/\.\./#'],
                    ['/', ''],
                    $resolved,
                    1,
                );
            } while ($resolved !== $before);

            $checked++;
            expect($present)->toHaveKey(
                $resolved,
                "{$name} links {$link}, which a scaffolded package does not contain",
            );
        }
    }

    // The count is the guard. Without it a regex that stops matching turns this
    // whole test green over a package full of broken links.
    expect($checked)->toBeGreaterThan(8, 'inspected implausibly few relative links');
});
