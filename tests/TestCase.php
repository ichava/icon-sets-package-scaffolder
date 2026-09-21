<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Providers\IconPackageScaffolderServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [IconPackageScaffolderServiceProvider::class];
    }
}
