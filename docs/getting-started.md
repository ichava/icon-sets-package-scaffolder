# Getting started

Scaffolding your first icon pack, and what you get.

## One command

```bash
php artisan ichava::icon-sets-package-scaffolder.make
```

It asks for a name, a vendor, an author email, a Blade prefix, a set type and --
for a multi-variant pack -- the variants, then a destination.

## Type the bare noun

`Hero`, not `HeroIcons`. The suffix is appended for you:

| You type | Composer name | Namespace | Blade tag |
|---|---|---|---|
| `Hero` | `acme/hero-icons` | `Acme\HeroIcons` | `<x-hero-icon name="..." />` |
| `HeroIcons` | `acme/hero-icons-icons` | `Acme\HeroIconsIcons` | `<x-hero-icons-icon ... />` |

The second row is not a typo. It is what the generator does, inherited from core
and pinned by a test rather than changed, because changing it would rename every
class in a pack scaffolded before the change.

## Non-interactively

Supply everything and it never reaches a prompt:

```bash
php artisan ichava::icon-sets-package-scaffolder.make Hero \
  --vendor=Acme \
  --email=dev@example.com \
  --path=../acme-hero-icons \
  --prefix=hero \
  --type=multi \
  --variants=outline,solid \
  --no-interaction
```

`--no-interaction` is worth passing in a script even when every option is
supplied. Without it, a missing option reaches a prompt, and a prompt with no TTY
behind it blocks rather than fails.

## What lands

25 files for a single-set pack, 26 for a multi-variant one:

```
acme-hero-icons/
├── composer.json                    name, PSR-4 map, provider discovery
├── README.md  CHANGELOG.md  LICENSE.md
├── .github/workflows/               tests, code-quality, release, sync-upstream
├── .gitignore  .gitattributes  phpunit.xml.dist
├── config/hero-icons.php            the config file, named for the pack
├── docs/                            variants, attribution, customization
├── resources/assets/svg/
│   ├── config.json                  every piece of pack metadata
│   └── files/                       your icons go here (one dir per variant)
├── src/Providers/IconsServiceProvider.php
├── src/Constants/IconsConstants.php
├── src/Enums/Variant.php
├── src/View/Components/IconComponent.php
├── src/Commands/UpdateIconsCommand.php
└── tests/
```

## Then

1. Drop SVGs into `resources/assets/svg/files/` (one sub-directory per variant).
2. Edit `resources/assets/svg/config.json` -- description, homepage, repository.
3. `composer install` inside the pack.
4. From a host application, `composer require acme/hero-icons`.

The provider is auto-discovered. No `config/app.php` edit.

---

[← Docs index](../README.md#documentation)
