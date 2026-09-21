<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\Commands\MakeIconPackageCommand;

/**
 * The stub tree must not drift from the estate it scaffolds into.
 *
 * Eight defects shipped because nothing compared the two. Every one of them was
 * fixed in the five real packs and never propagated to `stubs/icon-package/`,
 * so the scaffolder kept emitting conventions the estate had already retired:
 *
 *   • `"ichava/core": "^1.0"`            -- a version that has never existed
 *   • `"php": "^8.3"`                    -- floor two minors below the estate
 *   • `illuminate/support: ^10|^12|^13`  -- stale, and skipping 11
 *   • `laranail/package-tools` omitted   -- while the provider imports it
 *   • `Simtabi\Laranail\PackageTools\*`  -- the pre-migration namespace
 *   • `ichava:update-<pack>-icons`       -- the bare name `V59` forbids
 *   • `orchestra/testbench: ^8|^10|^11`  -- stale
 *   • `pestphp/pest: ^2|^3`              -- two majors behind
 *
 * So this asserts against **a real pack's manifest at `origin/main`**, rather
 * than against literals. Hardcoded expectations are what let the drift happen:
 * a literal encodes the estate as it was on the day the test was written, and
 * then ages silently alongside the thing it was supposed to guard. A fixture
 * that IS the estate cannot.
 *
 * When this fails, the stub is behind (or ahead of) the packs. Fix whichever
 * is actually wrong -- the test does not care which, only that they agree.
 *
 * Moved here from `ichava/core` with the stub tree it guards. It belongs beside
 * the stubs rather than beside the packs: core no longer has a stub tree to be
 * wrong about, and a guard that outlives the thing it guards is the failure
 * mode this file exists to prevent.
 */
beforeEach(function () {
    $this->scaffoldRoot = sys_get_temp_dir() . '/ichava-stub-parity-' . uniqid();
});

afterEach(function () {
    if (! empty($this->scaffoldRoot) && is_dir($this->scaffoldRoot)) {
        (new Filesystem)->deleteDirectory($this->scaffoldRoot);
    }
});

/**
 * A real, shipped pack to measure the stub against.
 *
 * The flag pack rather than any other: it is public, it carries the full
 * manifest shape (repositories, scripts, branch-alias, support.security) and
 * all four workflows, and it is the pack whose `<use>` elements the SVG policy
 * corpus run already tracks, so it is unlikely to be quietly retired.
 *
 * **The directory and the GitHub repository are both `icon-sets-flag`.** They
 * diverged for part of 2026-09-21 -- the restructure renamed the local
 * checkouts while the remotes still said `flag-icons` -- and the rebrand later
 * closed the gap. The clone step in `tests.yml` fetches
 * `ichava/icon-sets-flag.git` into a directory of the same name, so the two
 * still have to move together; they are equal now by maintenance, not by
 * anything enforcing it.
 *
 * `dirname(__DIR__, 3)` is the package parent, `packages/`, and was correct
 * before and after the move: the whole tree relocated together, so the break
 * was the rename alone. Anyone reaching for the depth here is fixing something
 * that is not wrong.
 */
function estateCheckoutPath(): ?string
{
    return is_dir(estateCandidatePath() . '/.git') ? estateCandidatePath() : null;
}

/** Where the sibling is expected, named so a skip can say what it looked for. */
function estateCandidatePath(): string
{
    return dirname(__DIR__, 3) . '/icon-sets-flag';
}

/**
 * The estate pack at `origin/main`, read from the object store.
 *
 * **Never the working tree.** Reading the sibling's checked-out files makes this
 * guard answer a different question depending on what branch somebody else has
 * open next door: a sibling on a feature branch turns this red in one checkout
 * and green in another, for a reason CI structurally cannot reproduce, and the
 * first instinct on seeing it is to chase a regression that is not there. That
 * happened, in this monorepo, on this test.
 *
 * **And not `git archive` either, which is the obvious way to do this and is
 * wrong.** Archive applies `export-ignore`, and every pack export-ignores
 * `.github`, `docs`, `tests` and `CONTRIBUTING.md` to keep them out of dist
 * tarballs -- so four of the things this guard compares would simply not be
 * there, and the cases covering them would fail claiming the estate ships no
 * workflows. `ls-tree` and `show` read the tree as committed.
 *
 * The cost is a real sequencing constraint rather than a hidden one: a change
 * moving both the stub and the estate is red here until the estate side merges.
 * **Land the pack first, then the stub.** A guard that went green mid-wave
 * would not be measuring anything.
 */
