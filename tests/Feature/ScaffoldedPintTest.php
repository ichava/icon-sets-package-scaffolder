<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/**
 * A scaffolded pack runs `composer pint` (laranail-pint --test) in its own CI,
 * so a pack that fails it is red on its first pull request. Nothing here looked
 * at the rendered PHP with Pint: the stubs are excluded from this repository's
 * own Pint run, because a `.stub` holding `{{namespace}}` is not valid PHP.
 *
 * So the check runs on the output. Several definitions are rendered because the
 * import order Pint wants depends on how long the vendor and package names are
 * (see PhpImportOrderer): one name passing proves little about another.
 *
 * PHPUnit assertions rather than `expect()`, for the diagnostic message.
 */
it('scaffolds packs that pass the shared Pint config', function (): void {
    $root = scratchDirectory('pint');

    $definitions = [
        'short-single' => ['name' => 'Hero', 'vendor' => 'Acme'],
        'long-single'  => ['name' => 'Material Design Community', 'vendor' => 'Northwind Traders Worldwide'],
        'short-multi'  => ['name' => 'Ux', 'vendor' => 'Ab', 'type' => 'multi', 'variants' => ['outline', 'solid']],
        'long-multi'   => ['name' => 'Phosphor Duotone Collection', 'vendor' => 'Simtabi', 'type' => 'multi', 'variants' => ['regular', 'bold', 'duotone']],
    ];

    foreach ($definitions as $label => $overrides) {
        $definition = PackageDefinition::create(...array_merge(['email' => 'dev@example.com'], $overrides));

        app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root . '/' . $label));
    }

    // Assert the size of what Pint is about to inspect, so a render that wrote no
    // PHP cannot pass by giving Pint nothing to read.
    $phpFiles = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        $phpFiles += str_ends_with($file->getFilename(), '.php') ? 1 : 0;
    }
    $this->assertGreaterThanOrEqual(10 * count($definitions), $phpFiles, 'Too few PHP files were rendered for the Pint check to mean anything.');

    $packageRoot = dirname(__DIR__, 2);
    $pint = new Process([PHP_BINARY, $packageRoot . '/vendor/bin/laranail-pint', '--test', $root], $packageRoot);
    $pint->setTimeout(300);
    $pint->run();

    removeDirectory($root);

    $this->assertSame(
        0,
        $pint->getExitCode(),
        "A scaffolded pack fails laranail-pint --test:\n" . $pint->getOutput() . $pint->getErrorOutput(),
    );
});
