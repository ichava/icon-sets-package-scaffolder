<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\Testing\PackConformance;

/*
|--------------------------------------------------------------------------
| PHPStan runs at level max, with no baseline
|--------------------------------------------------------------------------
|
| The rule lives once, in core's Testing\PackConformance; this file names it
| for this pack, so the pack's test names stay as they were.
|
*/

it('configures PHPStan at level max', function (): void {
    PackConformance::analysesAtLevelMax(dirname(__DIR__, 2));
});

it('analyses without a baseline', function (): void {
    PackConformance::analysesWithoutBaseline(dirname(__DIR__, 2));
});
