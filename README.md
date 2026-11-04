# ichava/icon-sets-package-scaffolder

[![Packagist](https://img.shields.io/packagist/v/ichava/icon-sets-package-scaffolder)](https://packagist.org/packages/ichava/icon-sets-package-scaffolder)
[![Tests](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/tests.yml/badge.svg)](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/tests.yml)
[![Static analysis](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/code-quality.yml/badge.svg)](https://github.com/ichava/icon-sets-package-scaffolder/actions/workflows/code-quality.yml)
[![License MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Scaffolds a complete Ichava icon package from one command: a token-substituted
> stub tree, the icon directory layout, and the CI the ecosystem's conventions expect.

Requires PHP `^8.4.1 || ^8.5` and Laravel `^13`.

## Install

The package is on Packagist, but two of its dependencies, `laranail/console` and
`laranail/package-tools`, are not yet, so add them as VCS repositories to your
application's `composer.json` first:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/laranail/package-tools.git" },
        { "type": "vcs", "url": "https://github.com/laranail/console.git" }
    ]
}
```

```bash
composer require --dev ichava/icon-sets-package-scaffolder:^0.1
```

A dev dependency on purpose. Scaffolding is something you do once per pack, on a
workstation; nothing an installed pack does at runtime goes through this package.

It does not require `ichava/core`. That direction is deliberate: core takes this
as a `require-dev`, and a package that both generates core's packs and depends on
core would be a cycle.

## Quick start guide and usage

### Getting started

Nothing to publish or configure: the service provider registers itself through
package discovery, and the command reads its stubs from this package. Point it at
your own stub tree only if you want one ([Configuration](docs/configuration.md)).

### Usage

```bash
php artisan ichava::icon-sets-package-scaffolder.make
```

Answer the prompts, or supply everything and skip them:

```bash
php artisan ichava::icon-sets-package-scaffolder.make tabler \
  --vendor=Ichava --email=ichava@simtabi.com \
  --path=../icon-sets-tabler --prefix=ti --type=multi --variants=outline,filled \
  --no-interaction
```

Type the bare slug. `tabler` yields `ichava/icon-sets-tabler` and
`Ichava\IconSetsTabler`; the `icon-sets-` prefix is added for you, and typing
the full `icon-sets-tabler` gives the same names. Pass `--path` yourself and
name the directory `icon-sets-<slug>`, as the existing packs are.

See [Getting started](docs/getting-started.md) for the full walkthrough, and the
[documentation index](#documentation) for everything else.

## <a name="documentation"></a>Documentation

The documentation lives in this repository, under `docs/`.

### Guides

- [Installation](docs/installation.md) - requirements and how it is wired in
- [Getting started](docs/getting-started.md) - your first pack, end to end
- [Configuration](docs/configuration.md) - pointing it at your own stub tree
- [Architecture](docs/architecture.md) - actions, services, domain, and why
- [Release](docs/release.md) - how a version ships
- [Creating icon packages](docs/creating-icon-packages.md) - the anatomy of a pack, by hand or generated

### Reference

- [`ichava::icon-sets-package-scaffolder.make`](docs/tools/make-command.md) - every argument and option
- [Stub tokens](docs/tools/tokens.md) - the token vocabulary a stub may use

### Recipes

- [Scaffold from your own stubs](docs/recipes/custom-stubs.md)
- [Scaffold from code, without a terminal](docs/recipes/programmatic-scaffolding.md)
- [Build your own pack](docs/recipes/build-your-own-pack.md)

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities through
[security policy](https://github.com/ichava/icon-sets-package-scaffolder/security/policy), never as a public issue.

## License

MIT. See [LICENSE](LICENSE).
