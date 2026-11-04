# Building your own icon pack

*Tutorial.*

You can ship your own icon pack as a Composer package and have it work alongside `ichava/icon-sets-tabler` and the others. The fastest path is the scaffolder in [`ichava/icon-sets-package-scaffolder`](https://github.com/ichava/icon-sets-package-scaffolder), a dev-only package. It was part of `ichava/core` up to `0.2.7` and moved out in `0.3.0`.

## 1. Run the scaffolder

In a host Laravel app that has `ichava/core` installed:

```bash
composer require --dev ichava/icon-sets-package-scaffolder
php artisan ichava::icon-sets-package-scaffolder.make
```

Or non-interactively:

```bash
php artisan ichava::icon-sets-package-scaffolder.make hero \
    --vendor=Acme \
    --email=team@acme.test \
    --prefix=hi \
    --type=multi \
    --variants=outline,solid \
    --path=icon-sets-hero
```

You get a complete package: `composer.json`, service provider, constants class, variant enum, Blade component, update command, `config.json`, README, CHANGELOG, LICENSE, `.gitignore`, `.gitattributes`, `phpunit.xml.dist`, four GitHub Actions workflows, and a `docs/` directory pre-populated with vendor-specific pages.

The name you type is the bare slug. `hero` gives `acme/icon-sets-hero` and `Acme\IconSetsHero`; the `icon-sets-` prefix is added for you, once, so typing `icon-sets-hero` gives the same names. See [the full mapping](../getting-started.md#type-the-bare-slug).

See the [command reference](https://github.com/ichava/icon-sets-package-scaffolder/blob/main/docs/tools/make-command.md) for every flag, and the [token reference](https://github.com/ichava/icon-sets-package-scaffolder/blob/main/docs/tools/tokens.md) for every stub placeholder.

## 2. Drop in your SVGs

Single-style packs:

```
resources/assets/svg/files/
├── home.svg
├── user.svg
└── settings.svg
```

Multi-variant packs:

```
resources/assets/svg/files/
├── outline/
│   └── home.svg
└── solid/
    └── home.svg
```

## 3. Edit `config.json`

`resources/assets/svg/config.json` declares your pack's metadata: name, description, variants, default class, prefix. The `IconsConstants` class reads this at runtime.

## 4. Write your pack-specific docs

The scaffolder seeded `docs/` for you. Fill in:

- `docs/variants.md`, what your variants are and when to use each
- `docs/customization.md`, knobs unique to your pack (stroke width, currentColor, sizing)
- `docs/attribution.md`, upstream credits + licence

Cross-link to the main documentation for shared concerns:

```markdown
For the icon path grammar, see
https://github.com/ichava/core/blob/main/docs/tools/icon-path-format.md
```

## 5. Release and publish

The pack starts on the family's 0.1 line: its `CHANGELOG.md` opens at `[0.1.0]` and
`.release-please-manifest.json` already reads `0.1.0`, as in every estate pack. Tag `v0.1.0` by hand
on the commit that changelog describes; the tag starts `release.yml`, which publishes the changelog
section. From then on run *Release Please* from the Actions tab: it opens a release pull request for
the next version, and merging it tags that version. The workflow needs the organisation's Refresh Bot
credentials (`REFRESH_APP_CLIENT_ID`, `REFRESH_APP_PRIVATE_KEY`), copied to the repository if it is
private.

Submit to [Packagist](https://packagist.org) so users can `composer require <vendor>/<pkg>`.

## Conventions you inherit

Every Ichava pack follows the same shape:

- Class short names are constants (`IconsServiceProvider`, `IconsConstants`, `Variant` or `Category`, `IconComponent`). Disambiguation is by namespace.
- Composer name is `<vendor>/<short>-icons`. GitHub repo is `<vendor>/<short>-icons` (no extra prefix).
- Pack-specific docs live in `docs/`.
- Releases are tagged `v<x.y.z>` by release-please, on the family's shared 0.x minor.

## See also

- [`ichava::icon-sets-package-scaffolder.make` flag reference](https://github.com/ichava/icon-sets-package-scaffolder/blob/main/docs/tools/make-command.md)
- [Icon path format](https://github.com/ichava/core/blob/main/docs/tools/icon-path-format.md)
- [Database seeding](https://github.com/ichava/core/blob/main/docs/recipes/seed-the-database.md)
- [Custom icon sets (in-app, no package)](https://github.com/ichava/core/blob/main/docs/recipes/add-a-custom-icon-set.md)

---

[← Docs index](../../README.md#documentation)
