# AGENTS.md — omega-mvc/gettext

i18n/l10n library for the Omega ecosystem (namespace `Omega\Gettext\`): scan, load, edit, and merge
translations across PO, MO, PHP array, JSON, and JS. Standalone Composer package with its own git repo
and CI; consumed by `omega-mvc/framework` and the starter app from `vendor/`.

## Commands

Run from this package directory. `composer` is the source of truth; the starter app's OpenCode config
denies `composer*`, so call the underlying binaries directly when blocked.

```bash
composer test              # XDEBUG_MODE=coverage php vendor/bin/pest
composer test-no-coverage  # XDEBUG_MODE=off php vendor/bin/pest --no-coverage
composer type-coverage     # vendor/bin/pest --type-coverage
composer phpcs             # XDEBUG_MODE=off php vendor/bin/phpcs
composer phpstan           # XDEBUG_MODE=off php vendor/bin/phpstan analyze -vvv
```

- No `lint`/`check`/`ci` scripts exist here — only the ones above.
- The suite is large (~3400 tests): prefer a single file or `--filter` while iterating.
- Always prefix `XDEBUG_MODE=off` when calling `phpstan`/`phpcs` directly, or their output gets noisy.

## Tests

- **Pest 5**. The namespace is `Tests\Tests\` → `tests/Tests` (the doubled segment is intentional;
  `autoload-dev` maps it). Bootstrap is `tests/bootstrap.php`; `tests/constants.php` exists only for PHPStan.
- Layout: `tests/Tests/Gettext/{Generator,Languages,Loader,Scanner}/`, plus `assets/` and `snapshots/`.
- `phpunit.xml.dist`: strict coverage metadata (`requireCoverageMetadata`, `beStrictAboutCoverageMetadata`),
  `failOnPhpunitDeprecation`, `failOnRisky`, `failOnWarning=false`, `pathCoverage="true"` → `cache/coverage-report`.
- Two warnings are **intentional** (unreadable-file tests): `Scanner\CodeScannerTest::testScanFileThrowsWhenFileIsUnreadable`
  and `Loader\LoaderEdgesTest::testUnreadableFilesThrow`. To reveal/re-enable them, set
  `displayDetailsOnTestsThatTriggerWarnings=true` and `failOnWarning=true` in `phpunit.xml.dist`.

## Lint / static-analysis quirks (do not "clean up")

- `src/Omega/Gettext/Languages` is a vendored `gettext/languages` subpackage: excluded from both phpstan
  and phpcs. Apply deprecation fixes only; never restyle it.
- `tests/Tests/Gettext/assets` are scanner fixtures whose exact line numbers and comment shapes are
  asserted: never reformat or analyse them. `tests/data.php` and `tests/data.json` are regenerated on every
  test run (machine output, never a lint target).
- PHPCS additionally disables `PSR2.Classes.PropertyDeclaration` for `Translation.php`/`Translations.php`
  (PHPCS 4 cannot tokenize PHP 8.4 property hooks) and `PSR1.Files.SideEffects` for `tests/bootstrap.php`
  and `tests/Tests/**`.
- PHPStan level 10 bootstraps `tests/constants.php` and excludes `src/Omega/Gettext/Languages` and
  `tests/Tests/Gettext/assets`.

## Tools

- `bin/export-plural-rules` and `bin/import-cldr-data` generate the CLDR-derived language/plural data.
- Changes here belong to this package's git repo: commit them here, or they are lost on `composer update`.
- CI: `.github/workflows/{tests,coding-standard,static-analysis}.yml`.
