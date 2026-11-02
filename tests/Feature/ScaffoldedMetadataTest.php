<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| A scaffolded pack is not born claiming its own repo is its homepage
|--------------------------------------------------------------------------
|
| Asserted on the RENDERED pack, never on the stub tree: the stub's value is
| `{{packageName}}`, which tells you nothing about what the pack ends up
| holding.
|
| The stub used to emit
|
|     "homepage":   "https://github.com/{{packageName}}",
|     "repository": "https://github.com/{{packageName}}",
|
| so every pack generated from it was born with two identical URLs under
| different names, and two packs in the estate shipped exactly that. The
| browser API allows `homepage` through `publicMetadata()` beside
| `repository`, so both reach a consumer.
|
| Settled 2026-09-22: `metadata.homepage` is the UPSTREAM project's own site.
| The scaffolder cannot know it, so it emits no key at all rather than a
| plausible-looking wrong one -- `NextStepsReporter` asks for it instead.
|
*/

it('scaffolds no homepage, rather than one pointing at the new package itself', function (): void {
    $root = sys_get_temp_dir() . '/ichava-meta-' . bin2hex(random_bytes(4));

    $definition = PackageDefinition::create(
        name: 'Beacon',
        vendor: 'Acme',
        email: 'dev@example.com',
    );
    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root), force: true);

    $config = json_decode(
        (string) file_get_contents($root . '/resources/assets/svg/config.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $metadata = $config['metadata'];

    // The key is absent. Every reader handles that: IconRegistry uses
    // `?? null`, getHomepage() uses `?? ''`, and publicMetadata()'s
    // array_intersect_key drops absent keys.
    expect($metadata)->not->toHaveKey('homepage');

    // repository is still ours, and still present -- this change must not have
    // taken the sibling field with it.
    expect($metadata['repository'])->toBe('https://github.com/acme/icon-sets-beacon');

    // The rule, asserted independently of the key's absence: whatever a
    // scaffolded pack ends up holding, homepage must never equal repository.
    // Written this way so it keeps working if the stub ever emits a real value.
    expect($metadata['homepage'] ?? null)->not->toBe($metadata['repository']);

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($it as $entry) {
        $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
    }
    @rmdir($root);
});

/*
|--------------------------------------------------------------------------
| ...and it ships a guard of its own, so the value cannot drift later
|--------------------------------------------------------------------------
|
| The case above proves the scaffolder emits the right thing. It says nothing
| about what happens afterwards, and afterwards is where this went wrong: all
| five estate packs were generated correctly and had `metadata.homepage` added
| or drifted onto their own repository later, by hand. Each then needed the
| assertion added by hand too, five separate pull requests in one week.
|
| A pack that is born with the guard needs none of that. The stub asserts the
| RULE rather than a value, because the scaffolder cannot know the upstream.
|
*/

it('scaffolds a metadata guard, so the value cannot drift once the pack leaves here', function (): void {
    $root = sys_get_temp_dir() . '/ichava-metaguard-' . bin2hex(random_bytes(4));

    $definition = PackageDefinition::create(
        name: 'Beacon',
        vendor: 'Acme',
        email: 'dev@example.com',
    );
    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root), force: true);

    $guard = $root . '/tests/Unit/PackageMetadataTest.php';

    expect(file_exists($guard))->toBeTrue(
        'Scaffolded packages ship no tests/Unit/PackageMetadataTest.php, so nothing in the '
        . 'generated pack stops metadata.homepage drifting onto its own repository.',
    );

    $body = (string) file_get_contents($guard);

    // Both halves, named separately: the rendered repository URL, and the rule
    // that homepage is never one of ours. A guard carrying only the first would
    // pass this case while asserting nothing about the field that drifted.
    expect($body)
        ->toContain("toBe('https://github.com/acme/icon-sets-beacon')")
        ->and($body)->toContain("not->toContain('github.com/acme/')");

    // It must be a file of its own. A top-level it() sharing a file with a
    // PHPUnit class makes Pest skip that class entirely -- the defect that
    // silently switched off 19 assertions across three packs.
    expect(preg_match('/^class /m', $body))->toBe(
        0,
        'The metadata guard shares a file with a PHPUnit class; Pest will collect neither reliably.',
    );

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($it as $entry) {
        $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
    }
    @rmdir($root);
});
