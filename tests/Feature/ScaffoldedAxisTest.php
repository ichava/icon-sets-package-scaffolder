<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\IconSetType;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
|--------------------------------------------------------------------------
| Both axes scaffold, and each produces the shape a real pack has
|--------------------------------------------------------------------------
|
| Read off the RENDERED pack, never the stub tree. A stub is not a document
| and `{{token}}` is not valid in most of the grammars involved, so a stub
| that looks right is not evidence the output is.
|
| Before this, the generator emitted `Variant` unconditionally: a scaffolded
| pack could not look like `icon-sets-bundled`, `icon-sets-metronic` or
| `icon-sets-emoji`, which is three of the five packs in its own estate.
|
*/

/**
 * @param list<string> $variants
 */
function renderAxis(string $axis, array $variants = []): string
{
    $root = sys_get_temp_dir() . '/ichava-axis-' . $axis . '-' . bin2hex(random_bytes(4));

    $args = ['name' => 'Beacon', 'vendor' => 'Acme', 'email' => 'd@e.com', 'axis' => $axis];

    if ($variants !== []) {
        $args['type'] = IconSetType::Multi;
        $args['variants'] = $variants;
    }

    app(ScaffoldIconPackage::class)(
        PackageDefinition::create(...$args),
        TargetPath::absolute($root),
        force: true,
    );

    return $root;
}

function removeTree(string $root): void
{
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($it as $e) {
        $e->isDir() ? @rmdir($e->getPathname()) : @unlink($e->getPathname());
    }
    @rmdir($root);
}

dataset('axes', [
    'variant'  => ['variant', 'Variant', 'variants', 'Category'],
    'category' => ['category', 'Category', 'categories', 'Variant'],
]);

it('emits the enum, the docs page and the groups its axis names', function (
    string $axis,
    string $studly,
    string $plural,
    string $other,
) {
    $root = renderAxis($axis);

    expect(is_file("{$root}/src/Enums/{$studly}.php"))
        ->toBeTrue("no src/Enums/{$studly}.php");
    expect(is_file("{$root}/docs/{$plural}.md"))
        ->toBeTrue("no docs/{$plural}.md");

    // The other axis must not be emitted alongside it.
    expect(is_file("{$root}/src/Enums/{$other}.php"))
        ->toBeFalse("scaffolded {$other}.php for an {$axis} pack");

    $lang = require "{$root}/resources/lang/en/icons.php";
    expect($lang)->toHaveKey($plural);

    removeTree($root);
})->with('axes');

it('satisfies the shape guard it ships with, on both axes', function (
    string $axis,
    string $studly,
    string $plural,
    string $other,
) {
    // ResourceShapeTest DISCOVERS its groups and derives the enum name --
    // `categories` -> `Category`, not `Categorie`. That generalisation was
    // written for packs this generator could not make; this is the first
    // thing to exercise it against one.
    //
    // Its derivation is replicated here rather than the class being loaded:
    // the rendered enum `use`s core's HasIconSetVariants, and this package
    // does not depend on core (a dependency back would be a cycle). Case
    // VALUES are read out of the file, so the group-matches-enum contract is
    // checked for real, only without reflection.
    $root = renderAxis($axis);

    $lines = require "{$root}/resources/lang/en/icons.php";
    $checked = 0;

    foreach (array_keys($lines) as $group) {
        if (! is_array($lines[$group])) {
            continue;
        }

        $singular = str_ends_with($group, 'ies')
            ? substr($group, 0, -3) . 'y'
            : rtrim($group, 's');

        if (! isset($lines[$singular . '_descriptions'])) {
            continue;
        }

        $enumFile = "{$root}/src/Enums/" . ucfirst($singular) . '.php';
        expect(is_file($enumFile))->toBeTrue(
            "[{$group}] is a labelled group with no src/Enums/" . ucfirst($singular) . '.php',
        );

        // Anchored to the line start: the stub carries five COMMENTED-OUT
        // example cases and a docblock listing them again. An unanchored
        // pattern reads all three and reports nine cases against one.
        preg_match_all("/^\\s*case \\w+ = '([^']+)'/m", (string) file_get_contents($enumFile), $m);
        $cases = $m[1];
        sort($cases);
        expect($cases)->not->toBeEmpty();

        foreach ([$group, $singular . '_descriptions'] as $pair) {
            $keys = array_keys($lines[$pair]);
            sort($keys);
            expect($keys)->toBe($cases, "[{$pair}] does not match " . ucfirst($singular));
        }

        $checked++;
    }

    // A loop that pairs on the wrong key checks nothing and passes.
    expect($checked)->toBe(1, "the {$axis} pack declared no labelled group");

    removeTree($root);
})->with('axes');

it('puts the axis values in the config.json key core reads them from', function (
    string $axis,
    string $studly,
    string $plural,
    string $other,
) {
    // Both keys are always present and `JsonConfigConstants` reads each by
    // its literal name, so a config that renamed `variants` to match its axis
    // would make `getVariants()` answer an empty array. Read off a real pack
    // 2026-09-21: metronic, bundled and flag all ship `variants` AND
    // `categories` under `metadata.data`, whatever enum they expose.
    // Values are required here. With none, BOTH slots render `{}` and the
    // assertion below passes whichever slot the generator filled -- which is
    // exactly how the first version of this test passed its own mutation.
    $root = renderAxis($axis, ['outline', 'filled']);

    $config = json_decode(
        (string) file_get_contents("{$root}/resources/assets/svg/config.json"),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $data = $config['metadata']['data'];

    expect($data)->toHaveKeys(['variants', 'categories']);

    // Empty side must be a JSON object, not `[]` -- the consumer reads a map.
    $filledSlot = $axis === 'variant' ? 'variants' : 'categories';
    $emptySlot = $axis === 'variant' ? 'categories' : 'variants';

    expect(array_keys($data[$filledSlot]))->toBe(['outline', 'filled']);
    expect($data[$emptySlot])->toBe([]);
    expect(file_get_contents("{$root}/resources/assets/svg/config.json"))
        ->toContain("\"{$emptySlot}\": {}");

    removeTree($root);
})->with('axes');

it('leaves no unrendered axis token anywhere in the output', function (
    string $axis,
) {
    $root = renderAxis($axis);

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    $offenders = [];
    $scanned = 0;
    foreach ($files as $file) {
        if ($file->isDir()) {
            continue;
        }
        $scanned++;
        $body = (string) file_get_contents($file->getPathname());
        if (preg_match('/\{\{axis[A-Za-z]*\}\}/', $body)) {
            $offenders[] = str_replace($root . '/', '', $file->getPathname());
        }
    }

    expect($scanned)->toBeGreaterThan(0, 'the scan matched no files');
    expect($offenders)->toBe([]);

    removeTree($root);
})->with('axes');
