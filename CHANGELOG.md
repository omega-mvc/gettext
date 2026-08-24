# Gettext

All notable changes to this package will be documented in this file.

This project follows Semantic Versioning.

---

## [Unreleased]

### Added

- Native PHP 8.4 typing across the whole library: domain model, value objects,
  loaders, generators, scanners/AST visitors, translation runtime and global
  functions (`src/` is fully clean under PHPStan level 10).

### Changed

- `Formatter` rejects non-scalar arguments/replacement maps with
  `InvalidArgumentException` instead of failing inside `vsprintf`.
- Plural formulas compiled by `Translator` are validated as closures;
  results that are neither int nor bool raise `InvalidArgumentException`.
- `TranslatorFunctions` getters throw `LogicException` when accessed before
  `register()` instead of erroring on a null translator.
- `JsonLoader` rejects payloads that are not JSON objects; `ArrayLoader`
  rejects translation files that do not return an array.
- `PhpScanner`/`JsScanner` treat required gettext arguments that are not
  strings as invalid functions (previously only `null` was rejected).

### Fixed

- `Translation::create()` keeps the optional plural original instead of
  silently discarding the third argument.
- `PhpNodeVisitor` no longer crashes on first-class callable syntax
  (e.g. `__(...)`).
- `TranslatorInterface::dnpgettext()` now declares its missing `string`
  return type.
- `MoLoader` preserves the 1-based keys produced by `unpack()` when reading
  the offsets tables.

---

## [1.0.0] - 2026-07-16

### Added

- Initial release of the Gettext package.

### Changed

- None.

### Fixed

- None.

### Removed

- None.