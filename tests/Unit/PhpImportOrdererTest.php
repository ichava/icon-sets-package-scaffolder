<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Services\PhpImportOrderer;

it('orders top-level imports by length, then case-insensitively', function (): void {
    $php = "<?php\n\nnamespace Acme\\Pack;\n\nuse Zz\\Long\\ClassName;\nuse B\\Bb;\nuse a\\Aa;\nuse Mid\\Name as Alias;\n\nfinal class X {}\n";

    expect((new PhpImportOrderer)->order($php))->toBe(
        "<?php\n\nnamespace Acme\\Pack;\n\nuse a\\Aa;\nuse B\\Bb;\nuse Mid\\Name as Alias;\nuse Zz\\Long\\ClassName;\n\nfinal class X {}\n",
    );
});

it('leaves a trait use inside a class body alone', function (): void {
    $php = "<?php\n\nenum X: string\n{\n    use ZzzLongTrait;\n    use A;\n}\n";

    expect((new PhpImportOrderer)->order($php))->toBe($php);
});
