<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| A fresh scaffold has the shape of a finished estate pack
|--------------------------------------------------------------------------
|
| Fifteen packs were scaffolded on 2026-11-04 and every one was hand-fixed
| afterwards: a Variant enum holding a DEFAULT case and commented-out
| placeholders whatever --variants said, a config file nothing read, eight
| tests short of icon-sets-tabler, a docker-based sync-upstream.yml where the
| packs call icon-sets-sync's reusable sync-pack.yml, an empty release-please
| manifest the pack's own ReleaseVersionTest rejects, and older .gitattributes,
| .gitignore, phpunit.xml.dist and workflow permissions.
|
| The expected file set is icon-sets-tabler's at main (2026-11-04), cross-checked
| against icon-sets-lucide, -mdi and -carbon, minus what only a vendored pack has:
| the SVGs, the upstream licence copy and pack-history tests (RetiredCopiesTest,
| SanitisedSvgTest, LicenceRecordTest). Two deliberate differences: SECURITY.md
| (the packs lean on the organisation cascade; the stub keeps a per-pack policy
| routing to private vulnerability reporting) and NoStrayTextNodesTest (in the
| three newest packs, not yet in tabler).
|
| Asserted on the rendered pack, never on the stub tree.
|
*/

/** @return list<string> package-root-relative files, sorted */
function shapeFiles(string $root): array
{
    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile()) {
            $files[] = substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1);
        }
    }

    sort($files);

    return $files;
}

/** @return array{string, string} the scratch root and the pack inside it */
function scaffoldShape(string $label, string $type, array $variants = []): array
{
    $root = scratchDirectory($label);

    app(ScaffoldIconPackage::class)(
        PackageDefinition::create(
            name: 'tabler',
            vendor: 'Ichava',
            email: 'ichava@simtabi.com',
            prefix: 'ti',
            type: $type,
            variants: $variants,
        ),
        TargetPath::absolute($root . '/pack'),
    );

    return [$root, $root . '/pack'];
}

/** @return list<string> */
function expectedShape(array $iconDirectories): array
{
    $files = [
        '.editorconfig',
        '.gitattributes',
        '.github/ISSUE_TEMPLATE/bug_report.yml',
        '.github/ISSUE_TEMPLATE/config.yml',
        '.github/ISSUE_TEMPLATE/feature_request.yml',
        '.github/PULL_REQUEST_TEMPLATE.md',
        '.github/actionlint.yaml',
        '.github/dependabot.yml',
        '.github/workflows/changelog.yml',
        '.github/workflows/code-quality.yml',
        '.github/workflows/docs.yml',
        '.github/workflows/release-please.yml',
        '.github/workflows/release.yml',
        '.github/workflows/sync-upstream.yml',
        '.github/workflows/tests.yml',
        '.gitignore',
        '.release-please-manifest.json',
        'ATTRIBUTION.md',
        'CHANGELOG.md',
        'CONTRIBUTING.md',
        'LICENSE',
        'README.md',
        'SECURITY.md',
        'composer.json',
        'docs/architecture.md',
        'docs/attribution.md',
        'docs/configuration.md',
        'docs/customization.md',
        'docs/getting-started.md',
        'docs/installation.md',
        'docs/release.md',
        'docs/variants.md',
        'phpstan.neon.dist',
        'phpunit.xml.dist',
        'release-please-config.json',
        'resources/assets/svg/config.json',
        'resources/lang/en/icons.php',
        'resources/views/components/.gitkeep',
        'src/Constants/IconsConstants.php',
        'src/Enums/Variant.php',
        'src/Providers/IconsServiceProvider.php',
        'src/View/Components/IconComponent.php',
        'tests/Feature/IconsTest.php',
        'tests/Feature/SvgKindTest.php',
        'tests/Pest.php',
        'tests/TestCase.php',
        'tests/Unit/AttributionTest.php',
        'tests/Unit/DocsShapeTest.php',
        'tests/Unit/NoPersonalContactTest.php',
        'tests/Unit/NoStrayTextNodesTest.php',
        'tests/Unit/PhpstanLevelTest.php',
        'tests/Unit/ReleaseVersionTest.php',
        'tests/Unit/ResourceShapeTest.php',
        'tests/Unit/SiblingConstraintsTest.php',
        'tests/Unit/SyncWorkflowCallerTest.php',
        'tests/Unit/VariantTest.php',
        'tests/Unit/WorkflowPermissionsTest.php',
    ];

    foreach ($iconDirectories as $directory) {
        $files[] = 'resources/assets/svg/files/' . ($directory === '' ? '' : $directory . '/') . '.gitkeep';
    }

    sort($files);

    return $files;
}

