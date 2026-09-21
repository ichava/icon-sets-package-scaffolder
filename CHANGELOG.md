# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **`actionlint` runs on every pull request.** Nothing validated the workflow files at all:
  `release.yml` triggers only on `push: tags`, so a broken workflow was first observed as a
  release that refused to start — after the decision to release had been made.

  A YAML parse is not a substitute, and that is the sharp part. `yaml.safe_load` accepts a
  duplicate key and silently keeps the last one, so a double-applied patch that left
  `continue-on-error:` twice on a single step validated clean and would have failed only at tag
  time. `actionlint` rejects what Actions rejects.

  Checked against the defect rather than assumed: injecting that duplicate key, a typo'd step
  key, and an `if:` referencing a property that does not exist are all caught, while
  `yaml.safe_load` still parses the first of them without complaint.

- **The scaffolded workflows are linted too, by scaffolding a package and linting that.** The job
  above reads `.github/workflows/`, which is this package's own CI — not the CI it generates. The
  four workflow stubs are invisible to it twice over: they live under `stubs/`, and a `.stub`
  extension keeps them out of every YAML tool regardless. They could not be linted in place even
  if they were found, because `{{kebabName}}` opens a YAML flow mapping, so a stub is not a valid
  document standalone.

  `scripts/scaffold-sample.php` renders the tree with no terminal and no Laravel app, through the
  package's own `ScaffoldIconPackage` resolved from a real container — the provider binds exactly
  one thing, `StubLocator`, and the rest autowires. Hand-wiring it would drift from the provider
  the first time a constructor gains an argument, and drift here means CI lints a tree the command
  does not produce.

  **The job asserts on the number of files actionlint read, and that assertion is the point.**
  `raven-actions/actionlint` applies its `working-directory` input to one internal step, not to
  the lint step, which is a `github-script` resolving `files:` against `GITHUB_WORKSPACE` — so a
  scratch package rendered into `RUNNER_TEMP` globs to nothing. A glob matching nothing is not an
  error: actionlint falls back to linting `./.github/workflows`, finds this repository's own files
  already green from the job above, and passes having checked nothing of what it was pointed at.
  Comparing the action's `total-files` output against the stub count is what separates the two.

- **A scaffolded package now ships the `actionlint` job itself**, so a new pack lints its own
  workflows from its first pull request rather than inheriting the gap the five existing packs
  just closed.

### Fixed

- **A failed SBOM download no longer takes the whole release down.** `release.yml` generates the
  SBOM before it publishes, and the Syft installer fetches its checksums from GitHub's
  release-asset CDN. On 2026-09-21 that answered `504` for about twenty minutes, failing the job
  four times *before* the publish step — so the tag existed with no release behind it, which is
  the drift the release table exists to catch, produced by the release machinery itself.

  Two changes. The step now retries once after 45 seconds, which covers a single transient `504`
  — the common case. And a second failure no longer fails the job: the release publishes without
  the asset and emits a `::warning::` naming the re-run.

  **The two failure states are not equally bad, and that asymmetry is the whole design.** A
  release missing an attachment is repaired by re-running this workflow, which re-attaches it. A
  tag with no release persists silently until a person notices. Preferring the recoverable one
  is worth the loss of "every release always carries an SBOM" as an absolute.

  `fail_on_unmatched_files: false` is now stated on the publish step. It is already the action's
  default, but the point of this change is that a missing SBOM must not fail the publish, so it
  should not rest on a default a future reader has to know.

- **The stub's `ichava/core` constraint was a release behind the estate.** It read
  `^0.2.5 || ^0.3` while all five packs had moved to `^0.2.8 || ^0.3`, so a freshly scaffolded
  pack would resolve an older core than any pack in the family. `StubEstateParityTest` catches
  this by measuring the stub against `flag-icons` at `origin/main` rather than against a literal,
  but it skips when no sibling checkout is present — which is every CI runner. It is a guard that
  only fires on a maintainer's machine; run the suite locally before releasing.

- **The `[Unreleased]` block carried two `### Fixed` sections describing the same SBOM change in
  contradictory terms.** One said a second failure publishes the release without the asset; the
  other said two failures still fail the release. The workflow does the first — `sbom_retry`
  carries `continue-on-error: true` and the publish step sets `fail_on_unmatched_files: false`.
  The stale section has been removed rather than reconciled.

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
  a scaffolded package against `ichava/flag-icons` at `origin/main` rather than against literals,
  because a literal encodes the estate as it was the day it was written and then ages silently
  beside the thing it was supposed to guard. Core's CI cloned the sibling for it; that clone
  moved here too.

  It reads the pack through `git ls-tree` and `git show`, not off disk and not through
  `git archive`. Both of those are wrong in ways that took a failure each to find. The working
  tree makes the guard answer differently depending on what branch somebody has open next door,
  red in one checkout and green in another for a reason CI cannot reproduce. `git archive`
  applies `export-ignore`, and every pack export-ignores `.github`, `docs`, `tests` and
  `CONTRIBUTING.md` to keep them out of dist tarballs -- so four of the things this compares
  would not be in the archive at all, and the workflow case failed claiming the estate ships none.

  The cost is a sequencing constraint that is now visible rather than hidden: a change moving
  both the stub and the estate is red here until the estate side merges. Land the pack first,
  then the stub -- which is how the `^0.2.5 || ^0.3` constraint below actually got there, in two
  steps rather than one, with the guard red in between and saying why.
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
