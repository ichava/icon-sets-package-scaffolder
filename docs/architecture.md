# Architecture

How the package is laid out, and why it is laid out that way rather than as the
one class it came from.

## Layers

```
Console/          prompts, output, the Artisan command
   |
Actions/          one use case each, one public method each
   |
Services/         one capability each, injected
   |
Domain/           pure. Validates, derives, describes.
```

Three rules, and each one is there because breaking it is what produced the class
this package replaced:

- **An action has exactly one public method.** `__invoke`. The moment it grows a
  second entry point it is a service.
- **A service may not depend on an action.** Dependencies point one way, so
  reading a service never means reading the use cases that call it.
- **`Domain/` imports nothing from the framework.** The one exception is
  `Illuminate\Support\Str`, a pure string helper with no container, config or I/O
  behind it. Reimplementing `slug()` to claim purity would be worse code enforcing
  a rule nobody reads.

## The pieces

| Class | Layer | Responsibility |
|---|---|---|
| `MakeIconPackageCommand` | Console | Collect input, render the result. Decides nothing. |
| `PackageDefinitionPrompter` | Console | The only importer of a prompt library. |
| `DestinationResolver` | Console | Asks for a path, creates a missing parent. |
| `NextStepsReporter` | Console | Turns a `ScaffoldResult` into advice. |
| `ScaffoldIconPackage` | Action | The public API. Guards the destination, delegates. |
| `RenderStubTree` | Action | Every stub to its destination. |
| `CreateIconDirectories` | Action | The SVG layout, one directory per variant. |
| `StubLocator` | Service | Where stubs come from, and where each one goes. |
| `StubRenderer` | Service | What happens to a stub's text. |
| `TokenMapFactory` | Service | The token vocabulary. |
| `PackageDefinition` | Domain | Validates once; derives every public name. |
| `IconVariant`, `IconSetType` | Domain | The variant rules. |
| `ScaffoldResult` | Domain | What a scaffold produced. |
| `TargetPath` | Support | Path resolution, without touching the filesystem. |

## Why the seam is where it is

Before the extraction, generation was reachable only through a command's
`handle()`. Exercising it meant driving interactive prompts, so in practice it was
not exercised at all, and eight defects accumulated in the stub tree with nothing
watching.

`ScaffoldIconPackage` is the answer to that:

```php
$result = ($scaffold)($definition, TargetPath::absolute($path));
```

A test, a CI job, a queued job or a future browser UI calls that. The command is
one caller among several rather than the only way in.

## Why validation lives on the domain object

The rules used to be closures in prompt `validate:` arguments. That meant they ran
only when a human typed the answer: every option-driven run -- every test, every
CI invocation, every `--force` scaffold -- enforced nothing, and a malformed email
reached `composer.json` intact.

`PackageDefinition::create()` is a private constructor behind a validating named
constructor, so an invalid definition cannot be built at all. The rules hold
however the generator is driven.

## Why it does not depend on `ichava/core`

Core takes this package as a `require-dev`, so a dependency back on core would be
a cycle. It is also unnecessary: the generator's only structural tie to core was
`extends BaseCommand`, and the one thing that base supplied --
`SupportsNamespacedNames`, the trait that writes a `::` name past Symfony's
`validateName()` -- comes from `laranail/console` directly.

That tie was easy to miss. A grep for `use` statements finds nothing, because
`BaseCommand` sits in the same namespace and needs no import. **Check `extends`
and `implements` separately from `use`.**

## Why stubs are discovered, not listed

`StubLocator` walks the tree with Symfony Finder. Adding a stub is dropping a
file in; there is nothing to register.

`ignoreDotFiles(false)` is load-bearing. The tree ships `.gitignore.stub`,
`.gitattributes.stub` and four workflows under `.github/`, all of which Finder
skips by default -- and a pack generated without them looks complete and has no
CI. `ignoreVCS(true)` is safe alongside it: Finder's VCS list matches `.git`,
`.svn` and friends as whole directory names, so `.github` is untouched.

---

[← Docs index](../README.md#documentation)
