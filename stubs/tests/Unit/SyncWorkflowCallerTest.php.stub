<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/**
 * The upstream sync is a caller of icon-sets-sync's reusable workflow, pinned to a
 * commit, never triggered by a push, and holding only the permissions the called
 * workflow needs.
 *
 * @return array<string, mixed>
 */
function sync_caller(): array
{
    $path = dirname(__DIR__, 2) . '/.github/workflows/sync-upstream.yml';
    expect(is_file($path))->toBeTrue();

    return Yaml::parseFile($path);
}

it(description: 'calls the reusable sync-pack workflow pinned to a 40-hex commit', closure: function () {
    $job = sync_caller()['jobs']['sync'] ?? [];

    expect($job['uses'] ?? '')->toMatch('#^ichava/icon-sets-sync/\.github/workflows/sync-pack\.yml@[0-9a-f]{40}$#');
    expect($job['with']['toolkit-ref'] ?? '')->toMatch('/^[0-9a-f]{40}$/');
    expect(explode('@', $job['uses'])[1])->toBe($job['with']['toolkit-ref']);
});

it(description: 'runs on a schedule and a manual dispatch, never on push', closure: function () {
    $on = sync_caller()['on'] ?? null;

    expect($on)->toBeArray();
    expect(array_keys($on))->toBe(['schedule', 'workflow_dispatch']);
    expect($on['schedule'])->toHaveCount(1);
});

it(description: 'declares only the permissions the called workflow needs', closure: function () {
    $workflow = sync_caller();

    expect($workflow['permissions'])->toBe(['contents' => 'read']);
    expect($workflow['jobs']['sync']['permissions'])->toBe(['contents' => 'read', 'issues' => 'write']);
    expect(array_keys($workflow['jobs']))->toBe(['sync']);
});