function estateFile(string $relativePath): ?string
{
    $checkout = estateCheckoutPath();

    if ($checkout === null) {
        return null;
    }

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open(
        ['git', '-C', $checkout, 'show', 'origin/main:' . $relativePath],
        $descriptors,
        $pipes,
    );

    if (! is_resource($process)) {
        return null;
    }

    $contents = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return proc_close($process) === 0 ? $contents : null;
}

/**
 * Paths under $prefix at `origin/main`, repo-relative.
 *
 * @return list<string>
 */
function estateFiles(string $prefix): array
{
    $checkout = estateCheckoutPath();

    if ($checkout === null) {
        return [];
    }

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open(
        ['git', '-C', $checkout, 'ls-tree', '-r', '--name-only', 'origin/main', '--', $prefix],
        $descriptors,
        $pipes,
    );

    if (! is_resource($process)) {
        return [];
    }

    $listing = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return array_values(array_filter(explode("\n", trim($listing))));
}

function estatePackManifest(): ?array
{
    $manifest = estateFile('composer.json');

    return $manifest === null ? null : json_decode($manifest, true);
}

function scaffoldForParity(string $root): void
{
    test()->artisan(MakeIconPackageCommand::class, [
        'name'             => 'Parity',
        '--vendor'         => 'Ichava',
        '--email'          => 'opensource@simtabi.com',
        '--prefix'         => 'pa',
        '--type'           => 'single',
        '--path'           => $root,
        '--force'          => true,
        '--no-interaction' => true,
    ])->assertSuccessful();
}

/**
 * Skipping covers a standalone checkout with no sibling beside it.
 *
 * **It does not cover CI, which clones the sibling deliberately** -- see
 * `tests.yml`, `Check out flag-icons beside the checkout`. An earlier revision
 * of this docblock said CI clones one repository and that skipping was correct
 * there, and that claim outlived the workflow step that falsified it.
 *
 * The message names the path rather than the pack, because every way this has
 * actually skipped was a path that moved, and a message naming the pack reads
 * like a network or ref problem instead.
 */
function skipWithoutEstate(): void
{
    if (estatePackManifest() !== null) {
        return;
    }

    $reason = sprintf(
        'parity guard needs the estate sibling at %s (not found, or its origin/main is unreadable)',
        estateCandidatePath(),
    );

    // Somewhere that promised the sibling, a skip is a failure.
    //
    // Pest exits 0 on a skipped test, so a run where the clone step broke, the
    // directory was renamed, or the pack went private reports green and says
    // nothing -- which is the exact shape of silent drift this guard exists to
    // catch, arriving through the guard itself. It happened: the 2026-09-21
    // rename turned 59 passed into 51 passed and 8 skipped, and nothing went red.
    //
    // This version of Pest has no --fail-on-skipped, so the contract lives here
    // rather than in a grep over the output. CI and the scheduled run set
    // ICHAVA_REQUIRE_ESTATE=1 because they provision the sibling themselves; a
    // bare local checkout does not, and still skips politely.
    if (filter_var(getenv('ICHAVA_REQUIRE_ESTATE') ?: '', FILTER_VALIDATE_BOOLEAN)) {
        test()->fail($reason . ' -- ICHAVA_REQUIRE_ESTATE is set, so this is a failure rather than a skip');
    }

    test()->markTestSkipped($reason);
}

it('scaffolds the same runtime constraints a real pack declares', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $generated = json_decode((string) file_get_contents($this->scaffoldRoot . '/composer.json'), true);
    $estate = estatePackManifest();

    foreach (['php', 'illuminate/support', 'ichava/core', 'laranail/package-tools'] as $dependency) {
        // toHaveKey($key, $value) asserts presence AND equality in one step, so
        // a stub that omits the dependency and one that pins it differently
        // both fail here, which is what drift looks like in either direction.
        expect($estate['require'])->toHaveKey($dependency);
        expect($generated['require'] ?? [])->toHaveKey($dependency, $estate['require'][$dependency]);
    }
});

it('scaffolds the same dev toolchain a real pack declares', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $generated = json_decode((string) file_get_contents($this->scaffoldRoot . '/composer.json'), true);
    $estate = estatePackManifest();

    foreach (['orchestra/testbench', 'pestphp/pest'] as $dependency) {
        expect($estate['require-dev'])->toHaveKey($dependency);
        expect($generated['require-dev'] ?? [])->toHaveKey($dependency, $estate['require-dev'][$dependency]);
    }
});

