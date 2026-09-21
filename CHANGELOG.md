# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Scaffolded packs now ship the canonical `resources/` shape.** The stub tree
  carried `resources/assets/svg/config.json` and nothing else, so every
  generated pack was born missing `lang/` and `views/` -- the exact gap two
  estate packs had to be brought up from.

  - `resources/lang/en/icons.php.stub` -- variant labels, descriptions, command
    and info strings. It **omits `name` and `description` deliberately**:
    `config.json` is canonical for those and `IconRegistry` reads it. Every
    estate pack that kept a second copy had let the two drift, one of them
    describing a different product entirely, because nothing loaded the
    translation and so nothing compared them.
  - `resources/views/components/.gitkeep` -- placeholder, matching the estate.
  - `tests/Unit/ResourceShapeTest.php.stub` -- ships *with* the generated pack,
    so a new pack polices its own shape from its first commit.

- **`StubEstateParityTest` now covers `resources/`.** It compared composer
  constraints, the provider, command naming, workflows and docs, but nothing
  about resources -- which is how the stub could sit two phases behind the
  estate without the guard noticing. Three cases: the shape exists in both, the
  generated translation does not restate `config.json`, and its variant keys
  match the generated `Variant` enum.

  **Mutation-checked:** deleting the lang stub fails all three; restoring
  `name`/`description` fails the second; adding an enum case without a label
  fails the third.

### Fixed

- **The parity guard could not see an estate sibling checked out as a worktree.**
  `estateCheckoutPath()` tested `is_dir($path . '/.git')`, but `.git` is a
  directory only in a clone -- in a linked worktree it is a file. The guard
  skipped silently wherever the sibling was a worktree, and since skipping is
  now a CI failure, a guard blind to a valid checkout is worse than an absent
  one. It tests `file_exists()` now.

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

- **The estate guard resolved a sibling directory that no longer exists.** The 2026-09-21
  restructure moved the tree to `ichava/packages/` and renamed the local checkouts —
  `flag-icons` became `icons-flag` — while leaving every GitHub repository and composer name
  alone. `StubEstateParityTest` looked for `dirname(__DIR__, 3) . '/flag-icons'`, found nothing,
  and skipped: `51 passed, 8 skipped (162 assertions)` against `59 passed (208 assertions)`
  before the move. Restored to 59 and zero skipped.

  **It is a rename break, not a depth one.** `dirname(__DIR__, 3)` still resolves to `packages/`
  and was correct throughout, because the whole tree moved together. Anyone reaching for the
  depth would be changing something that is not wrong — and the demo app's path repositories,
  broken in the same move, are the opposite case: correct names at the wrong depth.

  The clone step in `tests.yml` moved with it. The repository is still `flag-icons` and the
  directory it clones into is now `icons-flag`, so the two spellings are both right in their own
  place and have to move together; both now say so where they are written.

  The skip message names the path it looked for rather than the pack. Every way this guard has
  actually skipped was a path that moved, and a message naming the pack reads like a network or
  ref problem instead — which is how eight silent skips went unnoticed through a green run.

- **`skipWithoutEstate()`'s docblock claimed CI clones one repository and that skipping was
  correct there.** CI clones the sibling deliberately and has since the initial release. The
  claim outlived the workflow step that falsified it, and it is the source of the same wrong
  statement corrected in the entry above.

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

- **`sync-upstream.yml.stub` word-split its `--force` flag.** `FLAGS=""` built a string that
  reached `sync --pack="$PACK_SLUG" $FLAGS` unquoted, so the argument arrived only because
  word-splitting happened to do the right thing — and quoting it, the obvious repair, would have
  passed an empty argument instead. It is now an array: `FLAGS=()`, `FLAGS+=(--force)`,
  `"${FLAGS[@]}"`, which the five real packs already carry.

  **The new job found this on its first run**, as `SC2086` on the rendered file. It had been in
  the stub since the workflow was written and no gate in the estate could see it.

- **The stub's `ichava/core` constraint was a release behind the estate.** It read
  `^0.2.5 || ^0.3` while all five packs had moved to `^0.2.8 || ^0.3`, so a freshly scaffolded
  pack would resolve an older core than any pack in the family. `StubEstateParityTest` catches
  this by measuring the stub against `flag-icons` at `origin/main` rather than against a literal,
  and `tests.yml` clones the sibling so the guard runs on CI as well as locally.

  **The window it leaves is a scheduling one, not a coverage one**, and the margin here was four
  minutes: this package's last CI run before the drift started at 16:03:32 UTC, `flag-icons`
  merged `^0.2.8 || ^0.3` at 16:07:53 UTC, and that run passed because it was correct when it
  ran. A cross-repo guard is only evaluated when *this* repo has a pull request, and nothing in
  the estate triggers one — so the stub stayed behind until the next local `vendor/bin/pest`.
  Closing that properly means a scheduled run here or moving the assertion to where the estate
  changes; both are decisions rather than repairs, and neither is made yet. Until one is, run
  the suite locally before releasing.

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
