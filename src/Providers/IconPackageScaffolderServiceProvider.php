<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Providers;

use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Exceptions\InvalidPath;
use Simtabi\Laranail\Package\Tools\Exceptions\InvalidPackage;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\StubLocator;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\Commands\MakeIconPackageCommand;

/**
 * Registers the scaffolder with a host application.
 *
 * Everything else in this package is plain PHP with no container behind it,
 * so this provider is the only file a non-Laravel consumer would skip. That is
 * the point of the split: `ScaffoldIconPackage` can be constructed by hand.
 */
final class IconPackageScaffolderServiceProvider extends PackageServiceProvider
{
    /**
     * @throws InvalidPath
     * @throws InvalidPackage
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->setName('ichava/icon-sets-package-scaffolder')
            ->setPathFrom(source: $this, levelsUp: 2)
            ->hasConfigFile('icon-sets-package-scaffolder')
            ->hasCommands(MakeIconPackageCommand::class);
    }

    public function registeringPackage(): void
    {
        // Bound rather than autowired because the constructor takes a string,
        // which the container cannot resolve on its own. Resolved lazily so a
        // config change between boot and use is honoured, and so a consumer can
        // rebind it in a test without touching config at all.
        $this->app->bind(StubLocator::class, fn (): StubLocator => new StubLocator($this->stubsRoot()));
    }

    public function packageBooted(): void
    {
        $this->publishes(
            [$this->bundledStubsRoot() => base_path('stubs/ichava/icon-package')],
            'ichava::icon-sets-package-scaffolder-stubs',
        );
    }

    private function stubsRoot(): string
    {
        $configured = config('ichava.icon-sets-package-scaffolder.stubs_path');

        return is_string($configured) && trim($configured) !== ''
            ? rtrim($configured, '/\\')
            : $this->bundledStubsRoot();
    }

    private function bundledStubsRoot(): string
    {
        return $this->package->basePath('stubs/icon-package');
    }
}
