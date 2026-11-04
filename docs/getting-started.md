# Getting started

Scaffolding your first icon pack, and what you get.

## One command

```bash
php artisan ichava::icon-sets-package-scaffolder.make
```

It asks for a name, a vendor, an author email, a Blade prefix, a set type and --
for a multi-variant pack -- the variants, then a destination.

## Type the bare slug

`tabler`, not `icon-sets-tabler`. The `icon-sets-` prefix is added for you, and
it is added once: typing the full slug gives the same names. The mapping below
was produced by running the generator with vendor `Ichava`, whose packs live under
`Simtabi\Laranail\Ichava\` like every real pack (any other vendor keeps
`<Vendor>\IconSets<Slug>`):

| You type | Composer name | Namespace | Config file |
|---|---|---|---|
| `tabler` or `Tabler` | `ichava/icon-sets-tabler` | `Simtabi\Laranail\Ichava\IconSetsTabler` | `config/icon-sets-tabler.php` |
| `flag` | `ichava/icon-sets-flag` | `Simtabi\Laranail\Ichava\IconSetsFlag` | `config/icon-sets-flag.php` |
| `icon-sets-flag` or `IconSetsFlag` | `ichava/icon-sets-flag` | `Simtabi\Laranail\Ichava\IconSetsFlag` | `config/icon-sets-flag.php` |
| `TablerIcons` | `ichava/icon-sets-tabler-icons` | `Simtabi\Laranail\Ichava\IconSetsTablerIcons` | `config/icon-sets-tabler-icons.php` |

The last row is not a typo. A trailing `Icons` in what you type is kept, so
`TablerIcons` is a different pack from `tabler`. Type the bare slug.

The destination directory is yours to name. The prompt suggests
`<vendor>-<slug>-icons`, which is not how the existing packs are laid out, so
pass `--path` and name it `icon-sets-<slug>` (`icon-sets-tabler`, `icon-sets-flag`,
`icon-sets-emoji`, `icon-sets-metronic`, `icon-sets-bundled`).

## Non-interactively

Supply everything and it never reaches a prompt:

```bash
php artisan ichava::icon-sets-package-scaffolder.make tabler \
  --vendor=Ichava \
  --email=ichava@simtabi.com \
  --path=../icon-sets-tabler \
  --prefix=ti \
  --type=multi \
  --variants=outline,filled \
  --no-interaction
```

`--no-interaction` is worth passing in a script even when every option is
supplied. Without it, a missing option reaches a prompt, and a prompt with no TTY
behind it blocks rather than fails.

## What lands

52 files, for a single-set pack and a multi-variant one alike (the variants add directories, not files):

```
icon-sets-tabler/
├── composer.json                    name, PSR-4 map, provider discovery
├── README.md  CHANGELOG.md  LICENSE.md
├── .github/workflows/               tests, code-quality, release, sync-upstream
├── .github/                         dependabot.yml, issue templates, PR template
├── .gitignore  .gitattributes  .editorconfig  phpunit.xml.dist
├── config/icon-sets-tabler.php      the config file, named for the pack
├── docs/                            variants, attribution, customization
├── resources/assets/svg/
│   ├── config.json                  every piece of pack metadata
│   └── files/                       your icons go here (one dir per variant)
├── src/Providers/IconsServiceProvider.php
├── src/Constants/IconsConstants.php
├── src/Enums/Variant.php
├── src/View/Components/IconComponent.php
└── tests/                           metadata, docs, attribution and the shared guards
```

There is no `SECURITY.md`: the real packs rely on the organisation's `.github`
cascade, so the README and `CONTRIBUTING.md` link the repository's own security
policy page. Disclosure goes to `security@simtabi.com`; `ichava@` is the author
and contact address only.

## Then

1. Drop SVGs into `resources/assets/svg/files/` (one sub-directory per variant).
2. Edit `resources/assets/svg/config.json` -- description, homepage, repository.
3. `composer install` inside the pack.
4. From a host application, `composer require ichava/icon-sets-tabler`.

The provider is auto-discovered. No `config/app.php` edit.

---

[← Docs index](../README.md#documentation)
