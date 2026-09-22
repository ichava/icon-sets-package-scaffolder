<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console;

use Illuminate\Console\OutputStyle;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\ScaffoldResult;

/**
 * Prints what to do with a freshly scaffolded package.
 *
 * Reads the `ScaffoldResult` rather than the replacement map the generator
 * happened to leave behind, so the advice describes what was actually written.
 * The old version reported a variant sub-folder hint from the requested type
 * even when nothing had created those folders.
 */
final readonly class NextStepsReporter
{
    public function report(OutputStyle $output, ScaffoldResult $result): void
    {
        $definition = $result->definition;

        $output->newLine();
        $output->writeln(sprintf(
            '<options=bold>%s</> - %d files, %d icon directories',
            $definition->packageName(),
            $result->fileCount(),
            count($result->directories),
        ));
        $output->writeln(sprintf('  <fg=gray>%s</>', $result->path));

        $output->newLine();
        $output->writeln('<options=bold>Next steps</>');

        foreach ($result->directories as $index => $directory) {
            $output->writeln(sprintf('  %d. Drop your SVG icons into <fg=cyan>%s/</>', $index + 1, $directory));
        }

        $step = count($result->directories);

        $output->writeln(sprintf(
            '  %d. Fine-tune <fg=cyan>resources/assets/svg/config.json</> (description, repository).',
            ++$step,
        ));
        $output->writeln(sprintf(
            '  %d. If this pack vendors someone else\'s icons, add <fg=cyan>metadata.homepage</>'
            . ' pointing at <fg=yellow>their</> project, and fill in the <fg=cyan>upstream</> block.',
            ++$step,
        ));
        $output->writeln(sprintf(
            '  %d. Run <fg=cyan>composer install</> inside the package to install its dev dependencies.',
            ++$step,
        ));
        $output->writeln(sprintf(
            '  %d. From a host app: <fg=cyan>composer require %s</>',
            ++$step,
            $definition->packageName(),
        ));
        $output->writeln('     The service provider is auto-discovered; no config/app.php edit is needed.');

        $output->newLine();
        $output->writeln(sprintf(
            '  Icons render as <fg=cyan><x-%s-icon name="..." /></> and the pack ships <fg=cyan>%s</>.',
            $definition->prefix,
            $definition->updateCommandName(),
        ));
        $output->writeln('  All package metadata is driven by config.json; nothing is hardcoded.');
        $output->newLine();
    }
}
