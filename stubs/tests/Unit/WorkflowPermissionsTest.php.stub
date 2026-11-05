<?php

declare(strict_types=1);

/**
 * Every workflow declares a top-level `permissions:` block, so the token each job gets does not
 * depend on the organisation's default. A job that needs more asks for it at job level.
 *
 * @return list<string>
 */
function workflow_files(): array
{
    $dir = dirname(__DIR__, 2) . '/.github/workflows';

    return array_values(array_merge(glob($dir . '/*.yml') ?: [], glob($dir . '/*.yaml') ?: []));
}

it(description: 'reads the workflow files it is meant to guard', closure: function () {
    // Floor: the number of workflows today. A glob that stops matching must fail, not pass empty.
    expect(count(workflow_files()))->toBeGreaterThanOrEqual(7);
});

it(description: 'declares a top-level permissions block in every workflow', closure: function () {
    $missing = [];

    foreach (workflow_files() as $file) {
        if (! preg_match('/^permissions:/m', (string) file_get_contents($file))) {
            $missing[] = basename($file);
        }
    }

    expect($missing)->toBe([]);
});
