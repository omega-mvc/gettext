# AGENTS.md — Omega Gettext

PHP 8.4+ GNU gettext-based localization and translation library (`Omega\Gettext\`
namespace). Not an application — a Composer package in the Omega ecosystem,
adapted from `php-gettext/Gettext` and split as a dedicated repo.

## Commands

```bash
composer run phpcs          # phpcs (PSR-12, src/ + tests/)
composer run phpcbf         # phpcbf auto-fix
composer run phpstan        # PHPStan level 10 (src/ + tests/), part of the workflow
composer run test           # pest WITH coverage (needs Xdebug/PCOV)
composer run test-no-coverage # pest WITHOUT coverage (CI runs this)
vendor/bin/pest tests/Tests/Gettext/Loader/PoLoaderTest.php  # a single file
vendor/bin/pest --filter='...'                               # filter tests
```

There is NO `check`/`ci` composer script. CI is GitHub Actions
(`.github/workflows/`: tests.yml, coding-standard.yml, static-analysis.yml, plus
ci.yml wiring them), always on PHP 8.4 and always `--no-coverage`. Lint before
test (`composer run phpcbf` first); run `composer run phpstan` as part of the
verification.

## Code Style

- **PSR-12** (full ruleset in `phpcs.xml.dist`, no 120-col override here)
- 4-space indent, UTF-8, LF line endings
- `declare(strict_types=1);` + `namespace` in every source/test file
- **NO "Part of Omega" GPL docblock header** in this package — files start with
  a bare `<?php` + `declare(strict_types=1);` (unlike framework/serializable-closure)
- **PHP 8.4 property hooks** are used in `Translation.php` and `Translations.php`;
  they are excluded from `PSR2.Classes.PropertyDeclaration` because PHPCS 4.x
  cannot tokenize hook syntax — do not restyle them, never remove the hooks
- `src/Omega/Gettext/Languages/*` — vendored gettext/languages subpackage;
  **deprecation fixes only, never restyled**, and excluded from both lint and
  phpstan
- `tests/Tests/Gettext/assets/*` — scanner fixtures that assert exact line
  numbers and comment shapes; **NEVER reformat**
- `tests/data.php` + `tests/data.json` — machine-generated on every test run
  (see Testing); not lint targets
- Do NOT add an `<exclude-pattern>vendor/*</exclude-pattern>` to phpcs.xml.dist:
  this package is checked out nested inside another project's `vendor/` (the
  omega-mvc/omega starter), so a relative `vendor/*` pattern would match that
  enclosing path and exclude every file
- `PSR1.Files.SideEffects` excluded for `tests/bootstrap.php` and `tests/Tests/*`
  (procedural bootstrap and Pest files deliberately mix declarations and
  `it()`/dataset side effects)

## Structure

- `src/Omega/Gettext/` — domain model: `Translation`, `Translations`, `Headers`,
  `Comments`, `Flags`, `References`, `Merge`
- `Formatter` / `FormatterInterface` — plural-form rendering
- `Translator` / `TranslatorInterface` / `GettextTranslator` / `TranslatorFunctions`
  — runtime message translation + static registry
- `Loader/` — `PoLoader`, `StrictPoLoader`, `MoLoader`, `JsonLoader`,
  `ArrayLoader` (+ `LoaderInterface`)
- `Generator/` — `PoGenerator`, `MoGenerator`, `JsonGenerator`, `ArrayGenerator`
  (+ `GeneratorInterface`)
- `Scanner/` — PHP + JS source scanning to extract translatable strings:
  `PhpScanner`/`PhpFunctionsScanner` via **nikic/php-parser**
  (`PhpNodeVisitor`), `JsScanner`/`JsFunctionsScanner` via **mck89/peast**
  (`JsNodeVisitor`), plus `CodeScanner`, `Scanner`/`ScannerInterface`,
  `ParsedFunction`, `FunctionsHandlersTrait`
- `Languages/` — vendored CLDR data (`cldr-data/main/en-US/*.json`,
  `supplemental/plurals.json`), `Language`, `Category`, `CldrData`,
  `FormulaConverter`, `Exporter/*` (php/json/po/html/ruby/xml exporters)
- `bin/` — `export-plural-rules` + `import-cldr-data` (+ `.bat` shims); the test
  bootstrap invokes `export-plural-rules` to regenerate `tests/data.php`/`.json`

## Testing

- Pest 5 over PHPUnit. Namespace `Tests\Tests\Gettext\*` → `tests/Tests/`.
  `tests/Pest.php` does `pest()->extend(PHPUnit\Framework\TestCase::class)
  ->in('Tests/Gettext');`
- `phpunit.xml.dist`: bootstrap `tests/bootstrap.php` (loads autoload +
  `constants.php`, sets `error_reporting(E_ALL)`, then **executes
  `bin/export-plural-rules` to regenerate `tests/data.php` and
  `tests/data.json` on every run**), `cacheDirectory=cache/phpunit.cache`,
  `executionOrder=depends,defects`
- **`requireCoverageMetadata` + `beStrictAboutCoverageMetadata` are `true`**:
  every test FILE must declare coverage via Pest `covers(SomeClass::class)` at
  the top (e.g. `covers(Headers::class)`). Missing metadata fails the suite.
- `beStrictAboutOutputDuringTests=true`, `failOnPhpunitDeprecation=true`,
  `failOnRisky=true`, `failOnWarning=false`. Path coverage report →
  `cache/coverage-report/`
- **Known intentional warnings (do NOT "fix"):** the suite reports
  `2 warnings, 3418 passed (73694 assertions)` on `--no-coverage`. The two
  warnings come from `Scanner/CodeScannerTest::testScanFileThrowsWhenFileIsUnreadable`
  and `Loader/LoaderEdgesTest::testUnreadableFilesThrow`, which deliberately
  create an unreadable file to assert the expected exception. Details display and
  `failOnWarning` are disabled on purpose; the tests pass.
- `phpstan.neon.dist`: level 10, `bootstrapFiles: tests/constants.php`, excludes
  `src/Omega/Gettext/Languages` and `tests/Tests/Gettext/assets`

## Notes

- `composer.lock` is gitignored/untracked; `cache/` is gitignored
- Dependencies: PHP `^8.4`, nikic/php-parser `^5.6`, mck89/peast `^1.16`
- Verify every API claim against the real `src/`, never from memory