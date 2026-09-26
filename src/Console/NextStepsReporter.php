<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;
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
        $output->writeln('<options=bold>' . Messages::get('next_steps.summary', [
            'package'     => $definition->packageName(),
            'files'       => $result->fileCount(),
            'directories' => count($result->directories),
        ]) . '</>');
        $output->writeln(sprintf('  <fg=gray>%s</>', $result->path));

        $output->newLine();
        $output->writeln('<options=bold>' . Messages::get('next_steps.heading') . '</>');

        $step = 0;

        foreach ($result->directories as $directory) {
            $this->step($output, ++$step, Messages::get('next_steps.drop_icons', ['directory' => $this->code($directory . '/')]));
        }

        $this->step($output, ++$step, Messages::get('next_steps.config', ['file' => $this->code('resources/assets/svg/config.json')]));
        $this->step($output, ++$step, Messages::get('next_steps.upstream', [
            'homepage' => $this->code('metadata.homepage'),
            'upstream' => $this->code('upstream'),
        ]));
        $this->step($output, ++$step, Messages::get('next_steps.install', ['command' => $this->code('composer install')]));
        $this->step($output, ++$step, Messages::get('next_steps.require', ['command' => $this->code('composer require ' . $definition->packageName())]));
        $output->writeln('     ' . Messages::get('next_steps.autodiscovered'));

        $output->newLine();
        $output->writeln('  ' . Messages::get('next_steps.usage', [
            'component' => $this->code(sprintf('<x-%s-icon name="..." />', $definition->prefix)),
            'command'   => $this->code($definition->updateCommandName()),
        ]));
        $output->writeln('  ' . Messages::get('next_steps.metadata'));
        $output->newLine();
    }

    private function step(OutputStyle $output, int $number, string $text): void
    {
        $output->writeln(sprintf('  %d. %s', $number, $text));
    }

    /**
     * A path or command, styled. The value is escaped so a `<` in it (the Blade
     * tag in the usage line) is printed rather than parsed as markup.
     */
    private function code(string $value): string
    {
        return '<fg=cyan>' . OutputFormatter::escape($value) . '</>';
    }
}
