# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-09-21

First release. Extracted from `ichava/core`, where the generator was a single
737-line command class reachable only by driving interactive prompts.

### Added

- `ichava::icon-package-scaffolder.make`, the scaffolding command. Namespaced per
  `V59`; no bare alias is registered, so nothing claims Laravel's `make:` namespace
  the way core's `make:icon-package` did.
- `Actions\ScaffoldIconPackage`, the package's public API. Takes a
  `PackageDefinition` and a `TargetPath`, returns a `ScaffoldResult`. A caller with
  no terminal -- a test, a CI job, a queued job -- can generate a package without
  a prompt.
- `Domain\PackageDefinition`, which validates once and derives every public name.
  The rules used to be closures in prompt `validate:` arguments, so an
  option-driven run enforced none of them.
- `Support\TargetPath`, which resolves a destination without touching the
  filesystem and refuses the filesystem root. `rtrim` turns `/` into `""` and
  `C:\` into `C:`; both used to fall through to being treated as relative.
- A publishable stub tree, and `ichava.icon-package-scaffolder.stubs_path` for
  pointing the generator at your own.
- `StubEstateParityTest`, moved here from `ichava/core` with the stub tree it guards. It measures
  a scaffolded package against `ichava/flag-icons` read off disk rather than against literals,
  because a literal encodes the estate as it was the day it was written and then ages silently
  beside the thing it was supposed to guard. Core's CI cloned the sibling for it; that clone
  moved here too.
- 59 tests. The extraction was gated on generating byte-identical output to core's command for
  both a single-set and a multi-variant pack -- 24 files and 25 files, `diff -r` clean against
  core at `a902a5e`.

### Changed

- Generation refuses a non-empty destination unless forced, rather than prompting
  and proceeding.
- Running with `--no-interaction` completes from options instead of blocking on a
  TTY that is not there.

### Known behaviour, inherited and pinned

- The `Icons` suffix is appended to the name you type, so `HeroIcons` yields
  `acme/hero-icons-icons` and `Acme\HeroIconsIcons`. Pinned by a test rather than
  changed, because changing it silently would rename every class in a pack
  scaffolded before the change.
- A scaffolded pack's update command is named `ichava::<slug>-icons.update`
  regardless of the vendor you give. Inherited from the stub.

[0.1.0]: https://github.com/ichava/icon-package-scaffolder/releases/tag/v0.1.0
