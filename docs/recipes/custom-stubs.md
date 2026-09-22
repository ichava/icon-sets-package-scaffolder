# Scaffold from your own stubs

Generate packs from a template tree you control.

Publish the bundled tree, edit it, point the config at it:

```bash
php artisan vendor:publish --tag=ichava::icon-sets-package-scaffolder-stubs
```

```php
// config/icon-sets-package-scaffolder.php
'stubs_path' => base_path('stubs/ichava'),
```

Or per environment:

```dotenv
ICHAVA_SCAFFOLDER_STUBS_PATH=/srv/templates/ichava-stubs
```

## What the tree may contain

Anything. The generator walks the directory and writes every file it finds, so a
new stub needs no registration -- there is no manifest to add it to.

Two conventions the tree has to keep:

- **A trailing `.stub` is stripped.** `composer.json.stub` becomes
  `composer.json`. A file without the suffix is copied under its own name.
- **`{{token}}` is substituted in contents and in path segments alike.**
  `src/{{studlyName}}/{{kebabName}}.php.stub` becomes `src/Hero/hero.php`. The
  vocabulary is in [Stub tokens](../tools/tokens.md); a token outside it is left
  as a literal in the generated file.

## Starting from an empty directory

A stubs path that does not exist is refused with a message naming it, rather than
producing an empty package. A path that exists and is empty produces a package
with no files, which is a legitimate thing to ask for and is not second-guessed.

---

[← Docs index](../../README.md#documentation)
