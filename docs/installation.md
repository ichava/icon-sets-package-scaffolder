# Installation

What this package needs, and how it attaches to a Laravel application.

## Requirements

| Requirement | Version |
|---|---|
| PHP | `^8.4.1 \|\| ^8.5` |
| Laravel | `^13.0` (`illuminate/support`) |
| `laranail/package-tools` | `^0.1.0` |
| `laranail/console` | `^0.1.0` |

Nothing here is on Packagist yet, so a plain `composer require` cannot resolve it.
Add the VCS repositories first:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/ichava/icon-sets-package-scaffolder" },
        { "type": "vcs", "url": "https://github.com/laranail/package-tools.git" },
        { "type": "vcs", "url": "https://github.com/laranail/console.git" },
        { "type": "vcs", "url": "https://github.com/laranail/enumerator.git" }
    ]
}
```

Composer's `repositories` key is read from the **root** package only. A dependency
declaring its own repositories has no effect on your resolution, which is why
every entry a package needs has to appear in the application that installs it.

## Install

```bash
composer require --dev ichava/icon-sets-package-scaffolder
```

A dev dependency: scaffolding happens once per pack, on a workstation, and
nothing a generated pack does at runtime goes through this package.

## Why it does not require `ichava/core`

Core takes this package as a `require-dev`. If this package required core in
turn, the two would form a cycle. It also does not need to: the only tie the
generator ever had to core was inheritance from core's `BaseCommand`, and the one
thing that class supplied -- `SupportsNamespacedNames` -- comes from
`laranail/console` directly.

## Verify

```bash
php artisan list ichava
```

`ichava::icon-package-scaffolder.make` should be listed. If it is not, the
provider did not register: check that package discovery is not disabled for this
package in your application's `composer.json` `extra.laravel.dont-discover`.

---

[← Docs index](../README.md#documentation)
