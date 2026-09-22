[← Docs index](../README.md#documentation)

# Creating Custom Icon Packages

*How-to guide.*

There are two paths to a publishable icon package:

1. **Recommended, scaffold with `ichava::icon-sets-package-scaffolder.make`** (next section). Generates
   a complete, production-ready package from the stub tree in seconds. Use this
   for almost every new package.
2. **Manual**, if you need a layout the stubs don't cover, follow the section
   "Authoring a package by hand" later on.

### Recommended path. `ichava::icon-sets-package-scaffolder.make`

The scaffolder is a separate, dev-only package. It was part of `ichava/core` up to `0.2.7` and
moved out in `0.3.0`, so install it first:

```bash
composer require --dev ichava/icon-sets-package-scaffolder
php artisan ichava::icon-sets-package-scaffolder.make
```

The interactive command walks every file under `stubs/icon-package/` and emits
the complete tree into the destination directory: `composer.json`, the service
provider, constants class, variant enum, Blade component, update command,
`config.json`, README, CHANGELOG, LICENSE, `.gitignore`, `.gitattributes`,
`phpunit.xml.dist`, and a Pest test base. After the command finishes the
package is `composer install`-ready; just drop SVG files into
`resources/assets/svg/files/` (single-set) or
`resources/assets/svg/files/<variant>/` (multi-set).

> **Directory naming convention.** New packages should live in a directory
> named `ichava-<kebabName>-icons`, matching the existing siblings
> (`ichava-tabler-icons`, `ichava-bundled-icons`, `ichava-metronic-icons`).
> The interactive prompt defaults to `base_path("ichava-{kebabName}-icons")`.

The full flag reference lives with the command, in
[`ichava/icon-sets-package-scaffolder`](https://github.com/ichava/icon-sets-package-scaffolder/blob/main/docs/tools/make-command.md).

#### How the scaffolder works

The command is built around three primitives, once you know them, customising
or extending the scaffold is straightforward.

**1. Auto-discovery walker.** `MakeIconPackageCommand::generateFiles()` uses
Symfony Finder to recursively walk `stubs/icon-package/`. **Every file** in the
tree is treated as a stub, there is no manual file map to keep in sync. To
add a new file to the scaffold, drop it into the stub tree; that's the only
step. Removing a file is the inverse.

**2. Optional `.stub` suffix.** A trailing `.stub` is stripped from the
destination filename when present. The suffix exists purely so contributors
can keep stubs out of language-tooling indices (PHP linters, IDE autoloads).
Files without the suffix, for example a future `.editorconfig` or a binary
asset, are copied through verbatim with their original name.

**3. Mustache placeholders everywhere.** The same `{{token}}` syntax used in
file contents also works inside paths and filenames. The directory
`src/Constants/{{studlyName}}IconsConstants.php.stub` becomes
`src/Constants/HeroIconsConstants.php` when scaffolding `Hero`. No special
filename grammar to learn, one convention everywhere.

#### Replacement tokens

Tokens are grouped by intent so the right form lands in the right grammar.
Mixing them up (using `{{vendorLower}}` where a kebab form is needed, or
`{{vendor}}` where a PHP namespace is needed) is the most common
scaffolding bug, that's why both correct and lossy variants are exposed.

**Vendor variants** (`Your Company` input ⇒ each form below):

| Token | Result | Use for |
|---|---|---|
| `{{vendor}}` | `Your Company` | Author/copyright display only, keeps user input verbatim. |
| `{{vendorStudly}}` | `YourCompany` | PHP namespaces, `use` statements, `@package` tags. |
| `{{vendorKebab}}` | `your-company` | Composer package name, GitHub URLs, route segments. |
| `{{vendorLower}}` | `your company` (lowercased) | Legacy, prefer `{{vendorKebab}}` for paths. |
| `{{vendorSnake}}` | `your_company` | Env-var prefixes, snake_case identifiers. |

**Package-name variants** (`Hero` input ⇒):

| Token | Result | Use for |
|---|---|---|
| `{{studlyName}}` | `Hero` | Class names; the stubs append the literal `Icons` suffix where needed. |
| `{{camelName}}` | `hero` | Local variables, camelCase identifiers. |
| `{{kebabName}}` | `hero` | Composer fragments, Blade component prefixes, route slugs. |
| `{{snakeName}}` | `hero` | Database tables, snake-cased configuration keys. |
| `{{snakeNameUpper}}` | `HERO` | Constant identifiers. |
| `{{humanName}}` | `Hero` (title-cased from kebab) | Display text in README, descriptions, UI strings. |

**Composed identifiers** (pre-glued to keep stubs DRY):

| Token | Result | Use for |
|---|---|---|
| `{{namespace}}` | `YourCompany\HeroIcons` | Single-token PHP namespace prefix. |
| `{{namespaceEscaped}}` | `YourCompany\\HeroIcons` | JSON-safe namespace (composer.json `psr-4` keys). |
| `{{packageName}}` | `your-company/hero-icons` | Full composer package name. |
| `{{bladeNamespace}}` | `hero-icons` | Blade component namespace (`<x-hero-icons-icon>`). |

> **Class short names are constants**. `IconsServiceProvider`,
> `IconsConstants`, `IconComponent`, `Variant`. Every scaffolded
> package uses the same names; disambiguation happens via the
> namespace. This eliminates a class of bugs (e.g, double-`Icons`
> suffix when a user typed `HeroIcons` instead of `Hero`) and lets
> you grep `class IconsConstants` across the whole ecosystem.

**Author / metadata** (pass-through):

| Token | Source |
|---|---|
| `{{prefix}}` | `--prefix` flag (defaults to `{{kebabName}}`). |
| `{{email}}` | `--email` flag. |
| `{{year}}` | `date('Y')` at scaffold time. |
| `{{date}}` | `date('Y-m-d')` at scaffold time. |
| `{{iconSetType}}` | `single` or `multi` (`--type` flag). |
| `{{variantsJson}}` | Pretty-printed JSON of the `--variants` list (used by `config.json`). |

> **Version constraints**. PHP, Laravel, Testbench, Pest etc.. are *not*
> tokenised. They live as literals inside `composer.json.stub` because the
> supported matrix changes infrequently and editing the stub directly is
> clearer than indirection through a token.

#### Customising or extending the stubs

The stubs ship inside the core package at
`vendor/ichava/core/stubs/icon-package/`. To customise them for your
organisation:

```bash
# 1. Copy the stubs into your application
cp -R vendor/ichava/core/stubs/icon-package resources/stubs/

# 2. Edit them, add files, remove files, change boilerplate
$EDITOR resources/stubs/icon-package/README.md.stub

# 3. Point the command at your copy by overriding getStubsPath() in a
#    subclass, or edit the stubs in place if you only need a
#    one-off run.
```

To add a new file to the scaffold:

1. Drop it into your stubs tree (with or without a `.stub` suffix).
2. Use `{{token}}` placeholders anywhere, including the filename and any
   intermediate directory segments.
3. Run `php artisan ichava::icon-sets-package-scaffolder.make`, the new file appears in the output
   automatically. No code changes required.

To remove a file from the scaffold, just delete it from the stubs tree.

---

### Authoring a package by hand

If `ichava::icon-sets-package-scaffolder.make` doesn't fit (for example you're porting an existing
library with a non-standard layout), the package can be assembled manually.
The minimum requirements are unchanged from the auto-generated version.

### 1. Create Package Structure

```
your-icons/
├── composer.json
├── config/
│   └── config.json          # Icon set metadata
├── resources/
│   └── assets/
│       └── svg/
│           └── files/       # SVG files go here
│               ├── category1/
│               │   ├── icon1.svg
│               │   └── icon2.svg
│               └── category2/
│                   └── icon3.svg
└── src/
    └── Providers/
        └── YourIconsServiceProvider.php
```

### 2. Create config.json

```json
{
    "name": "your/icons",
    "title": "Your Icons",
    "description": "Your custom icon set",
    "version": "1.0.0",
    "license": "MIT",
    "author": "Your Name",
    "homepage": "https://example.com",
    "categories": ["category1", "category2"],
    "variants": ["default"]
}
```

### 3. Create Service Provider

Extend `Simtabi\Laranail\Ichava\Support\ServiceProvider`. **not** `PackageServiceProvider` directly.
The base class provides `loadBladeComponent()`, `registerIconDirectory()`, `registerBulkIconSets()`,
and `svgAssetsPath()` out of the box.

```php
<?php

namespace Your\Icons\Providers;

use Simtabi\Laranail\Packager\Package\Package;
use Simtabi\Laranail\Ichava\Support\ServiceProvider;
use Your\Icons\View\Components\YourIconComponent;

class YourIconsServiceProvider extends ServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->setName('your/icons')
            ->setPathFrom(source: $this, levelsUp: 2)
            ->hasConfigFile('your-icons');
    }

    public function bootingPackage(): void
    {
        // Registers: <x-your-icons-icon name="..." />
        $this->loadBladeComponent(YourIconComponent::class, 'your-icons');

        // Register the SVG directory (must contain config.json)
        $this->registerIconDirectory($this->svgAssetsPath());
    }
}
```

**For large bundles** (72 sets), use `registerBulkIconSets()` instead:

```php
public function bootingPackage(): void
{
    $this->loadBladeComponent(YourBundleComponent::class, 'your-bundle');

    $this->registerBulkIconSets(
        basePath: $this->svgAssetsPath(),
        iconSets: $this->iconSets,   // ['fontawesome' => [...], 'bootstrap' => [...]]
        vendor:   'Your Bundle',
    )->trackStatistics()->enableLogging();
}
```

### 4. Register in composer.json

```json
{
    "extra": {
        "laravel": {
            "providers": [
                "Your\\Icons\\Providers\\YourIconsServiceProvider"
            ]
        }
    }
}
```

### 5. Provider Loading Order (Critical)

The Ichava ecosystem uses a parent–child provider architecture with a strict boot sequence:

1. **IchavaServiceProvider registers**, log channels created, all core services bound to the container, Horizon supervisor configured.
2. **Child icon packages register**, package config merged, Artisan commands registered. Core services are **not yet available** here.
3. **IchavaServiceProvider boots**, routes, views, middleware, and Blade directives loaded.
4. **Child icon packages boot**. Blade components registered, icons registered with `IconRegistry`. Logging is now safe.

> **Practical rule:** put everything in `bootingPackage()`. Never call `IconRegistry` or `IchavaLogger` from `registeringPackage()`.

### 6. Do's and Don'ts

**Lifecycle method reference:**

| Method | Required | Purpose |
|---|---|---|
| `configurePackage()` | ✅ Yes | Set package name, path, config file, commands |
| `bootingPackage()` | ✅ Yes | Register Blade component + icon directory |
| `registeringPackage()` | ❌ Usually not | Infrastructure is handled by the parent |
| `packageRegistered()` | ❌ Usually not | Only for conditional extra service bindings |

**What child packages must NOT do:**

```php
// ❌ WRONG, do not register log channels (parent owns them)
public function registeringPackage(): void
{
    $this->app['config']->set('logging.channels.my-icons', [...]);
}

// ❌ WRONG, do not log in registeringPackage(), channels don’t exist yet
public function registeringPackage(): void
{
    Log::channel('ichava')->info('Hello'); // throws: Log [ichava] not defined
}

// ❌ WRONG, do not rebind IchavaLogger; use the parent’s singleton
$this->app->singleton(IchavaLogger::class, fn () => new IchavaLogger(...));
```

### 7. Logging in Child Packages

Always resolve `IchavaLogger` from the container in `bootingPackage()` or later:

```php
public function bootingPackage(): void
{
    $logger = $this->app->make(IchavaLogger::class);

    $logger->info('Package booted');                         // → ichava channel
    $logger->seeding('Processing 500 icons…');              // → ichava-icons channel
    $logger->debug('Icon path', ['path' => $iconPath]);     // → ichava channel
}
```

> Messages routed through `IchavaLogger` to the dedicated `ichava*` channels do **not** need a `[Ichava]` prefix, the channel name already scopes them. Use the `[Ichava]` prefix only when writing directly to the default Laravel `stack`/`daily` channel from outside the logger service.

### 8. Using IchavaRegistrar for Large Bundles

For bundles with many icon set sub-directories call `IchavaRegistrar` directly (or use the inherited `registerBulkIconSets()` helper):

```php
public function bootingPackage(): void
{
    $this->loadBladeComponent(IconsBundleComponent::class, 'icons-bundle');

    IchavaRegistrar::register('icons-bundle')
        ->basePath($this->svgAssetsPath())
        ->packagePrefix('icons-bundle')
        ->bladeNamespace('icons-bundle')
        ->providerClass(self::class)
        ->vendor('Icons Bundle')
        ->configure($this->iconSets)   // triggers registration immediately
        ->trackStatistics()
        ->enableLogging();
}
```

Or via the inherited helper (shorthand):

```php
$this->registerBulkIconSets(
    basePath: $this->svgAssetsPath(),
    iconSets: $this->iconSets,
    vendor:   'Icons Bundle',
)->trackStatistics()->enableLogging();
```

### 9. Debugging Common Problems

| Problem | Likely cause | Fix |
|---|---|---|
| Icons not rendering | `registerIconDirectory()` not called | Ensure it’s in `bootingPackage()` |
| Blade component not found | `loadBladeComponent()` not called | Ensure it’s in `bootingPackage()`; run `php artisan view:clear` |
| `Log [ichava] not defined` | Logging in `registeringPackage()` too early | Move all logging to `bootingPackage()` |
| Icons registered warning | Duplicate `fromDirectory()` call | Check no two providers register the same path |
| Icons seeded but not rendering | Wrong icon path format | Verify `vendor/package::category/icon-name` format |

---

[← Docs index](../README.md#documentation)
