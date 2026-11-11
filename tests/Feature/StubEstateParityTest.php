<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\Commands\MakeIconPackageCommand;

/**
 * The stub tree must not drift from the estate it scaffolds into.
 *
 * Eight defects shipped because nothing compared the two. Every one of them was
 * fixed in the five real packs and never propagated to `stubs/`,
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
    // `.git` is a directory in a clone and a *file* in a linked worktree.
    // Testing only for a directory made this skip silently wherever the estate
    // sibling was checked out as a worktree -- and since skipping is now a CI
    // failure, a guard that cannot see a perfectly valid checkout is worse than
    // one that is merely absent.
    return file_exists(estateCandidatePath() . '/.git') ? estateCandidatePath() : null;
}

/**
 * The estate pack this guard measures against: the one closest to the
 * majority shape of every estate pack checked out beside this one (S-02).
 *
 * It used to be flag, always, and flag is one of the most divergent packs, so
 * the stub was held to one pack's quirks and every drift the other packs
 * shared was invisible. A pack's shape is the set of tracked paths under
 * `tests/`, `.github/workflows/` and `docs/` at `origin/main`, with its axis
 * page and axis test named generically; the majority shape is every path more
 * than half the packs have. With no sibling checked out, flag's path is
 * returned so the skip names it.
 */
function estateCandidatePath(): string
{
    static $chosen = null;

    if ($chosen !== null) {
        return $chosen;
    }

    $majority = estateMajorityShape();
    $best = null;
    $distance = PHP_INT_MAX;

    foreach (estatePacks() as $path) {
        $shape = estateShape($path);
        $d = count(array_diff($shape, $majority)) + count(array_diff($majority, $shape));

        if ($d < $distance) {
            [$best, $distance] = [$path, $d];
        }
    }

    return $chosen = $best ?? dirname(__DIR__, 3) . '/icon-sets-flag';
}

/**
 * Every path more than half the estate packs carry.
 *
 * @return list<string>
 */
function estateMajorityShape(): array
{
    $packs = estatePacks();
    $counts = [];

    foreach ($packs as $path) {
        foreach (estateShape($path) as $file) {
            $counts[$file] = ($counts[$file] ?? 0) + 1;
        }
    }

    $majority = array_keys(array_filter($counts, static fn (int $n): bool => $n * 2 > count($packs)));
    sort($majority);

    return $majority;
}

/**
 * Every estate pack checked out beside this repository, by path.
 *
 * @return list<string>
 */
function estatePacks(): array
{
    $packs = array_values(array_filter(
        glob(dirname(__DIR__, 3) . '/icon-sets-*', GLOB_ONLYDIR) ?: [],
        static fn (string $path): bool => ! in_array(basename($path), ['icon-sets-sync', 'icon-sets-package-scaffolder'], true)
            && file_exists($path . '/.git'),
    ));
    sort($packs);

    return $packs;
}

/**
 * A pack's shape: its tracked test, workflow and docs paths at origin/main.
 *
 * @return list<string>
 */
function estateShape(string $path): array
{
    $listing = (string) shell_exec('git -C ' . escapeshellarg($path) . ' ls-tree -r --name-only origin/main -- tests .github/workflows docs 2>/dev/null');

    return normaliseShape(array_filter(explode("\n", trim($listing))));
}

/**
 * @param array<int, string> $paths
 *
 * @return list<string>
 */
