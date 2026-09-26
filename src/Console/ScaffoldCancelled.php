<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Console;

use RuntimeException;

/**
 * The person at the terminal declined to continue.
 *
 * Not an error: the command reports it and exits successfully, the way every
 * ichava command treats a declined confirmation. It exists so a prompt helper
 * can stop the run without calling `exit()` -- which ends the whole PHP
 * process, host application and test runner included.
 */
final class ScaffoldCancelled extends RuntimeException {}
