# Release

How a version of this package ships.

## Tag-driven

`release.yml` triggers on `push` of a `v*.*.*` tag, plus a `workflow_dispatch`
recovery path that refuses any ref but a release tag. Every other
workflow triggers on `pull_request` plus `workflow_dispatch`: a job that runs
after a change has landed is at the wrong end of the process, and double-runs on
every merge.

## Cutting one

Releases are prepared by [release-please](https://github.com/googleapis/release-please).
Nothing reaches `main` directly, a one-line docs fix included.

1. Every pull request that changes behaviour adds to the one `## [Unreleased]` section of
   `CHANGELOG.md`, and uses a Conventional Commit subject.
2. Run *Release Please* from the Actions tab (it also runs every Monday). It opens or refreshes one
   release pull request that bumps `.release-please-manifest.json`, and closes `[Unreleased]` as
   `## [X.Y.Z] - date` on that branch. It fails when `[Unreleased]` is empty.
3. Merge the release pull request once CI is green. That run tags `vX.Y.Z` and creates the release;
   the tag starts `release.yml`, which publishes the changelog section as the body.

On 0.x a breaking change (`feat!:` or a `BREAKING CHANGE:` footer) bumps the minor and everything
else bumps the patch. The minor moves in lockstep across the coupled ichava packages: when one of
them needs `0.N.0`, every coupled repository gets a pull request carrying a `Release-As: 0.N.0`
footer alongside its constraint change.
Never edit the release pull request by hand: release-please rebuilds its branch and drops anything
it did not write. Change the notes on `main` instead.

If a tag ever starts nothing, run `release.yml` by hand against it:
`gh workflow run release.yml --ref vX.Y.Z`. It refuses any ref that is not a release tag.

`release.yml` extracts that version's CHANGELOG section and passes it as the
GitHub release body. A release whose description is auto-generated notes or a
bare "see CHANGELOG" is incomplete.

## The 0.1.0 release

`v0.1.0` is the first release. The repository is published as a single commit, and that tag is cut
by hand on it rather than by release-please: `CHANGELOG.md` already carries a `## [0.1.0]` section,
which `release.yml` publishes as the body, and `.release-please-manifest.json` already reads `0.1.0`,
so release-please treats `v0.1.0` as the last release and proposes `0.1.1` next.

Two earlier series are folded into it. The tags `v0.1.0` to `v0.1.3` (2026-09-21 to 2026-09-26)
were removed when versioning was re-based on 2026-10-23, and the tags `v0.1.0` and `v0.1.1`
(2026-10-23 to 2026-10-27) were removed by the single-commit publication. Their notes are kept in
`CHANGELOG.md` under *Archived history*. Version numbers in older changelog entries and issues refer
to those earlier series.

## Versioning

Semantic versioning. For this package specifically:

- **A change to what a scaffolded pack contains is a feature, not a fix**, even
  when it corrects something. A consumer's existing packs do not change, but the
  next pack they generate does.
- **A change to a derived name is breaking.** The `Icons` suffix behaviour, the
  `ichava::` prefix on a generated pack's update command, the config filename --
  each of these renames files or classes in the output.
- No `version` field in `composer.json`. The tag is the version.

## Tags are final

A pushed `vX.Y.Z` tag is never moved, deleted or reused: once a tag has been fetched, lockfiles and
caches keep the commit it named while fresh installs resolve the moved one, so two consumers of the
same version run different code (and for a Go module, `sum.golang.org` makes the moved version fail
verification). Test a release with a pre-release tag (`vX.Y.Z-rc.N`), whose commit needs its own
`## [X.Y.Z-rc.N]` CHANGELOG section and which publishes as a GitHub pre-release. Retract a bad
release and fix it forward in the next version. The full rules are in
[CONTRIBUTING.md](../CONTRIBUTING.md#releases-and-versioning).

---

[← Docs index](../README.md#documentation)
