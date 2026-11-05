<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\Testing\PackConformance;

/*
|--------------------------------------------------------------------------
| Every ichava/* constraint admits 0.1.0
|--------------------------------------------------------------------------
|
| The rule lives once, in core's Testing\PackConformance; this file names it
| for this pack, so the pack's test names stay as they were.
|
| CI aliases core's main into the 0.1 line and restores composer.json after the
| install, so this reads the committed constraints, never the CI alias.
|
*/

it('admits 0.1.0 in every ichava/* constraint', function (): void {
    PackConformance::siblingConstraintsAdmit(dirname(__DIR__, 2));
});
