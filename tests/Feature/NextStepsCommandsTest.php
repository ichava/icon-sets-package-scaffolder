<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console\NextStepsReporter;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;

/*
 * The next steps used to tell every new pack's author to run
 * `ichava::<pack>.update`, which no stub registers: upstream syncing is
 * ichava/icon-sets-sync's job. Asserted against the RENDERED pack, so a
 * command the advice names must actually exist in what was written.
 */
it('names only commands and files the scaffolded pack has', function (): void {
    $root = scratchDirectory('next-steps');

    try {
        $definition = PackageDefinition::create(name: 'Beacon', vendor: 'Acme', email: 'dev@example.com');
        $result = app(ScaffoldIconPackage::class)($definition, TargetPath::absolute($root), force: true);

        $buffer = new BufferedOutput;
        (new NextStepsReporter)->report(new OutputStyle(new ArrayInput([]), $buffer), $result);
        $text = $buffer->fetch();

        $this->assertStringContainsString('Next steps', $text);

        // Every `ichava::` command named must be registered somewhere in the pack's source.
        preg_match_all('/ichava::[\w.:-]+/', $text, $commands);
        $source = '';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS)) as $file) {
            $source .= (string) file_get_contents($file->getPathname());
        }
        $this->assertNotSame('', $source, 'The scaffolded pack has no source to check against.');

        foreach ($commands[0] as $command) {
            $this->assertStringContainsString($command, $source, "Next steps name `{$command}`, which the pack does not register.");
        }

        // No `.update` command: upstream syncing is icon-sets-sync's, run by the pack's workflow.
        $this->assertDoesNotMatchRegularExpression('/ichava::[a-z0-9-]+\.update\b/', $text);

        // The workflow the advice points at is one the pack actually ships.
        $this->assertStringContainsString(NextStepsReporter::SYNC_WORKFLOW, $text);
        $this->assertFileExists($root . '/' . NextStepsReporter::SYNC_WORKFLOW);
    } finally {
        removeDirectory($root);
    }
});