function normaliseShape(array $paths): array
{
    $paths = array_map(static fn (string $p): string => (string) preg_replace(
        ['#^docs/(variants|categories|sets|icon-sets)\.md$#', '#^tests/Unit/(Variant|Category|Set)Test\.php$#'],
        ['docs/AXIS.md', 'tests/Unit/AXISTest.php'],
        $p,
    ), $paths);
    sort($paths);

    return array_values(array_unique($paths));
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
        '--email'          => 'ichava@simtabi.com',
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

/**
 * Majority paths the stub deliberately does not generate, each with its
 * reason and the measurement behind it. An entry whose path stops being a
 * majority path is reported, so the list cannot outlive its reason.
 */
const STUB_SHAPE_EXEMPTIONS = [
    // 17 of 21 packs carry it (2026-11-05); the blueprint asserts the same
    // three cases inside its ResourceShapeTest instead (packs re-audit P-05).
    'tests/Feature/RetiredCopiesTest.php' => 'its cases live in the stub\'s ResourceShapeTest',
];

it('scaffolds every test, workflow and docs path most estate packs carry', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $floor = filter_var(getenv('ICHAVA_REQUIRE_ESTATE') ?: '', FILTER_VALIDATE_BOOLEAN) ? 19 : 1;
    $generated = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->scaffoldRoot, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        $relative = substr($file->getPathname(), strlen($this->scaffoldRoot) + 1);

        if (preg_match('#^(tests|\.github/workflows|docs)/#', $relative) === 1) {
            $generated[] = $relative;
        }
    }

    // CI clones every public pack; locally whatever is checked out counts.
    expect(count(estatePacks()))->toBeGreaterThanOrEqual($floor)
        ->and(array_values(array_diff(estateMajorityShape(), normaliseShape($generated), array_keys(STUB_SHAPE_EXEMPTIONS))))->toBe([], 'The stub lacks paths most estate packs carry.')
        ->and(array_values(array_diff(array_keys(STUB_SHAPE_EXEMPTIONS), estateMajorityShape())))->toBe([], 'An exemption no longer names a majority path; remove it.');
});

it('scaffolds the same runtime constraints a real pack declares', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $generated = json_decode((string) file_get_contents($this->scaffoldRoot . '/composer.json'), true);
    $estate = estatePackManifest();

    // A pack requires php and ichava/core and nothing else; core alone uses the
    // laranail/* packages. Compared as the whole map, so a stub that keeps a
    // dependency the estate dropped fails as surely as one that pins differently.
    expect(array_keys($estate['require']))->toEqualCanonicalizing(['php', 'ichava/core']);
    expect($generated['require'] ?? [])->toEqual($estate['require']);
    expect($generated['type'] ?? null)->toBe($estate['type'] ?? null);
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

it('scaffolds a provider on the same core base a real pack extends, naming no laranail type', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $generated = (string) file_get_contents($this->scaffoldRoot . '/src/Providers/IconsServiceProvider.php');
    $estate = (string) estateFile('src/Providers/IconsServiceProvider.php');

    // Read the base out of the real pack rather than naming it here, so a
    // rename in core is caught instead of being encoded twice.
    preg_match('/^class IconsServiceProvider extends (\\w+)$/m', $estate, $extends);
    expect($extends)->toHaveCount(2, 'flag-icons no longer declares its provider base on one line.');
    preg_match('/^use (Simtabi\\\\Laranail\\\\Ichava\\\\Providers\\\\' . $extends[1] . ');$/m', $estate, $import);
    expect($import)->toHaveCount(2, "flag-icons extends {$extends[1]} but does not import it from core.");

    expect($generated)->toContain("use {$import[1]};")
        ->and($generated)->toContain("class IconsServiceProvider extends {$extends[1]}");

    // Neither the generated provider nor the pack it measures against imports
    // package-tools, and the pre-migration spelling stays gone.
    expect($generated)->not->toContain('Laranail\\Package\\Tools')
        ->and($estate)->not->toContain('Laranail\\Package\\Tools')
        ->and($generated)->not->toContain('Laranail\\PackageTools\\');
});

