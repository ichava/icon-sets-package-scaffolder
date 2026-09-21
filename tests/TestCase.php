<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconPackageScaffolder\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Providers\IconPackageScaffolderServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [IconPackageScaffolderServiceProvider::class];
    }
}
