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
