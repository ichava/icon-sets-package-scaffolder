<?php

declare(strict_types=1);

/**
 * Nothing this package ships names a person (decided 2026-10-28, finding W-20).
 *
 * The author is Simtabi LLC and the contact is the role address
 * ichava@simtabi.com. The guard reads composer.json, README.md, LICENSE and every
 * file under docs/, src/, .github/ and .dev/ (the workflows set the sync bot's commit
 * identity, and .dev/ holds generated tooling output), and fails on a personal name or mailbox. It also
 * fails when it read fewer files than it did when written, so a path that stops
 * matching cannot pass by reading nothing.
 */
function personal_contact_root(): string
{
    return dirname(__DIR__, 2);
}

/** @return list<string> */
function personal_contact_files(): array
{
    $root = personal_contact_root();
    $files = [];
    foreach (['composer.json', 'README.md', 'LICENSE'] as $file) {
        if (is_file("{$root}/{$file}")) {
            $files[] = "{$root}/{$file}";
        }
    }
    foreach (['docs', 'src', '.github', '.dev'] as $dir) {
        if (! is_dir("{$root}/{$dir}")) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if ($entry->isFile()) {
                $files[] = $entry->getPathname();
            }
        }
    }

    return $files;
}

it(description: 'names Simtabi LLC and the role address as the only author', closure: function () {
    $composer = json_decode((string) file_get_contents(personal_contact_root() . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($composer['authors'])->toHaveCount(1);
    expect($composer['authors'][0]['name'])->toBe('Simtabi LLC');
    expect($composer['authors'][0]['email'])->toBe('ichava@simtabi.com');
});

it(description: 'ships no personal name or email address', closure: function () {
    $files = personal_contact_files();
    // 16 files measured 2026-10-30; a walk matching nothing must not pass.
    expect(count($files))->toBeGreaterThanOrEqual(16);
    foreach ($files as $file) {
        $this->assertDoesNotMatchRegularExpression('/imani@|manyara/i', (string) file_get_contents($file), "{$file} names a person; use Simtabi LLC <ichava@simtabi.com>");
    }
});
