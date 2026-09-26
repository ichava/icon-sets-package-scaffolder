# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **`composer.json` and the scaffolded stub list `laranail/db-tools` as a VCS repository.** `ichava/core` is about to
  require it, and Composer reads `repositories` from the root package only, so a
  consumer that does not declare it cannot resolve core at all. The entry is harmless
  until then. Nothing here is on Packagist.

## [0.1.3] - 2026-09-26

### Added

- **A test pins the `docs/` shape, and a workflow runs it on the changes that
  break it.** the scaffolded `DocsShapeTest` asserts the five concern pages the
  authoring standard requires, the concern pages a scaffolded pack receives, that the README
  indexes every page and lists none that is absent, and that each page opens at
  its `# ` title and carries the index link **exactly once** as its footer.

  `docs.yml` exists because of the gap it sits in: `tests.yml` and
  `code-quality.yml` both carry `paths-ignore: ['**.md']`, so a markdown-only
  pull request runs neither -- and a markdown-only pull request is exactly what
  deletes a docs page. It triggers on `**.md`, the test itself and its own file,
  and nothing else.

  Two of its assertions exist to stop the guard passing vacuously: the page glob
  is asserted non-empty before the loop reads it, and the README's page list is
  asserted non-empty before it is cross-checked. A glob that matches nothing
  otherwise makes every assertion below it true over a directory it never read.

- **A scaffolded package no longer ships dead links.** `stubs/README.md.stub`
  linked `CONTRIBUTING.md` and `SECURITY.md` and the stub tree contained
  neither, so every package ever generated was born with two 404s -- one of
  them in the section that tells a reporter where to send a vulnerability.
  Both stubs now exist.

- **`ScaffoldedLinkTargetsTest` asserts every relative link in a generated
  package resolves to a file that package contains.** No link checker could
  have caught the two above: a checker runs against *this* repository, where
  `stubs/README.md.stub` is not a document anyone renders and `{{token}}` is
  not a path. The only artefact where these links are real is the generated
  package, which exists solely inside a test, so the assertion has to run
  there.

  It skips HTML comments, fenced blocks and code spans. `attribution.md.stub`
  teaches the pack author what to write with a worked example inside a
  comment, and a guard that cannot tell an illustration from a live link fails
  on correct files until somebody deletes the illustration to appease it.

  It is mutation-checked: removing either new stub turns it red.

### Changed

- **`SECURITY.md` removed; the organization policy serves this repository now.**
  The file was byte-identical across six ichava repositories and held nothing
  specific to any of them. It was promoted into `ichava/.github` first, so the
  policy improved before any copy was removed rather than after, and GitHub
  serves that default on `/security/policy` for every repository without its
  own. The two channels and the 48-hour acknowledgement are unchanged.

- **The README's security link moved with it.** A relative
  `[SECURITY.md](SECURITY.md)` is a path into this repository's file tree, and
  the cascade does not put a file there -- it answers the policy page and
  nothing else. Left alone the link would have become a 404 the moment the file
  went, so it now points at `/security/policy` directly. `composer.json` and the
  issue-template link already did.

## [0.1.2] - 2026-09-22

### Added

- **A scaffolded pack ships the five concern pages the estate packs ship.**
  `stubs/docs/{installation,getting-started,configuration,architecture,release}.md.stub`,
  with `stubs/README.md.stub` pointing at them. A new pack was previously born
  with a `docs/` tree the estate's own authoring standard does not recognise.

- **The generator can scaffold a `Category` pack.** It emitted `Variant`
  unconditionally, so a scaffolded pack could not look like
  `icon-sets-bundled`, `icon-sets-metronic` or `icon-sets-emoji` --
  **three of the five packs in its own estate.** `IconAxis` now carries the
  choice through `PackageDefinition`, the CLI and the prompter, and four
  tokens (`{{axis}}`, `{{axisStudly}}`, `{{axisPlural}}`,
  `{{axisStudlyPlural}}`) render it. Two stubs carry the token in their
  *path*: `src/Enums/{{axisStudly}}.php.stub` and `docs/{{axisPlural}}.md.stub`.

  `plural()` is spelled out rather than suffixed -- `categories`, not
  `categorys` -- which is the same `-ies` case the shipped `ResourceShapeTest`
  already handled and nothing had ever exercised against a generated pack.

- **A scaffolded pack ships its own `metadata` guard.**
  `stubs/tests/Unit/PackageMetadataTest.php.stub` asserts that
  `metadata.repository` is the new package's own repository and that
  `metadata.homepage` is never one of ours.

  The scaffolder already guarded the value it *emits* -- it writes no
  `homepage` key, and `ScaffoldedMetadataTest` pins that. What no pack had was
  a guard of its own for afterwards, and afterwards is where this went wrong:
  all five estate packs were generated correctly and had `homepage` drift onto
  their own repository later, each needing the assertion added by hand. Five
  pull requests in one week, for a rule a newborn pack can simply carry.

  The stub asserts the **rule**, not a value, because the scaffolder cannot
  know the upstream: absent is accepted, a real upstream URL is accepted, and
  anything equal to `repository` or under our own vendor fails. Verified by
  mutation against the rendered pack -- 2 passed clean, 1 failed for each of
  three drifts, and 2 passed again for a genuine upstream value, so the guard
  is not merely refusing everything.

  It is a file of its own, and `ScaffoldedMetadataTest` asserts that too: a
  top-level `it()` sharing a file with a PHPUnit class makes Pest skip the
  class, which silently switched off 19 assertions across three packs.

