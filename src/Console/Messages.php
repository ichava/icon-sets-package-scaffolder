<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console;

/**
 * The console's strings, from `resources/lang/<locale>/console.php`.
 *
 * One place spells the `ichava/icon-sets-package-scaffolder::console.` prefix,
 * so a call site names only the key, and the guard in ConsoleTranslationsTest
 * can collect every key by reading `Messages::get('…')` calls.
 *
 * @internal
 */
final class Messages
{
    public const string NAMESPACE = 'ichava/icon-sets-package-scaffolder';

    /**
     * @param array<string, scalar> $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $message = __(self::NAMESPACE . '::console.' . $key, $replace);

        return is_string($message) ? $message : $key;
    }
}