it('scaffolds no Artisan command, and would namespace one if it ever did', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    // Two assertions, and the first is the one with teeth.
    //
    // The stub used to ship an UpdateIconsCommand extending a core class that
    // has never existed, so the generated provider registered a missing class
    // and every scaffolded package fatally failed to boot. No real pack has ever
    // had src/Commands; updates go through `ichava::ichava-core.check-updates`
    // and the sync-upstream workflow. So a generated pack registering *any*
    // command is drift until the estate grows one.
    $signatures = [];

    // RecursiveDirectoryIterator, not glob(). PHP's glob does not treat `**` as
    // "any depth" -- `src/**/*.php` matches exactly one directory level, so a
    // command nested deeper would go unseen and this guard would pass by finding
    // nothing. Measured: on a tree with src/Y.php and src/Commands/X.php,
    // glob('src/**/*.php') returns 1 file and the iterator returns 2.
    $php = new RegexIterator(
        new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->scaffoldRoot . '/src')),
        '/\\.php$/',
    );

    foreach ($php as $file) {
        preg_match_all("/\\\$signature\\s*=\\s*'([^\\s']+)/", (string) file_get_contents($file->getPathname()), $m);
        $signatures = [...$signatures, ...$m[1]];
    }

    expect($signatures)->toBe([], 'A scaffolded pack registers a command the estate does not have.');

    // And if that ever changes deliberately, `V59` still applies: Artisan's table
    // is a flat map, so a bare generic slug replaces whatever got there first.
    foreach ($signatures as $signature) {
        expect($signature)->toMatch('/^[a-z0-9-]+::[a-z0-9-]+\\./');
    }
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

it('scaffolds the same src/ shape a real pack has', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    // The case that was missing, and what it cost: the stub shipped
    // `src/Commands/UpdateIconsCommand.php` extending
    // `Simtabi\\Laranail\\Ichava\\Commands\\UpdateIconsCommand`, a class core has
    // never had. The generated provider registered it, so **every scaffolded
    // package fatally failed to boot** -- and nothing noticed, because the other
    // cases compare composer, the provider, the workflows and the docs, and the
    // end-to-end test only runs `php -l`, which is syntax and not resolution.
    //
    // Comparing the directory listing is cheap and catches the whole class: a
    // stub that grows a subsystem the estate does not have, or loses one it does.
    $estate = array_values(array_diff(estateFiles('src'), ['']));
    $estateDirs = array_unique(array_map(
        static fn (string $p): string => explode('/', substr($p, strlen('src/')))[0],
        $estate,
    ));
    sort($estateDirs);

    $generated = array_map(
        static fn (string $p): string => basename($p),
        glob($this->scaffoldRoot . '/src/*', GLOB_ONLYDIR) ?: [],
    );
    sort($generated);

    expect($generated)->toBe($estateDirs);
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

it('scaffolds the canonical resources shape the estate packs ship', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    // The estate settled on this shape after three bugs with one cause: a
    // resource file nothing loads cannot fail loudly. One pack shipped another
    // pack's translations, one claimed a licence it does not hold, and every
    // pack keeping name/description in two places had let them drift. A new
    // pack must not be born with any of that.
    foreach ([
        'resources/assets/svg/config.json',
        'resources/lang/en/icons.php',
        'resources/views/components/.gitkeep',
    ] as $path) {
        expect(estateFile($path))->not->toBeNull(
            "The estate pack no longer ships {$path}; the stub tree should follow it, not lead it.",
        );

        expect(file_exists($this->scaffoldRoot . '/' . $path))->toBeTrue(
            "Scaffolded packages are missing {$path}, which the estate ships.",
        );
    }
});

it('scaffolds a translation file that does not restate config.json', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $lang = require $this->scaffoldRoot . '/resources/lang/en/icons.php';

    // config.json is canonical for these in English, and IconRegistry reads it.
    // A pack born with a second copy is a pack born with the drift in it.
    expect($lang)->not->toHaveKey('name')
        ->and($lang)->not->toHaveKey('description')
        ->and($lang)->not->toHaveKey('features');
});

it('scaffolds a translation file whose keys match the generated Variant enum', function () {
    skipWithoutEstate();
    scaffoldForParity($this->scaffoldRoot);

    $lang = require $this->scaffoldRoot . '/resources/lang/en/icons.php';
    $enum = (string) file_get_contents($this->scaffoldRoot . '/src/Enums/Variant.php');

    // Whatever cases the generated enum declares must have labels, or the
    // shipped ResourceShapeTest fails on a pack nobody has touched yet.
    preg_match_all("/^\s*case\s+\w+\s*=\s*'([^']+)'/m", $enum, $matches);

    $cases = $matches[1];
    sort($cases);

    $labels = array_keys($lang['variants']);
    sort($labels);

    expect($labels)->toBe($cases);
});
