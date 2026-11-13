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

| You type | Composer name | Namespace |
|---|---|---|
| `tabler` or `Tabler` | `ichava/icon-sets-tabler` | `Simtabi\Laranail\Ichava\IconSetsTabler` |
| `flag` | `ichava/icon-sets-flag` | `Simtabi\Laranail\Ichava\IconSetsFlag` |
| `icon-sets-flag` or `IconSetsFlag` | `ichava/icon-sets-flag` | `Simtabi\Laranail\Ichava\IconSetsFlag` |
| `TablerIcons` | `ichava/icon-sets-tabler-icons` | `Simtabi\Laranail\Ichava\IconSetsTablerIcons` |

The last row is not a typo. A trailing `Icons` in what you type is kept, so
`TablerIcons` is a different pack from `tabler`. Type the bare slug.

The destination directory is yours to name. The prompt suggests
`<vendor>-<slug>-icons`, which is not how the existing packs are laid out, so
pass `--path` and name it `icon-sets-<slug>` (`icon-sets-tabler`, `icon-sets-flag`,
`icon-sets-twemoji`, `icon-sets-openmoji`).

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

57 files, for a single-set pack and a multi-variant one alike, plus a `.gitkeep` in each icon
directory (the variants add directories, not files). It is the file set of `icon-sets-tabler`, less
what only a vendored pack has (its SVGs, the upstream licence copy, and tests about its own history),
and `tests/Feature/ScaffoldedPackShapeTest.php` holds it there:

```
icon-sets-tabler/
├── composer.json                    name, PSR-4 map, provider discovery
├── README.md  CHANGELOG.md  LICENSE.md
├── .github/workflows/               tests, code-quality, release, sync-upstream
├── .github/                         dependabot.yml, issue templates, PR template
├── .gitignore  .gitattributes  .editorconfig  phpunit.xml.dist  phpstan.neon.dist
├── docs/                            variants, attribution, customization
├── resources/assets/svg/
│   ├── config.json                  every piece of pack metadata
│   └── files/                       your icons go here (one dir per variant)
├── src/Providers/IconsServiceProvider.php
├── src/Constants/IconsConstants.php
├── src/Enums/Variant.php            one case per --variants entry, or DEFAULT for a flat pack
├── src/View/Components/IconComponent.php
└── tests/                           Feature: IconsTest, SvgKindTest; Unit: the enum, resources,
                                     docs, attribution, release, workflows and the shared guards
```

`SECURITY.md` names `security@simtabi.com` as the only disclosure address and GitHub private
vulnerability reporting as the preferred channel, as the organisation policy does. `ichava@` is the
author and contact address only. The real packs ship no `SECURITY.md` and use the organisation
cascade; the README and `CONTRIBUTING.md` link the repository's security policy page.

## Then

There is no `config/` file. The estate packs dropped theirs: it held copies of the set name and prefix,
which nothing read, because core takes both from `config.json`. `sync-upstream.yml` calls
icon-sets-sync's reusable `sync-pack.yml`, pinned to one commit that the pack's Dependabot advances.

1. Drop SVGs into `resources/assets/svg/files/` (one sub-directory per variant). Then raise the
   icon-count floors the generated tests carry (`NoStrayTextNodesTest`, `IconsTest`) to the number
   vendored, as every estate pack does; until there are icons, the item cases in `SvgKindTest` fail.
2. Edit `resources/assets/svg/config.json` -- description, homepage, repository.
3. `composer install` inside the pack.
4. From a host application, `composer require ichava/icon-sets-tabler`.

The provider is auto-discovered. No `config/app.php` edit.

---

[← Docs index](../README.md#documentation)
