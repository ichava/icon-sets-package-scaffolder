<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\Testing\PackConformance;

/*
|--------------------------------------------------------------------------
| Every workflow declares its token's permissions
|--------------------------------------------------------------------------
|
| The rule lives once, in core's Testing\PackConformance; this file names it
| for this pack, so the pack's test names stay as they were.
|
*/

it('reads the workflow files it is meant to guard', function (): void {
    PackConformance::workflowsDeclarePermissions(dirname(__DIR__, 2), minFiles: 7);
});

it('declares a top-level permissions block in every workflow', function (): void {
    PackConformance::workflowsDeclarePermissions(dirname(__DIR__, 2), minFiles: 7);
});
