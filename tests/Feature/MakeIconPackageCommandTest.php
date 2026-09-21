<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\Ichava\IconPackageScaffolder\Console\Commands\MakeIconPackageCommand;

it('registers under a name that carries the vendor and the slug', function (): void {
    // V59. Asserted against the live registry rather than the $signature
    // property: grepping the property proves how registration was written, not
    // what Artisan ended up holding -- and the `::` only survives because a
    // trait writes the name past Symfony's validateName().
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKey('ichava::icon-package-scaffolder.make');

    $names = array_map(
        static fn ($command): string => $command->getName(),
        array_values($commands),
    );

    foreach ($names as $name) {
        if (str_contains($name, 'icon-package')) {
            expect($name)->toMatch('/^ichava::[a-z0-9-]+\./');
        }
    }
});

it('registers no bare alias back into the generic namespace', function (): void {
    // A retained `make:icon-package` would be exactly the collision the
    // namespaced name exists to prevent, and it would sit in Laravel's own
    // `make:` namespace. The keys of all() include aliases, which is what makes
    // this assertion able to see one.
    expect(array_keys(app(Kernel::class)->all()))
        ->not->toContain('make:icon-package')
        ->not->toContain('icon-package:make')
        ->not->toContain('ichava:make');
});

it('scaffolds a package end to end from options alone', function (): void {
    $root = scratchDirectory('command');

    // --no-interaction on purpose: the claim under test is that a fully
    // specified run never reaches a prompt. Without it the assertion would
    // pass on a machine with a TTY and hang in CI.
    $exit = $this->artisan(MakeIconPackageCommand::class, [
        'name'             => 'Hero',
        '--vendor'         => 'Acme',
        '--email'          => 'dev@example.com',
        '--path'           => $root . '/acme-hero-icons',
        '--type'           => 'multi',
        '--variants'       => 'outline, solid',
        '--no-interaction' => true,
    ])->run();

    expect($exit)->toBe(0)
        ->and(is_file($root . '/acme-hero-icons/composer.json'))->toBeTrue()
        ->and(is_dir($root . '/acme-hero-icons/resources/assets/svg/files/outline'))->toBeTrue();

    removeDirectory($root);
});

it('reports bad input as invalid input, not as a crash', function (): void {
    $root = scratchDirectory('command-invalid');

    // Validation used to live in `validate:` closures on the prompts, so an
    // option-driven run enforced nothing and a malformed email reached
    // composer.json intact.
    $exit = $this->artisan(MakeIconPackageCommand::class, [
        'name'             => 'Hero',
        '--vendor'         => 'Acme',
        '--email'          => 'not-an-email',
        '--path'           => $root . '/acme-hero-icons',
        '--no-interaction' => true,
    ])->run();

    expect($exit)->toBe(2)
        ->and(is_dir($root . '/acme-hero-icons'))->toBeFalse();

    removeDirectory($root);
});

it('refuses an occupied destination and says so', function (): void {
    $root = scratchDirectory('command-occupied');
    mkdir($root . '/pack');
    file_put_contents($root . '/pack/composer.json', '{}');

    $exit = $this->artisan(MakeIconPackageCommand::class, [
        'name'             => 'Hero',
        '--vendor'         => 'Acme',
        '--email'          => 'dev@example.com',
        '--path'           => $root . '/pack',
        '--no-interaction' => true,
    ])->run();

    expect($exit)->toBe(1)
        ->and(file_get_contents($root . '/pack/composer.json'))->toBe('{}');

    removeDirectory($root);
});