it('scaffolds exactly the file set of a multi-variant estate pack', function (): void {
    [$root, $pack] = scaffoldShape('shape-multi', 'multi', ['outline', 'filled']);

    expect(shapeFiles($pack))->toBe(expectedShape(['filled', 'outline']));

    removeDirectory($root);
});

it('scaffolds exactly the file set of a flat estate pack', function (): void {
    [$root, $pack] = scaffoldShape('shape-flat', 'single');

    expect(shapeFiles($pack))->toBe(expectedShape(['']));

    removeDirectory($root);
});

it('declares the enum cases it was asked for, and no placeholders', function (): void {
    [$root, $pack] = scaffoldShape('shape-enum', 'multi', ['outline', 'filled']);

    $enum = (string) file_get_contents($pack . '/src/Enums/Variant.php');
    preg_match_all("/^    case (\\w+) = '([^']+)';$/m", $enum, $cases);

    expect(array_combine($cases[1], $cases[2]))->toBe(['OUTLINE' => 'outline', 'FILLED' => 'filled'])
        ->and($enum)->not->toContain('// case')
        ->and($enum)->not->toContain('DEFAULT')
        ->and($enum)->toContain('self::OUTLINE->value');

    // The labels and the enum test name the same cases, in the same order.
    $lang = require $pack . '/resources/lang/en/icons.php';
    expect(array_keys($lang['variants']))->toBe(['outline', 'filled'])
        ->and(array_keys($lang['variant_descriptions']))->toBe(['outline', 'filled'])
        ->and((string) file_get_contents($pack . '/tests/Unit/VariantTest.php'))
        ->toContain("expect(Variant::values())->toBe(['outline', 'filled']);");

    // A flat pack gets the one DEFAULT case, at the files/ root, as icon-sets-lucide has it.
    [$flatRoot, $flat] = scaffoldShape('shape-enum-flat', 'single');
    $flatEnum = (string) file_get_contents($flat . '/src/Enums/Variant.php');

    expect($flatEnum)->toContain("    case DEFAULT = 'default';")
        ->and($flatEnum)->toContain('return IconsConstants::getSvgPath();')
        ->and($flatEnum)->not->toContain('// case');

    removeDirectory($root);
    removeDirectory($flatRoot);
});

it('ships no config file and registers none', function (): void {
    [$root, $pack] = scaffoldShape('shape-config', 'multi', ['outline', 'filled']);

    expect(is_dir($pack . '/config'))->toBeFalse()
        ->and((string) file_get_contents($pack . '/src/Providers/IconsServiceProvider.php'))->not->toContain('hasConfigFile');

    removeDirectory($root);
});

it('calls the reusable sync-pack workflow, pinned to one commit', function (): void {
    [$root, $pack] = scaffoldShape('shape-sync', 'multi', ['outline', 'filled']);

    $sync = (string) file_get_contents($pack . '/.github/workflows/sync-upstream.yml');

    expect(preg_match('#uses: ichava/icon-sets-sync/\.github/workflows/sync-pack\.yml@([0-9a-f]{40}) \##', $sync, $uses))->toBe(1)
        ->and(preg_match('/toolkit-ref: ([0-9a-f]{40})$/m', $sync, $ref))->toBe(1)
        ->and($uses[1])->toBe($ref[1])
        ->and($sync)->not->toContain('docker')
        ->and($sync)->not->toMatch('/^\s*push:/m');

    removeDirectory($root);
});

