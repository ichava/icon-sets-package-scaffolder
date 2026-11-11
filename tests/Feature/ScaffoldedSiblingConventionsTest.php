<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| A fresh scaffold matches the conventions the real packs share
|--------------------------------------------------------------------------
|
| Queue item Q14. Scaffolding icon-sets-openmoji on 2026-11-04 came out with a
| namespace no sibling uses, no Dependabot config, no issue templates, three
| guard tests short, and a SECURITY.md that routed disclosure to ichava@. The
| stub tree is measured against icon-sets-openmoji, -emoji and -tabler, so each
| assertion below names a file those packs carry byte for byte, or differ from
| only by the pack name.
|
| Asserted on the rendered pack, never the stub tree.
|
*/

function scaffoldIchavaPack(string $name, string $label): array
{
    $root = scratchDirectory($label);

    app(ScaffoldIconPackage::class)->handle(
        PackageDefinition::create(name: $name, vendor: 'Ichava', email: 'ichava@simtabi.com'),
        TargetPath::absolute($root . '/pack'),
    );

    return [$root, $root . '/pack'];
}

it('uses the estate namespace root for the ichava vendor', function (): void {
    [$root, $pack] = scaffoldIchavaPack('openmoji', 'sib-ns');

    $composer = json_decode((string) file_get_contents($pack . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['autoload']['psr-4'])->toHaveKey('Simtabi\\Laranail\\Ichava\\IconSetsOpenmoji\\')
        ->and($composer['autoload-dev']['psr-4'])->toHaveKey('Simtabi\\Laranail\\Ichava\\IconSetsOpenmoji\\Tests\\')
        ->and($composer['extra']['laravel']['providers'])
        ->toContain('Simtabi\\Laranail\\Ichava\\IconSetsOpenmoji\\Providers\\IconsServiceProvider');

    $provider = (string) file_get_contents($pack . '/src/Providers/IconsServiceProvider.php');
    expect($provider)->toContain('namespace Simtabi\\Laranail\\Ichava\\IconSetsOpenmoji\\Providers;');

    removeDirectory($root);
});

it('ships the Dependabot config, issue templates and PR template the packs share', function (): void {
    [$root, $pack] = scaffoldIchavaPack('openmoji', 'sib-gh');

    foreach ([
        '.github/dependabot.yml',
        '.github/PULL_REQUEST_TEMPLATE.md',
        '.github/ISSUE_TEMPLATE/bug_report.yml',
        '.github/ISSUE_TEMPLATE/feature_request.yml',
        '.github/ISSUE_TEMPLATE/config.yml',
        '.editorconfig',
    ] as $file) {
        expect(is_file($pack . '/' . $file))->toBeTrue("{$file} was not scaffolded");
    }

    $dependabot = (string) file_get_contents($pack . '/.github/dependabot.yml');
    expect($dependabot)->toContain('package-ecosystem: composer')
        ->and($dependabot)->toContain('package-ecosystem: github-actions')
        ->and($dependabot)->toContain('interval: weekly')
        ->and($dependabot)->toContain('composer-minor-and-patch');

    // The templates name the pack, never the template's own repository.
    foreach (['bug_report.yml', 'feature_request.yml', 'config.yml'] as $template) {
        $body = (string) file_get_contents($pack . '/.github/ISSUE_TEMPLATE/' . $template);
        expect($body)->toContain('ichava/icon-sets-openmoji');
    }

    $config = (string) file_get_contents($pack . '/.github/ISSUE_TEMPLATE/config.yml');
    expect($config)->toContain('blank_issues_enabled: false')
        ->and($config)->toContain('https://github.com/ichava/icon-sets-openmoji/security/policy');

    removeDirectory($root);
});

it('ships the guard tests every real pack carries', function (): void {
    [$root, $pack] = scaffoldIchavaPack('openmoji', 'sib-guards');

    foreach ([
        'tests/Unit/NoPersonalContactTest.php',
        'tests/Unit/PhpstanLevelTest.php',
        'tests/Unit/ReleaseVersionTest.php',
        'tests/Unit/SiblingConstraintsTest.php',
        'tests/Unit/AttributionTest.php',
        'tests/Unit/DocsShapeTest.php',
        'tests/Unit/ResourceShapeTest.php',
    ] as $guard) {
        expect(is_file($pack . '/' . $guard))->toBeTrue("{$guard} was not scaffolded");
    }

    // The released-version guard reads release-please's extra-files, so the
    // generated config has to list config.json there.
    $release = json_decode((string) file_get_contents($pack . '/release-please-config.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($release['packages']['.']['extra-files'])->toContain(
        ['type' => 'json', 'path' => 'resources/assets/svg/config.json', 'jsonpath' => '$.package.version'],
    );

    // Warnings, risky tests and an empty suite fail the run, as in every pack.
    $phpunit = (string) file_get_contents($pack . '/phpunit.xml.dist');
    foreach (['failOnWarning', 'failOnRisky', 'failOnEmptyTestSuite', 'beStrictAboutOutputDuringTests'] as $flag) {
        expect($phpunit)->toContain($flag . '="true"');
    }

    removeDirectory($root);
});

it('names security@ as the only disclosure address and never ichava@', function (): void {
    [$root, $pack] = scaffoldIchavaPack('openmoji', 'sib-sec');

    // icon-sets-openmoji, -emoji and -tabler rely on the organisation cascade,
    // but the stub stays (owner decision pending); it must agree with that policy.
    expect(is_file($pack . '/SECURITY.md'))->toBeTrue();

    $policy = (string) file_get_contents($pack . '/SECURITY.md');
    expect($policy)->toContain('security@simtabi.com')
        ->and($policy)->toContain('private vulnerability reporting')
        ->and(strpos($policy, 'private vulnerability reporting'))->toBeLessThan(strpos($policy, 'security@simtabi.com'))
        ->and($policy)->toContain('https://github.com/ichava/icon-sets-openmoji/security/policy');

    $walked = 0;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pack, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $walked++;
        foreach (file($file->getPathname()) ?: [] as $line) {
            // `ichava@` is the author and contact role address. It is wrong as a
            // disclosure address; SECURITY.md may name it only to say it is not one.
            if (stripos($line, 'ichava@') === false || preg_match('/vulnerab|security|disclos/i', $line) !== 1) {
                continue;
            }
            if (basename($file->getPathname()) === 'SECURITY.md' && preg_match('/community|handle|delay/i', $line) === 1) {
                continue;
            }
            $this->fail("{$file->getPathname()} routes disclosure to ichava@: " . trim($line));
        }
    }

    expect($walked)->toBeGreaterThan(40);

    // README and CONTRIBUTING send reporters to the repository's policy page.
    foreach (['README.md', 'CONTRIBUTING.md'] as $doc) {
        $body = (string) file_get_contents($pack . '/' . $doc);
        expect($body)->toContain('https://github.com/ichava/icon-sets-openmoji/security/policy')
            ->and($body)->not->toContain('](SECURITY.md)');
    }

    removeDirectory($root);
});

it('keeps a non-ichava vendor on its own namespace root', function (): void {
    $root = scratchDirectory('sib-acme');

    app(ScaffoldIconPackage::class)->handle(
        PackageDefinition::create(name: 'Hero', vendor: 'Acme', email: 'dev@example.com'),
        TargetPath::absolute($root . '/pack'),
    );

    $composer = json_decode((string) file_get_contents($root . '/pack/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($composer['autoload']['psr-4'])->toHaveKey('Acme\\IconSetsHero\\');

    removeDirectory($root);
});
