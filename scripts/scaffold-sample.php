<?php

declare(strict_types=1);

/**
 * Renders the stub tree into a directory, with no terminal and no Laravel app.
 *
 * This exists so CI can lint what a scaffolded package actually receives. The
 * stub tree ships four workflow files, and until now nothing looked at them:
 * `actionlint` reads `.github/workflows/`, which is this package's own CI, not
 * the CI it generates. A mustache token sits inside a `${{ }}` expression and a
 * `.stub` extension keeps the file out of every YAML tool, so a broken
 * scaffolded workflow was first observable in somebody else's repository.
 *
 * Usage:
 *
 *     php scripts/scaffold-sample.php <destination>
 *
 * The container is real rather than hand-wired: the provider binds exactly one
 * thing, `StubLocator`, and everything else is autowired. Hand-wiring would be
 * four `new` calls that drift from the provider the first time a constructor
 * gains an argument, and drift here means CI lints a tree the command does not
 * produce.
 */

use Illuminate\Container\Container;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconSetType;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\StubLocator;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

require __DIR__ . '/../vendor/autoload.php';

$destination = $argv[1] ?? null;

if ($destination === null || trim($destination) === '') {
    fwrite(STDERR, "usage: php scripts/scaffold-sample.php <destination>\n");
    exit(2);
}

$stubsRoot = __DIR__ . '/../stubs/icon-package';

$container = new Container;
$container->bind(StubLocator::class, fn (): StubLocator => new StubLocator($stubsRoot));

/** @var ScaffoldIconPackage $scaffold */
$scaffold = $container->make(ScaffoldIconPackage::class);

// A multi-variant pack, because it is the shape with more moving parts: it
// renders the variant directories and the `{{variantsJson}}` token a
// single-set pack never reaches.
$definition = PackageDefinition::create(
    name: 'Sample',
    vendor: 'Acme',
    email: 'dev@example.com',
    type: IconSetType::Multi,
    variants: ['outline', 'filled'],
);

$result = $scaffold($definition, TargetPath::absolute($destination), force: true);

printf("%d files, %d directories -> %s\n", $result->fileCount(), count($result->directories), $destination);
