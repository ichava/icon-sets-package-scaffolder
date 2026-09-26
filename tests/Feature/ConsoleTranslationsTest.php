<?php

declare(strict_types=1);

/*
 * Everything under src/Console/ talks to a person, so it talks through
 * resources/lang rather than in English literals. Two guards:
 *
 * - every key the source references resolves, so a renamed key cannot quietly
 *   print its own name;
 * - no English literal reaches a prompt argument, a Prompts call, a writeln()
 *   or an exception a user reads -- counted, so the number can only go down.
 *
 * Both assert how many files they read first. A glob that stops matching
 * would otherwise pass over nothing and report a clean tree.
 */

const SCAFFOLDER_LANG_NAMESPACE = 'ichava/icon-sets-package-scaffolder';

/**
 * Measured 2026-09-26: 44 before the migration, 0 after. Lower it when a literal is
 * translated; never raise it.
 */
const SCAFFOLDER_CONSOLE_LITERAL_CEILING = 0;

/** @return array<string, string> relative path => source */
function scaffolderConsoleSources(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src/Console'));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[substr($file->getPathname(), strlen($root) + 1)] = (string) file_get_contents($file->getPathname());
        }
    }

    ksort($files);

    return $files;
}

/**
 * English literals a user would read, per file.
 *
 * @return list<string>
 */
function scaffolderConsoleLiterals(string $source): array
{
    $patterns = [
        // named prompt arguments: label: 'Package name'
        '/\b(label|placeholder|required|hint|yes|no):\s*(sprintf\(\s*)?[\'"][^\'"]*[A-Za-z]{2}/',
        // Prompts free functions and console writes
        '/\b(intro|outro|note|info|warning|error|alert)\(\s*[\'"][^\'"]*[A-Za-z]{2}/',
        '/->(writeln|line|info|error|warn|comment)\(\s*(sprintf\(\s*)?[\'"][^\'"]*[A-Za-z]{3}/',
        // user-facing exceptions
        '/new\s+RuntimeException\(\s*(sprintf\(\s*)?[\'"][^\'"]*[A-Za-z]{3}/',
        // select() option labels
        '/=>\s*[\'"][A-Z][a-z]+ [^\'"]*[\'"]/',
    ];

    // Symfony markup (`<fg=cyan>`, `<options=bold>`, `</>`) is not English;
    // strip it so a styled wrapper around a translated string does not count.
    $source = (string) preg_replace('#</?[a-z]*(=[a-z;,=]+)?>#i', '', $source);

    $found = [];

    foreach ($patterns as $pattern) {
        preg_match_all($pattern, $source, $matches);
        array_push($found, ...$matches[0]);
    }

    return $found;
}

it('reads the console sources it guards', function (): void {
    expect(count(scaffolderConsoleSources()))->toBeGreaterThanOrEqual(4);
});

it('registers its translations under the vendor-scoped namespace', function (): void {
    $hints = app('translator')->getLoader()->namespaces();

    $this->assertArrayHasKey(SCAFFOLDER_LANG_NAMESPACE, $hints);
    $this->assertSame('Next steps', __(SCAFFOLDER_LANG_NAMESPACE . '::console.next_steps.heading'));
});

it('resolves every translation key the console references', function (): void {
    $keys = [];

    foreach (scaffolderConsoleSources() as $source) {
        preg_match_all('/Messages::get\(\s*[\'"]([a-z0-9_.]+)[\'"]/', $source, $matches);
        array_push($keys, ...$matches[1]);
    }

    $this->assertNotEmpty($keys, 'The console references no translation keys at all.');

    foreach (array_unique($keys) as $key) {
        $full = SCAFFOLDER_LANG_NAMESPACE . '::console.' . $key;
        $this->assertNotSame($full, __($full), "Translation key does not resolve: {$full}");
    }
});

it('does not add English literals to console output', function (): void {
    $found = [];

    foreach (scaffolderConsoleSources() as $path => $source) {
        foreach (scaffolderConsoleLiterals($source) as $literal) {
            // `<` neutralised: the failure message is itself rendered as console
            // markup, and a captured `<fg=cyan>` fragment would crash the renderer.
            $found[] = "{$path}: " . str_replace('<', '‹', $literal);
        }
    }

    $this->assertLessThanOrEqual(
        SCAFFOLDER_CONSOLE_LITERAL_CEILING,
        count($found),
        "English literals in console output -- move them to resources/lang/en/console.php:\n  " . implode("\n  ", $found),
    );
});
