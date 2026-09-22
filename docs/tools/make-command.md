# `ichava::icon-sets-package-scaffolder.make`

The one command this package registers. Scaffolds a complete icon package.

## Arguments and options

| Name | Kind | Default | Notes |
|---|---|---|---|
| `name` | argument | prompted | The bare noun. `Hero`, not `HeroIcons`. |
| `--vendor` | option | prompted | Vendor name. Studly for the namespace, kebab for the composer name. |
| `--email` | option | prompted | Author email. Validated; a malformed one fails the run. |
| `--path` | option | prompted | Destination. Absolute, or relative to the application root. |
| `--prefix` | option | kebab name | Blade prefix: `<x-{prefix}-icon name="..." />`. |
| `--type` | option | `single` | `single` or `multi`. |
| `--variants` | option | prompted for `multi` | Comma separated. The first is the default. |
| `--force` | flag | off | Overwrite a non-empty destination. |

## Exit codes

| Code | Meaning |
|---|---|
| `0` | Package written. |
| `1` | Failed -- an occupied destination, an unwritable parent, a missing stub. |
| `2` | Invalid input -- a bad email, a name under two characters, `multi` with no variants. |

The split matters for scripting: `2` is the caller's input and `1` is the
environment, so a wrapper can retry one and not the other.

## The name

`ichava::icon-sets-package-scaffolder.make`, and no bare alias. An alias like
`make:icon-package` would be a generic key in Artisan's flat command map -- which
is the collision the namespaced name exists to prevent -- and it would sit inside
Laravel's own `make:` namespace, where a framework command could claim it.

The `::` works because `SupportsNamespacedNames` writes the name past Symfony's
`validateName()`, which rejects the empty segment. Dispatch is unaffected: Symfony
resolves an exact name before falling back to splitting on `:`.

## Non-interactive runs

With every option supplied, the command never reaches a prompt. Pass
`--no-interaction` anyway in a script: without it a missing option reaches a
prompt, and a prompt with no TTY blocks rather than fails.

Under `--no-interaction` a missing required answer becomes exit `2` naming the
field, not a hang.

## What it does not do

It does not run `composer install` in the generated package, and it does not
initialise a git repository. Both are the author's to decide.

---

[← Docs index](../../README.md#documentation)
