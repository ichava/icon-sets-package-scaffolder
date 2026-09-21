<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\Commands;

use Throwable;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\NextStepsReporter;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\DestinationResolver;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\PackageDefinitionPrompter;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions\InvalidPackageDefinition;

/**
 * Scaffolds a new Ichava icon package.
 *
 * Deliberately thin. It collects input, hands a `PackageDefinition` and a
 * `TargetPath` to the action, and renders the result. Everything that decides
 * anything lives behind that seam, which is what makes the generator reachable
 * from a test without driving prompts.
 *
 * The `::` in the name is written past Symfony's `validateName()` by
 * {@see SupportsNamespacedNames}. Dispatch still works because Symfony resolves
 * an exact name before it falls back to splitting on `:`.
 *
 * @api
 */
final class MakeIconPackageCommand extends Command
{
    use SupportsNamespacedNames;

    protected $signature = 'ichava::icon-sets-package-scaffolder.make
                           {name? : Package name, e.g. HeroIcons}
                           {--vendor= : Vendor name}
                           {--email= : Author email address}
                           {--path= : Destination directory}
                           {--prefix= : Blade component prefix, defaults to the kebab-case name}
                           {--type= : Icon set type: single or multi}
                           {--variants= : Comma-separated variants for a multi-set pack}
                           {--force : Overwrite a non-empty destination}';

    protected $description = 'Scaffold a new Ichava icon package';

    public function handle(
        PackageDefinitionPrompter $prompter,
        DestinationResolver $destinations,
        ScaffoldIconPackage $scaffold,
        NextStepsReporter $reporter,
    ): int {
        // Whether there is anyone to prompt. Laravel Prompts blocks on a TTY,
        // so a run under --no-interaction, in CI, or from a test would hang
        // rather than fail if this were assumed.
        $interactive = $this->input->isInteractive();

        if ($interactive) {
            intro('Create a new Ichava icon package');
        }

        $force = (bool) $this->option('force');

        try {
            $definition = $prompter->prompt([
                'name'     => $this->argument('name'),
                'vendor'   => $this->option('vendor'),
                'email'    => $this->option('email'),
                'prefix'   => $this->option('prefix'),
                'type'     => $this->option('type'),
                'variants' => $this->option('variants'),
            ], $interactive);

            $path = $destinations->resolve($definition, $this->option('path'), $force, $interactive);

            $result = $scaffold($definition, $path, $force);
        } catch (InvalidPackageDefinition $e) {
            // Separated from the generic catch because this one is the user's
            // input, not a fault: it gets the message and nothing else.
            $this->components->error($e->getMessage());

            return self::INVALID;
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $reporter->report($this->getOutput(), $result);

        if ($interactive) {
            outro('Icon package scaffolded');
        }

        return self::SUCCESS;
    }
}
