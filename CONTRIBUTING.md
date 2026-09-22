# Contributing

Contribution guidelines are centralized for the whole Ichava org:

- [Contributing guide](https://github.com/ichava/documentation/blob/main/CONTRIBUTING.md)
- [Code of Conduct](https://github.com/ichava/documentation/blob/main/CODE_OF_CONDUCT.md)

## Working on this package

```bash
composer install
composer test      # the whole suite
composer lint      # pint, --test mode
composer analyse   # phpstan, level 6
```

Four things about this package specifically:

- **A stub is added by dropping a file into `stubs/`.** There is no
  manifest to register it in, on purpose: a list is a thing a new stub gets left
  out of, and the omission is silent.
- **A new `{{token}}` goes in `Services\TokenMapFactory`, and a test checks it.**
  `StubTreeTest` scans every stub for token references and fails if one is not
  produced, so a stub cannot ship a literal `{{token}}` into a generated package.
- **An action has exactly one public method.** `__invoke`, and nothing else. The
  moment it grows a second entry point it is a service, and services are what
  accreted into the 737-line class this package was carved out of.
- **`Domain/` imports nothing from the framework** beyond `Illuminate\Support\Str`,
  which is a pure string helper with no container behind it. Validation lives
  there so it holds however the generator is driven -- through the command, from
  a test, or from code.

## A fix that lands across the estate needs a second pass here

**`stubs/` is the blind spot of every sweep.** A change made across the ichava
repositories does not reach it, the sweep reports success, and the defect then
ships in every pack generated afterwards rather than in anything visible here.

Two mechanics cause it, and they compound:

- **The extension differs.** A sweep over `*.md` or `.github/workflows/*.yml`
  matches nothing under `stubs/`, where the same files are `*.md.stub` and
  `*.yml.stub`.
- **The defect is invisible locally.** A stub is not a rendered page. A dead
  link in `stubs/docs/variants.md.stub` resolves against a directory that only
  exists after scaffolding, so no link checker, no test and no reader in this
  repository ever meets it.

Four of these were found in a single evening on 2026-09-21, three only because
something unrelated went looking:

| Shipped into every scaffolded pack | Found while |
|---|---|
| 8 links to `ichava/documentation` pages a migration had deleted | reviewing that migration |
| a breadcrumb **and** footer reading `[← {{humanName}} docs](README.md)` — from `docs/x.md` that resolves to `docs/README.md`, which the docs standard forbids and no pack has. Six dead links per generated pack | normalising breadcrumbs across the estate |
| `## Pack-specific docs` — the **origin** of the `#pack-specific-docs` anchor that 16 pack doc pages pointed at | rewriting the stub README |
| `paths-ignore: ['*.md']`, which matches only root-level markdown | fixing that filter in nine repositories, none of which touched these stubs |

The last is the clearest: the fix swept `.github/workflows/*.yml` in nine
repositories, went green, and left `stubs/.github/workflows/*.yml.stub` carrying
exactly the defect it existed to remove.

**So after any estate-wide change, run the same check over the rendered stub
tree** — the output, not the template:

```bash
git ls-tree -r --name-only origin/main -- stubs \
| while read -r f; do git show "origin/main:$f"; done \
| grep -n '<the thing you just fixed>'
```

`StubEstateParityTest` and `ScaffoldedDocsShapeTest` catch the subset they
assert on, and are worth extending whenever a new invariant appears. Neither
replaces the sweep: they pin what someone thought to pin, and all four defects
above sat outside that set until the day they were found.

Conventional Commits for messages. Every change reaches `main` through a pull
request; nothing is pushed to `main` directly.
