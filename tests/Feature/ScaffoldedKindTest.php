<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconKind;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\Commands\MakeIconPackageCommand;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/*
|--------------------------------------------------------------------------
| A scaffolded pack declares the SVG kind it was asked for
|--------------------------------------------------------------------------
|
| `package.kind` in config.json is the pack-wide kind ichava/core resolves
| for every category that names none. Asserted on the RENDERED pack: the
| stub holds `{{kind}}`, which says nothing about what a pack ends up with.
|
| Also pinned here: the copies the estate packs dropped on 2026-10-30 stay
| out of what the generator emits -- the `commands`/`info` translation
| groups, and the prefix copied into the config file.
|
*/

beforeEach(function () {
    $this->kindRoot = sys_get_temp_dir() . '/ichava-kind-' . bin2hex(random_bytes(4));
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->kindRoot);
});

/** @return array<string, mixed> */
function scaffoldedKindConfig(string $root): array
{
    return json_decode(
        (string) file_get_contents($root . '/resources/assets/svg/config.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

it('declares icon by default', function () {
    $definition = PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'dev@example.com');

    expect($definition->kind)->toBe(IconKind::Icon);

    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($this->kindRoot), force: true);

    expect(scaffoldedKindConfig($this->kindRoot)['package']['kind'])->toBe('icon');
});

it('declares the kind it was given', function (string $input, string $expected) {
    $definition = PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'dev@example.com', kind: $input);

    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($this->kindRoot), force: true);

    expect(scaffoldedKindConfig($this->kindRoot)['package']['kind'])->toBe($expected);
})->with([
    'icon'                    => ['icon', 'icon'],
    'illustration'            => ['illustration', 'illustration'],
    'case and space forgiven' => [' Illustration ', 'illustration'],
]);

it('takes the kind from --kind', function () {
    $this->artisan(MakeIconPackageCommand::class, [
        'name'             => 'Scenes',
        '--vendor'         => 'Acme',
        '--email'          => 'dev@example.com',
        '--kind'           => 'illustration',
        '--path'           => $this->kindRoot,
        '--force'          => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(scaffoldedKindConfig($this->kindRoot)['package']['kind'])->toBe('illustration');
});

it('refuses a kind core would not recognise', function () {
    PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'dev@example.com', kind: 'picture');
})->throws(InvalidPackageDefinition::class, 'Unknown icon kind "picture"');

it('emits no update-command or about translations, and no prefix copy in config', function () {
    $definition = PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'dev@example.com');
    app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($this->kindRoot), force: true);

    $lang = require $this->kindRoot . '/resources/lang/en/icons.php';

    expect($lang)->not->toHaveKey('commands')
        ->and($lang)->not->toHaveKey('info')
        // The descriptions core loads beside the labels are still there.
        ->and(array_keys($lang['variant_descriptions']))->toBe(array_keys($lang['variants']));

    // The prefix lives in config.json (`config.icon_prefix`), which
    // IconsConstants reads; the config file's copy was read by nothing.
    $configFile = (string) file_get_contents($this->kindRoot . '/config/icon-sets-beacon.php');

    expect(str_contains($configFile, "'prefix'"))->toBeFalse('the config file still copies the prefix');
});
