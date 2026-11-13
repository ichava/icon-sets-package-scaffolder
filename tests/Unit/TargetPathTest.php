<?php

declare(strict_types=1);

use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;

it('keeps an absolute path as given', function (): void {
    expect(TargetPath::resolve('/srv/packages/acme-hero-icons')->value)
        ->toBe('/srv/packages/acme-hero-icons');
});

it('resolves a relative path against the base path', function (): void {
    expect(TargetPath::resolve('acme-hero-icons', '/srv/app')->value)
        ->toBe('/srv/app' . DIRECTORY_SEPARATOR . 'acme-hero-icons');
});

it('trims a trailing separator without joining two', function (): void {
    expect(TargetPath::resolve('/srv/packages/hero/')->value)->toBe('/srv/packages/hero')
        ->and(TargetPath::resolve('hero/', '/srv/app/')->value)
        ->toBe('/srv/app' . DIRECTORY_SEPARATOR . 'hero');
});

it('refuses the filesystem root', function (string $root): void {
    // rtrim eats the root's own separator, so "/" arrives here as "". Without
    // the guard it would look relative and be re-based, scaffolding a package
    // into <base>/ -- which is to say, over the application.
    TargetPath::resolve($root, '/srv/app');
})->throws(InvalidArgumentException::class)->with(['/', '\\', 'C:\\']);

it('refuses an empty path', function (): void {
    TargetPath::resolve('   ', '/srv/app');
})->throws(InvalidArgumentException::class);

it('refuses a relative path with nothing to resolve it against', function (): void {
    TargetPath::resolve('hero-icons');
})->throws(InvalidArgumentException::class);

it('recognises every absolute form without touching the filesystem', function (string $path): void {
    // Every one of these is a directory that does not exist. A resolver that
    // asked the filesystem would call each relative and re-base it.
    expect(TargetPath::resolve($path, '/srv/app')->value)->toBe(rtrim($path, '/\\'));
})->with([
    'unix'                        => ['/srv/nope/hero'],
    'windows drive'               => ['C:\\packages\\hero'],
    'windows drive forward slash' => ['C:/packages/hero'],
    'unc share'                   => ['\\\\server\\share\\hero'],
]);

it('joins segments with the host separator', function (): void {
    $path = TargetPath::absolute('/srv/hero');

    expect($path->join('src', 'Providers'))
        ->toBe('/srv/hero' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Providers')
        ->and($path->parent())->toBe('/srv')
        ->and($path->basename())->toBe('hero')
        ->and((string) $path)->toBe('/srv/hero');
});
