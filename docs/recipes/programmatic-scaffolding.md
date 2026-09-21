[← Docs index](../../README.md#documentation)

# Scaffold from code, without a terminal

Generate a package from a test, a queued job, a CI step or an HTTP handler.

```php
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Actions\ScaffoldIconPackage;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\PackageDefinition;
use Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Support\TargetPath;

$definition = PackageDefinition::create(
    name: 'Hero',
    vendor: 'Acme',
    email: 'dev@example.com',
    type: 'multi',
    variants: ['outline', 'solid'],
);

$result = app(ScaffoldIconPackage::class)(
    $definition,
    TargetPath::absolute('/srv/packages/acme-hero-icons'),
);

$result->definition->packageName();   // acme/hero-icons
$result->fileCount();                 // 26
$result->files;                       // package-root-relative, sorted
$result->directories;                 // resources/assets/svg/files/outline, ...
```

No prompt is reached on this path, because there is no prompt on it: prompting
lives in `Console\PackageDefinitionPrompter`, which the action never touches.

## Failure modes

| Thrown | When |
|---|---|
| `InvalidPackageDefinition` | Bad input -- caught at `create()`, before anything is written. |
| `InvalidArgumentException` | A path that is empty, relative with no base, or the filesystem root. |
| `RuntimeException` | The destination exists and is not empty, or a stub is missing. |

Pass `force: true` as the third argument to overwrite a non-empty destination.
It is a third argument rather than a default because the failure mode is
overwriting somebody's package.

## Validation runs here too

`PackageDefinition::create()` is the only way to build one, and it validates. The
rules used to be closures inside prompt `validate:` arguments, which meant this
path -- the one every test and CI job takes -- enforced none of them.

---

[← Docs index](../../README.md#documentation)