### Changed

- **The "Authenticate Composer for private GitHub deps" step is gone.** It
  configured a `GH_PACKAGES_PAT` against `github-oauth.github.com` because
  `ichava/core` and `laranail/package-tools` were private. **Both are public
  now**, and so is every other dependency this pack resolves, so the step
  authenticated nothing.

  It was already dead rather than merely redundant, and the estate proved it:
  `icon-sets-emoji`'s `tests.yml` carries no such step and has been resolving
  `ichava/core` from a VCS repository on every CI run, green. Removing it is
  therefore not a gamble on rate limits -- it is matching the configuration
  that already works.

- **The dead Composer auth step is gone from the stub tree too.** A scaffolded
  pack was being born with a step configuring `GH_PACKAGES_PAT` for
  dependencies that are all public, so it authenticated nothing from the first
  commit. Removed from `stubs/.github/workflows/{code-quality,tests}.yml.stub`
  alongside this repository's own workflows.

  This is the second pass a repository-wide sweep needs and usually does not
  get: the estate fix and the stub fix are separate edits, and nothing here
  fails when only the first one lands.

- **Dead links to the deleted `ichava/documentation` repository removed.** That repository no
  longer exists, so every cross-reference to it resolved to a 404. The reporting channels in
  `SECURITY.md` were already stated inline and are unchanged; the Code of Conduct now cites the
  Contributor Covenant directly. Historical mentions in this changelog are left as written.

### Fixed

- **A scaffolded `Category` pack wrote its axis values into the wrong
  `config.json` key.** `metadata.data` carries both `variants` and
  `categories`, and `JsonConfigConstants` reads each **by its literal name**,
  so a config renaming the key to match its axis would leave
  `getVariants()` answering an empty array. Read off `origin/main`
  2026-09-21: metronic, bundled and flag all ship both keys whatever enum
  they expose. Both are always emitted now and the axis decides which one
  carries the values.

  The first version of the guard for this could not fail: it rendered a pack
  with **no** axis values, so both slots were `{}` and either assignment
  passed. It renders values now, and the mutation is caught.

- Core's contract names are not axis words. `IconsConstants`' docblock
  describes `getVariants()` / `getCategories()` on `JsonConfigConstants`, and
  templating those would have produced a `Category` pack whose docblock said
  `getVariants()` returns category slugs. Left alone deliberately, alongside
  `HasIconSetVariants` and `IconSetVariantInterface`, which a real `Category`
  pack still uses verbatim.


- **The stub tree linked at documentation pages that have moved out of `ichava/documentation`.**
  Eight links across `stubs/README.md.stub`, `stubs/docs/customization.md.stub` and
  `stubs/docs/variants.md.stub`, so **every pack scaffolded from here would have shipped a README
  and two docs pages pointing at 404s.**

  | Was | Is |
  |---|---|
  | `documentation/…/core/icon-path-format.md` | `core/…/docs/tools/icon-path-format.md` |
  | `documentation/…/core/blade-components.md` | `core/…/docs/tools/blade-components.md` |
  | `documentation/…/core/global-helper.md` | `core/…/docs/tools/global-helper.md` |
  | `documentation/…/icon-packs/seeding-pack-icons.md` | `core/…/docs/recipes/seed-pack-icons.md` |

  The ninth was worse and would have broken even though its file survives:
  `documentation/README.md#icon-packs` — that **anchor no longer exists** in the rewritten
  README, which is now security-only. It points at the owning packages instead.

  **The stub tree is template content, so a sweep over a repository's own docs does not see it.**
  Eight of the nine repositories in the migration fixed their own inbound links; this one fixed
  its `docs/` and not its `stubs/`. Same shape as the `$FLAGS` word-splitting and the dotted
  stubs dropped by a `mv` glob: the thing that ships is not the thing that was searched.

  Every new target was verified to exist on its repository's `main` before being linked — and
  the first check said `MISSING` for all four, because the local ref was stale. Fetch before
  believing a `cat-file -e`.

### Documentation

- `creating-icon-packages.md` documents the six axis tokens, and states what
  `--axis` does **not** reach: `icon-sets-emoji` needs two enums rather than a
  different value for one, and `icon-sets-bundled`'s `set-licenses.md` has no
  counterpart in any other pack. Both are one-offs; the guide says to scaffold
  the closest axis and add the rest by hand.

- Corrected a stale path. The guide told readers to copy stubs from
  `vendor/ichava/core/stubs/icon-package/`, which has not existed since
  scaffolding left core in `0.3.0`.

## [0.1.1] - 2026-09-21

### Added

