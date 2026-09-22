# Stub tokens

Every `{{token}}` a stub may use, and what it expands to.

Tokens are substituted in file **contents** and in **path segments**, using the
same mustache form. One convention, no separate filename grammar.

For `name: Hero`, `vendor: Acme Labs`, `email: dev@example.com`:

| Token | Value | Notes |
|---|---|---|
| `{{vendor}}` | `Acme Labs` | Verbatim, as typed. |
| `{{vendorStudly}}` | `AcmeLabs` | Namespace segment. |
| `{{vendorLower}}` | `acme labs` | |
| `{{vendorKebab}}` | `acme-labs` | Composer vendor. |
| `{{vendorSnake}}` | `acme_labs` | |
| `{{studlyName}}` | `Hero` | As typed, studly. |
| `{{camelName}}` | `hero` | |
| `{{kebabName}}` | `hero` | |
| `{{snakeName}}` | `hero` | |
| `{{snakeNameUpper}}` | `HERO` | Env var prefixes. |
| `{{humanName}}` | `Hero` | Headings and descriptions. |
| `{{namespace}}` | `AcmeLabs\HeroIcons` | Note the appended `Icons`. |
| `{{namespaceEscaped}}` | `AcmeLabs\\HeroIcons` | For JSON and double-quoted contexts. |
| `{{packageName}}` | `acme-labs/hero-icons` | Composer name. |
| `{{bladeNamespace}}` | `hero-icons` | Pack short name. |
| `{{prefix}}` | `hero` | Blade component prefix. |
| `{{email}}` | `dev@example.com` | |
| `{{iconSetType}}` | `single` or `multi` | |
| `{{variantsJson}}` | `{}` or a JSON object | Pretty-printed, already ordered. |
| `{{year}}` | `2026` | |
| `{{date}}` | `2026-09-21` | |

## Adding one

Add it to `Services\TokenMapFactory::for()`. A test scans every stub for token
references and fails if one is not produced, so a stub cannot ship a literal
`{{token}}` into a generated package.

## Two that are easy to get wrong

- **`{{namespaceEscaped}}` is a distinct token, not something a stub re-escapes.**
  A PHP namespace inside JSON needs every separator doubled, and a stub that
  escapes inline gets it wrong in one of the several places it appears.
- **`{{variantsJson}}` is `{}` for an empty set, never `[]`.** `json_encode` turns
  an empty PHP array into `[]`, and `config.json` declares `variants` as an
  object; a pack shipping `"variants": []` reads back as the wrong type the
  moment anything iterates it as a map. The variant `attributes` field carries the
  same trap and is cast to an object for the same reason.

---

[← Docs index](../../README.md#documentation)
