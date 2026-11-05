<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\Testing\PackConformance;

/*
|--------------------------------------------------------------------------
| A pack depends on ichava/core, and on nothing else
|--------------------------------------------------------------------------
|
| The rule lives once, in core's Testing\PackConformance; this file names it
| for this pack, so the pack's test names stay as they were.
|
| `composer pint` runs `vendor/bin/laranail-pint`, which reaches the pack through
| core's require: a sanctioned transitive dev tool, not a pack dependency.
|
*/

it('requires php and ichava/core, and nothing else', function (): void {
    PackConformance::requiresOnlyCore(dirname(__DIR__, 2));
});

it('declares the composer type core discovers packs by', function (): void {
    PackConformance::declaresPackType(dirname(__DIR__, 2));
});

it('imports no laranail namespace but ichava/core from src/', function (): void {
    PackConformance::importsOnlyCore(dirname(__DIR__, 2));
});