it('pins third-party actions to a commit and floats first-party ones, with explicit permissions', function (): void {
    [$root, $pack] = scaffoldShape('shape-actions', 'multi', ['outline', 'filled']);

    $workflows = glob($pack . '/.github/workflows/*.yml') ?: [];
    expect($workflows)->toHaveCount(7);

    $references = 0;

    foreach ($workflows as $workflow) {
        $body = (string) file_get_contents($workflow);
        $name = basename($workflow);

        // Every workflow states its token's permissions at the top level, so none
        // depends on the organisation default (S9). release.yml still needs
        // `contents: write` to publish, and release-please.yml grants nothing.
        expect($body)->toMatch('/^permissions:( \{\}|\n  contents: (read|write))$/m', "{$name} declares no top-level permissions");

        preg_match_all('/uses: ([\w.-]+\/[\w.\/-]+)@(\S+)(.*)$/m', $body, $uses, PREG_SET_ORDER);

        foreach ($uses as [, $action, $ref, $comment]) {
            $references++;

            if (preg_match('#^(actions|github|docker)/#', $action)) {
                expect($ref)->toMatch('/^v\d+$/', "{$name}: first-party {$action} must float on its major");

                continue;
            }

            expect($ref)->toMatch('/^[0-9a-f]{40}$/', "{$name}: third-party {$action} must be pinned to a commit");
            expect(trim($comment))->toMatch('/^# \S+/', "{$name}: {$action} needs a version comment");
        }
    }

    // A pattern that stops matching must fail, not pass.
    expect($references)->toBeGreaterThanOrEqual(20);

    removeDirectory($root);
});

it('carries the estate packs\' hygiene, release and author shape', function (): void {
    [$root, $pack] = scaffoldShape('shape-hygiene', 'multi', ['outline', 'filled']);

    expect((string) file_get_contents($pack . '/.gitattributes'))->toContain('/docs                   export-ignore')
        ->and((string) file_get_contents($pack . '/.gitignore'))->toContain('pnpm-lock.yaml')
        ->and((string) file_get_contents($pack . '/phpunit.xml.dist'))->toContain('<env name="APP_ENV" value="testing"/>')
        ->and((string) file_get_contents($pack . '/.github/workflows/tests.yml'))->toContain('coverage: none');

    // The manifest names the version config.json carries, which the pack's own
    // ReleaseVersionTest requires; an empty one failed it on every new pack.
    $manifest = json_decode((string) file_get_contents($pack . '/.release-please-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $config = json_decode((string) file_get_contents($pack . '/resources/assets/svg/config.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($manifest)->toBe(['.' => $config['package']['version']]);

    $composer = json_decode((string) file_get_contents($pack . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($composer['authors'])->toBe([[
        'name'     => 'Simtabi LLC',
        'email'    => 'ichava@simtabi.com',
        'homepage' => 'https://simtabi.com',
        'role'     => 'Developer',
    ]])
        ->and($composer['require-dev'])->toHaveKey('symfony/yaml')
        ->and($composer['support']['docs'])->toBe('https://github.com/ichava/icon-sets-tabler/tree/main/docs');

    expect((string) file_get_contents($pack . '/LICENSE'))->toContain('Simtabi LLC');

    $security = (string) file_get_contents($pack . '/SECURITY.md');
    expect($security)->toContain('private vulnerability reporting')
        ->and($security)->toContain('mailto:security@simtabi.com');

    // Provenance manifests are spelled lowercase across ichava (source.json,
    // vendored.json). A pack ships neither, and must never ship the old spelling.
    $uppercase = array_filter(shapeFiles($pack), static fn (string $f): bool => in_array(basename($f), ['SOURCE.json', 'VENDORED.json'], true));
    expect($uppercase)->toBe([]);

    removeDirectory($root);
});
