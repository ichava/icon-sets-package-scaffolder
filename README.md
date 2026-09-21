# ichava/icon-sets-package-scaffolder

[![Tests](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/tests.yml/badge.svg)](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/tests.yml)
[![Static analysis](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/code-quality.yml/badge.svg)](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/code-quality.yml)
[![License MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Scaffolds a complete Ichava icon package from one command: a token-substituted
> stub tree, the icon directory layout, and the CI the ecosystem's conventions expect.

Requires PHP 8.4.1+ and Laravel 13. No registry version badge: nothing in this
ecosystem is published to Packagist yet, so a Packagist badge would render as
`invalid` and claim the package is installable when it is not.

## Install

```bash
composer require --dev ichava/icon-sets-package-scaffolder
```

A dev dependency on purpose. Scaffolding is something you do once per pack, on a
workstation; nothing an installed pack does at runtime goes through this package.

It does not require `ichava/core`. That direction is deliberate: core takes this
as a `require-dev`, and a package that both generates core's packs and depends on
core would be a cycle.

## Usage

```bash
php artisan ichava::icon-package-scaffolder.make
```

Answer the prompts, or supply everything and skip them:

```bash
php artisan ichava::icon-package-scaffolder.make Hero \
  --vendor=Acme --email=dev@example.com \
  --path=../acme-hero-icons --type=multi --variants=outline,solid \
  --no-interaction
```

Type the bare noun. `Hero` yields `acme/hero-icons` and `Acme\HeroIcons`; the
`Icons` suffix is added for you.

## <a name="documentation"></a>Documentation

Hosted at
[opensource.simtabi.com/documentation/ichava/icon-sets-package-scaffolder/](https://opensource.simtabi.com/documentation/ichava/icon-sets-package-scaffolder/).

### Guides

- [Installation](docs/installation.md) - requirements and how it is wired in
- [Getting started](docs/getting-started.md) - your first pack, end to end
- [Configuration](docs/configuration.md) - pointing it at your own stub tree
- [Architecture](docs/architecture.md) - actions, services, domain, and why
- [Release](docs/release.md) - how a version ships

### Reference

- [`ichava::icon-package-scaffolder.make`](docs/tools/make-command.md) - every argument and option
- [Stub tokens](docs/tools/tokens.md) - the token vocabulary a stub may use

### Recipes

- [Scaffold from your own stubs](docs/recipes/custom-stubs.md)
- [Scaffold from code, without a terminal](docs/recipes/programmatic-scaffolding.md)

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities through
[SECURITY.md](SECURITY.md), never as a public issue.

## License

MIT. See [LICENSE](LICENSE).
