<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\Testing\PackConformance;

/*
|--------------------------------------------------------------------------
| The sync caller calls icon-sets-sync's reusable workflow
|--------------------------------------------------------------------------
|
| The rule lives once, in core's Testing\PackConformance; this file names it
| for this pack, so the pack's test names stay as they were.
|
*/

it('calls the reusable sync-pack workflow pinned to a 40-hex commit', function (): void {
    PackConformance::syncCallerIsPinned(dirname(__DIR__, 2));
});

it('runs on a schedule and a manual dispatch, never on push', function (): void {
    PackConformance::syncCallerTriggers(dirname(__DIR__, 2));
});

it('declares only the permissions the called workflow needs', function (): void {
    PackConformance::syncCallerPermissions(dirname(__DIR__, 2));
});
