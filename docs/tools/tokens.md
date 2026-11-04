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
| `{{studlyName}}` | `Tabler` | As typed, studly. |
| `{{camelName}}` | `tabler` | |
| `{{kebabName}}` | `tabler` | |
| `{{snakeName}}` | `tabler` | |
| `{{snakeNameUpper}}` | `TABLER` | Env var prefixes. |
| `{{humanName}}` | `Tabler` | Headings and descriptions. |
| `{{namespace}}` | `AcmeLabs\IconSetsTabler` | Note the `IconSets` prefix. |
| `{{namespaceEscaped}}` | `AcmeLabs\\IconSetsTabler` | For JSON and double-quoted contexts. |
| `{{packageName}}` | `acme-labs/icon-sets-tabler` | Composer name. |
| `{{bladeNamespace}}` | `icon-sets-tabler` | Pack short name: the `icon-sets-` prefix plus the kebab name, never doubled. |
| `{{prefix}}` | `tabler` | Icon prefix (`icon_prefix` in `config.json`); defaults to the kebab name. |
| `{{email}}` | `dev@example.com` | |
| `{{iconSetType}}` | `single` or `multi` | |
| `{{kind}}` | `icon` or `illustration` | `package.kind` in `config.json`. |
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
