<?php

declare(strict_types=1);

/*
 * Published as config/icon-sets-package-scaffolder.php and read at
 * `ichava.icon-sets-package-scaffolder.*`.
 *
 * The filename matches the package short name on purpose. laranail/package-tools
 * appends the filename to the namespace whenever the two differ, so a file named
 * anything else would merge at `ichava.icon-sets-package-scaffolder.<filename>.*`
 * while every read site used the shorter key -- returning null for the whole
 * file, silently. That defect shipped in ichava/core and ichava/browser for
 * months (V39).
 */

return [

    /*
     * Where stub files are read from.
     *
     * null uses the bundled tree. Point this at a directory to scaffold from
     * your own templates -- the generator walks whatever it finds, so a custom
     * tree needs no registration, only the same token vocabulary.
     *
     * Publish the bundled tree to start from a copy:
     *   php artisan vendor:publish --tag=ichava::icon-sets-package-scaffolder-stubs
     */
    'stubs_path' => env('ICHAVA_SCAFFOLDER_STUBS_PATH'),

];