it('scaffolds the VCS repositories a pack needs, because nothing here is on Packagist', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $generated = json_decode((string) file_get_contents($this->scaffoldRoot . '/composer.json'), true);

    // Without these a generated package cannot resolve `ichava/core` at all:
    // `repo.packagist.org` answers 404 for every `ichava/*` and `laranail/*`.
    $urls = array_column($generated['repositories'] ?? [], 'url');

    expect($urls)->not->toBeEmpty('Scaffolded packages have no VCS repositories and cannot resolve ichava/core.');
    expect(implode(' ', $urls))->toContain('ichava/core');
});

it('scaffolds a provider importing the namespace package-tools actually publishes', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $generated = (string) file_get_contents($this->scaffoldRoot . '/src/Providers/IconsServiceProvider.php');
    $estate = (string) estateFile('src/Providers/IconsServiceProvider.php');

    // Read the namespace out of the real pack rather than naming it here, so an
    // upstream rename is caught instead of being encoded twice.
    preg_match('/^use (Simtabi\\\\Laranail\\\\[A-Za-z\\\\]+)\\\\Package;$/m', $estate, $matches);
    expect($matches)->toHaveCount(2, 'flag-icons no longer imports a package-tools Package class.');

    // Scaffolded provider must import the same package-tools namespace
    // flag-icons does, and must not carry the pre-migration spelling.
    expect($generated)->toContain("use {$matches[1]}\\Package;");
    expect($generated)->not->toContain('Laranail\\PackageTools\\');
});

it('scaffolds a namespaced update command, never a bare one', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $command = (string) file_get_contents($this->scaffoldRoot . '/src/Commands/UpdateIconsCommand.php');

    // `V59`: Artisan's command table is a flat map, so a bare generic slug does
    // not collide loudly -- it replaces whatever got there first.
    expect($command)->toMatch('/\$signature\s*=\s*\'ichava::[a-z0-9-]+\.[a-z-]+/');
    expect($command)->not->toMatch('/\$signature\s*=\s*\'ichava:[a-z]/');
});

it('scaffolds the workflows every real pack ships', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $estateWorkflows = estateFiles('.github/workflows');
    expect($estateWorkflows)->not->toBeEmpty('flag-icons ships no workflows to compare against.');

    foreach ($estateWorkflows as $workflow) {
        $name = basename($workflow);
        expect(file_exists($this->scaffoldRoot . '/.github/workflows/' . $name))->toBeTrue(
            "Scaffolded packages are missing {$name}, which flag-icons ships.",
        );
    }
});

it('scaffolds the docs pages a real pack ships, and no docs index', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    // One README per repo. The index is the package README's own docs section,
    // and a standalone docs/README.md duplicates it and then drifts -- which is
    // why no pack in the estate has one. The stub shipped one from `Initial
    // release`, so every scaffolded pack diverged on its first commit.
    expect(file_exists($this->scaffoldRoot . '/docs/README.md'))->toBeFalse(
        'Scaffolded packages must not ship a docs/README.md index; the package README carries it.',
    );

    // Deleting the index must not take its links with it: the pages it listed
    // still have to be scaffolded, and the README still has to reach them.
    foreach (estateFiles('docs') as $page) {
        $name = basename($page);
        expect(file_exists($this->scaffoldRoot . '/docs/' . $name))->toBeTrue(
            "Scaffolded packages are missing docs/{$name}, which flag-icons ships.",
        );
        // toContain() takes variadic needles, not a message, so assert the
        // predicate instead -- otherwise the message becomes a second needle.
        $readme = (string) file_get_contents($this->scaffoldRoot . '/README.md');
        expect(str_contains($readme, 'docs/' . $name))->toBeTrue(
            "The package README does not link docs/{$name}; nothing else indexes it now.",
        );
    }
});

it('scaffolds workflows that trigger on pull_request, never on a branch push', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    foreach (glob($this->scaffoldRoot . '/.github/workflows/*.yml') ?: [] as $workflow) {
        $body = (string) file_get_contents($workflow);
        $name = basename($workflow);

        // The only legitimate push trigger is a tag, for the release workflow.
        if (preg_match('/^\s*push:/m', $body)) {
            expect($body)->toMatch(
                '/push:\s*\n\s*tags:/',
                "{$name} triggers on push without a tag filter; CI must run on pull_request.",
            );
        }
    }
});
