<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PHPStan runs at level max, with no baseline
|--------------------------------------------------------------------------
|
| The level is the gate's whole strength, and lowering it is a one-line edit
| that turns nothing red: the analysis simply passes at the weaker level. So
| the configured level is pinned here, read from phpstan.neon.dist itself, and
| so is the absence of a baseline, which would hide errors the same way.
|
| Pest functions only: a PHPUnit class in the same file would stop being
| collected.
|
*/

it('configures PHPStan at level max', function (): void {
    $path = dirname(__DIR__, 2) . '/phpstan.neon.dist';
    $this->assertFileExists($path);

    $count = preg_match_all('/^\s*level:\s*(\S+)\s*$/m', (string) file_get_contents($path), $matches);

    $this->assertSame(1, $count, 'phpstan.neon.dist must set exactly one level.');
    $this->assertSame('max', $matches[1][0], 'phpstan.neon.dist must analyse at level max.');
});

it('analyses without a baseline', function (): void {
    $root = dirname(__DIR__, 2);

    $this->assertFileDoesNotExist($root . '/phpstan-baseline.neon');
    $this->assertStringNotContainsString(
        'baseline',
        (string) file_get_contents($root . '/phpstan.neon.dist'),
        'phpstan.neon.dist must not include a baseline.',
    );
});
