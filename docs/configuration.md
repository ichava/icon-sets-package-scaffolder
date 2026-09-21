# Configuration

One setting, and the reason the config file is named what it is.

## The file

```bash
php artisan vendor:publish --tag=ichava::icon-sets-package-scaffolder-config
```

Lands at `config/icon-sets-package-scaffolder.php`, read at
`ichava.icon-sets-package-scaffolder.*`.

| Key | Env | Default | What it does |
|---|---|---|---|
| `stubs_path` | `ICHAVA_SCAFFOLDER_STUBS_PATH` | `null` | Directory to read stubs from. `null` uses the bundled tree. |

## The filename is load-bearing

`laranail/package-tools` namespaces config by `vendor.package`, then appends the
config **filename** whenever the filename differs from the package short name:

```php
return $configFileName === $this->shortName() ? $base : $base . '.' . $configFileName;
```

So a file named anything other than `icon-sets-package-scaffolder.php` would merge at
`ichava.icon-sets-package-scaffolder.<filename>.*` while every read site used the
shorter key. Every read would return `null`, and nothing would say so.

That is not hypothetical. `ichava/core` shipped `config/ichava.php` and merged at
`ichava.core.ichava.*` while 68 read sites used `config('ichava.*')`: the whole
shipped config was inert for months -- cache TTLs, batch size, queue, logging,
the entire security block.

**Name a config file after the package short name.** Nothing enforces it, neither
the tests nor CI notice, because both run against the same fallbacks production
falls through to.

## Your own stubs

```php
'stubs_path' => base_path('stubs/ichava'),
```

The generator walks whatever it finds there, so a custom tree needs no
registration -- only the same token vocabulary. Start from a copy:

```bash
php artisan vendor:publish --tag=ichava::icon-sets-package-scaffolder-stubs
```

See [Scaffold from your own stubs](recipes/custom-stubs.md).

---

[← Docs index](../README.md#documentation)
