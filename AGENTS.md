# AGENTS.md

## What this is

PHP 8.4+ library for i18n/l10n: import, export, edit, and merge translations across PO, MO, PHP arrays, JSON, and JavaScript source. Part of the `omega-mvc` ecosystem.

## Commands

```sh
composer install          # install deps

# verification (run in this order):
composer phpcs            # PSR-12 lint (PHPCS)
composer phpstan          # static analysis, level 10
composer test-no-coverage # PHPUnit fast
composer test             # PHPUnit + Xdebug path coverage (HTML report in cache/)

# single test / filtered run:
composer test-no-coverage -- --filter MergeTest

# auto-fix style:
composer phpcbf
```

Composer scripts set `XDEBUG_MODE` for you (`off` everywhere; `coverage` for `test`). Running `vendor/bin/phpunit` directly fails unless you pass `--no-coverage` or export `XDEBUG_MODE=coverage` with Xdebug installed — `phpunit.xml.dist` requests a path-coverage report that requires it.

## Test bootstrap

`tests/bootstrap.php` runs `bin/export-plural-rules` to regenerate `tests/data.php` and `tests/data.json` before every test run. These files are gitignored artifacts — do not hand-edit them. The PHPCS and PHPStan configs exclude them from analysis.

## Excluded from lint/analysis

- `src/Omega/Gettext/Languages/` — vendored CLDR subpackage. Deprecation fixes only; never restyle or run PHPStan on it.
- `tests/Tests/Gettext/assets/` — scanner test fixtures. Line numbers and comment shapes are asserted by tests. Never reformat.
- `tests/data.php`, `tests/data.json` — machine-generated, excluded from PHPCS.

## Snapshots

Merge tests compare against committed files in `tests/Tests/Gettext/snapshots/`. A missing snapshot is auto-created from current behavior on first run — review it and commit it as part of your change.

## Coverage

- With coverage enabled (`composer test`), PHPUnit is strict: any test executing code not declared via `#[CoversClass]` becomes **risky** and silently loses its coverage. Declare every class the test touches (including base classes such as `Loader`, `Scanner`, `CodeScanner`); traits cannot be targets — declare the class that uses them instead.
- Known unreachable defensive lines (do not chase): `Translator.php:456,472-475`, `StrictPoLoader.php:268,283,296,409,575`, `PoLoader.php:179`, `ArrayGenerator.php:209,216`, `PhpNodeVisitor.php:199,234`, `JsFunctionsScanner.php:45`, `GettextTranslator.php:44`.

## Namespace structure

- `Omega\Gettext\` → `src/Omega/Gettext/` (PSR-4)
- `Tests\Tests\` → `tests/Tests/` (PSR-4, dev only)

Key subdirectories under `src/Omega/Gettext/`:
- `Loader/` — readers (PoLoader, MoLoader, JsonLoader, ArrayLoader, StrictPoLoader)
- `Generator/` — writers (PoGenerator, MoGenerator, JsonGenerator, ArrayGenerator)
- `Scanner/` — extract translatable strings from PHP/JS source (uses `nikic/php-parser` and `mck89/peast`)
- `Languages/` — vendored CLDR plural rules, excluded from static analysis

## Strictness

- PHPStan runs at **level 10** (maximum) on both `src` and `tests`. Expect zero tolerance for type issues.
- PHPUnit is strict: `failOnRisky`, `failOnWarning`, fails on deprecations, and fails if a test emits output. Tests need coverage metadata (`#[CoversClass]` attributes) or they fail.
- PHPCS enforces **PSR-12**.

## Config files

Tracked configs are `phpunit.xml.dist`, `phpcs.xml.dist`, `phpstan.neon.dist`. Untracked local copies (`phpunit.xml`, `phpcs.xml`, `phpstan.neon`) may exist and shadow them — always edit the `.dist` files.

## CI

GitHub Actions runs three checks on push to `main` and PRs: PHPCS, PHPStan, PHPUnit (PHP 8.4). Each is in `.github/workflows/` as separate callable workflows invoked by `ci.yml`.
