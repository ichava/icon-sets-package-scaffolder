# Contributing

Contribution guidelines are centralized for the whole Ichava org:

- [Contributing guide](https://github.com/ichava/documentation/blob/main/CONTRIBUTING.md)
- [Code of Conduct](https://github.com/ichava/documentation/blob/main/CODE_OF_CONDUCT.md)

## Working on this package

```bash
composer install
composer test      # 47 tests
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

Conventional Commits for messages. Every change reaches `main` through a pull
request; nothing is pushed to `main` directly.