- **Two parity cases over the generated `src/` tree**, both mutation-checked by reintroducing the
  stub and confirming they go red:

  - the top-level directory listing must match the estate pack's, which catches a stub that
    grows a subsystem the estate does not have or loses one it does;
  - a generated pack must register no Artisan command at all, and `V59`'s namespacing still
    applies if one is ever added deliberately.

  The second walks the tree with `RecursiveDirectoryIterator`. PHP's `glob()` does not treat
  `**` as "any depth" -- `src/**/*.php` matches exactly one directory level, so the first
  version of this guard would have missed a nested command and passed by finding nothing.


- **A skip is now a failure wherever the sibling was promised.** `ICHAVA_REQUIRE_ESTATE=1` makes
  `skipWithoutEstate()` fail instead of skip, and both CI and the scheduled run set it because
  they clone the pack themselves. A bare local checkout does not, and still skips politely.

  Pest exits `0` on a skipped test and this version has no `--fail-on-skipped`, so a broken
  clone step or a renamed directory reported green and said nothing. That is the failure this
  guard exists to catch, arriving through the guard itself, and it had already happened once.
  The contract lives in the test rather than in a grep over the output.

- **`estate-parity.yml`, a daily scheduled run of the parity guard.** A pull request only asks
  this question when *this* repository has one, so a change made in the estate is invisible here
  until something independently triggers a build.

  The window is measured, not hypothetical: the packs moved to `ichava/core: ^0.2.8` when
  `flag-icons#27` merged at `16:07:53Z`, and this package's last run before that started at
  `16:03:32Z` and passed -- correctly, for the estate as it stood 261 seconds earlier. A daily
  run closes the window to a day.

  Two properties of `schedule:` are written into the file because both are easy to trip over:
  it runs on the **default branch** only, whatever the file on a branch says; and GitHub
  disables scheduled workflows after 60 days of repository inactivity, announcing it only in the
  Actions tab. A quiet package is exactly the one whose stub drifts.


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

- **The stubs publish destination named a directory that no longer exists.** The bundled tree
  became `stubs/` when `stubs/icon-package/` was flattened, but the provider still published to
  `base_path('stubs/ichava/icon-package')`, and two docs pages repeated it as the `stubs_path`
  example. Now `base_path('stubs/ichava')`, matching the source shape.

  The destination is arbitrary -- it is a directory in the host application, and nothing breaks
  if it is wrong. It is worth fixing anyway: a path that names a layout nobody has reads as
  though it still means something, and the next person to change the tree will look for the
  `icon-package` directory it implies.

- **`docs/configuration.md` named the wrong config file** in the `V39` explanation --
  `icon-package-scaffolder.php` rather than `icon-sets-package-scaffolder.php`. It slipped the
  rebrand sweep because that pass matched `config/icon-package-scaffolder.php` with its
  directory prefix, and this instance is bare. Verified this time by reading the filename off
  disk and the config key out of the provider, then checking both appear in the page.


- **Every scaffolded package failed to boot.** The stub tree shipped
  `src/Commands/UpdateIconsCommand.php`, extending
  `Simtabi\Laranail\Ichava\Commands\UpdateIconsCommand` -- a class `ichava/core` has never
  had, at any commit. The generated provider registered it with
  `->hasCommand(UpdateIconsCommand::class)`, so a generated pack fatals the moment Laravel
  resolves its provider.

  Removed rather than repaired, because the estate does not have it: no pack in the ecosystem
  has ever had a `src/Commands/` directory. Refreshing assets goes through the `sync-upstream`
  workflow and `ichava/maintainer-toolkit`, and checking for upstream releases goes through
  `ichava::ichava-core.check-updates`. The generated README and attribution page now say that
  instead of documenting a command that could not run.

  **Nothing caught it**, which is the more interesting half. The parity guard compared
  `composer.json`, the provider, the workflows and the docs pages -- not `src/`. The end-to-end
  test ran `php -l` over every generated file, which is syntax and not class resolution. So a
  package that could not boot passed a suite that scaffolded it, linted it and compared it
  against a real pack.


- **The parity guard resolved the estate sibling by a name that no longer exists**, so it
  skipped instead of measuring. The 2026-09-21 restructure moved every package under
  `packages/` and renamed the local checkouts -- `flag-icons` is `icons-flag` now, while the
  GitHub repository keeps its old name -- and the guard spelled the directory. 59 passed became
  51 passed and 8 skipped, and a skip is green.

  `dirname(__DIR__, 3)` was never the problem and is unchanged: it resolves to the package
  parent, which was `ichava/` before and is `packages/` now, because the whole tree moved
  together. The docblock says so, because the depth is the obvious thing to reach for and it is
  not wrong.


- **The parity guard could not see an estate sibling checked out as a worktree.**
  `estateCheckoutPath()` tested `is_dir($path . '/.git')`, but `.git` is a
  directory only in a clone -- in a linked worktree it is a file. The guard
  skipped silently wherever the sibling was a worktree, and since skipping is
  now a CI failure, a guard blind to a valid checkout is worse than an absent
  one. It tests `file_exists()` now.


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

[0.1.0]: https://github.com/ichava/icon-sets-package-scaffolder/releases/tag/v0.1.0
