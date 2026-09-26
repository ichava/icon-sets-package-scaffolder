<?php

declare(strict_types=1);

/*
 * Everything the scaffolder says to a person at a terminal.
 *
 * Markup stays out of these strings: styled fragments (paths, commands) arrive
 * as `:placeholders` already wrapped by the caller, so a translator never edits
 * a Symfony `<fg=...>` tag and a missing one cannot break the output.
 *
 * The command's `$description` stays untranslated, as in ichava/core: Artisan
 * reads it before a locale is necessarily set.
 */
return [

    'make' => [
        'intro' => 'Create a new Ichava icon package',
        'outro' => 'Icon package scaffolded',
    ],

    'prompt' => [
        'name' => [
            'label'       => 'Package name',
            'placeholder' => 'e.g. Hero, FontAwesome, Feather',
            'required'    => 'Package name is required',
            'hint'        => 'Type the bare noun -- `Hero`, not `HeroIcons`; the Icons suffix is added for you.',
        ],
        'vendor' => [
            'label'       => 'Vendor name',
            'placeholder' => 'e.g. YourCompany, MyOrg',
            'required'    => 'Vendor name is required',
            'hint'        => 'Used in the namespace and the composer package name',
        ],
        'email' => [
            'label'       => 'Author email address',
            'placeholder' => 'you@example.com',
            'required'    => 'Email address is required',
            'hint'        => 'Used in the composer.json author field',
        ],
        'prefix' => [
            'label' => 'Blade component prefix',
            'hint'  => 'Used for Blade components: <x-{prefix}-icon name="..." />',
        ],
        'type' => [
            'label'  => 'Icon set type',
            'single' => 'Single set (one flat collection of icons)',
            'multi'  => 'Multi variant (outline, solid, duotone, ...)',
        ],
        'axis' => [
            'label'    => 'How does this pack subdivide its icons?',
            'variant'  => 'Variants (outline, solid, duotone, ...)',
            'category' => 'Categories (brands, weather, ui, ...)',
        ],
        'variants' => [
            'label'           => 'Variants',
            'placeholder'     => 'outline, solid, duotone',
            'required'        => 'A multi-variant package needs at least one variant',
            'hint'            => 'Comma separated. The first is the default, and its icon files carry no suffix.',
            'confirm_default' => '":variant" will be the default variant. Continue?',
            'declined'        => 'Scaffolding cancelled: the default variant was not confirmed.',
        ],
    ],

    'destination' => [
        'label'             => 'Destination path for the icon package',
        'required'          => 'A destination path is required',
        'hint'              => 'Absolute, or relative to the project root. The ecosystem convention is `:suggestion`.',
        'required_headless' => 'A destination path is required. Pass --path when running non-interactively.',
        'create_parent'     => 'Parent directory ":parent" does not exist. Create it?',
        'no_parent'         => 'Cannot create a package without a valid parent directory.',
        'not_writable'      => 'Parent directory is not writable: :parent',
    ],

    'next_steps' => [
        'summary'        => ':package - :files files, :directories icon directories',
        'heading'        => 'Next steps',
        'drop_icons'     => 'Drop your SVG icons into :directory',
        'config'         => 'Fine-tune :file (description, repository).',
        'upstream'       => 'If this pack vendors someone else\'s icons, add :homepage pointing at their project, and fill in the :upstream block.',
        'install'        => 'Run :command inside the package to install its dev dependencies.',
        'require'        => 'From a host app: :command',
        'autodiscovered' => 'The service provider is auto-discovered; no config/app.php edit is needed.',
        'usage'          => 'Icons render as :component and the pack ships :command.',
        'metadata'       => 'All package metadata is driven by config.json; nothing is hardcoded.',
    ],

];
