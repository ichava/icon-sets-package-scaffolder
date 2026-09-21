[← Docs index](../README.md#documentation)

# Release

How a version of this package ships.

## Tag-driven

`release.yml` triggers on `push` of a `v*.*.*` tag and nothing else. Every other
workflow triggers on `pull_request` plus `workflow_dispatch`: a job that runs
after a change has landed is at the wrong end of the process, and double-runs on
every merge.

## Cutting one

1. Open a pull request. Nothing reaches `main` directly, a one-line docs fix
   included -- the value is that everything on the default branch went through
   the same gate.
2. Add the version's section to `CHANGELOG.md`, Keep-a-Changelog format,
   `## [X.Y.Z] - YYYY-MM-DD`.
3. Merge once CI is green.
4. Tag `vX.Y.Z` on the merge commit and push the tag.

`release.yml` extracts that version's CHANGELOG section and passes it as the
GitHub release body. A release whose description is auto-generated notes or a
bare "see CHANGELOG" is incomplete.

## Versioning

Semantic versioning. For this package specifically:

- **A change to what a scaffolded pack contains is a feature, not a fix**, even
  when it corrects something. A consumer's existing packs do not change, but the
  next pack they generate does.
- **A change to a derived name is breaking.** The `Icons` suffix behaviour, the
  `ichava::` prefix on a generated pack's update command, the config filename --
  each of these renames files or classes in the output.
- No `version` field in `composer.json`. The tag is the version.

---

[← Docs index](../README.md#documentation)
