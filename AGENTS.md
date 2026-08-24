# AGENTS.md

PHP 8.4 library (`omega-mvc/gettext`) for i18n/l10n: load, edit, merge, and generate gettext translations (PO/MO/JSON/PHP array) and scan PHP/JS sources for translatable strings.

## Commands

```sh
composer test                                  # phpunit -> phpcs -> phpstan
XDEBUG_MODE=off vendor/bin/phpunit --no-coverage   # fast full suite (~25s)
vendor/bin/phpunit --no-coverage --filter testName # single test
vendor/bin/phpcs                               # PSR-12 lint (src + tests)
vendor/bin/phpcbf                              # auto-fix style
```

- Plain `vendor/bin/phpunit` generates an HTML **path coverage** report via Xdebug (`pathCoverage="true"` in phpunit.xml.dist) — takes many minutes even for a few tests. Always pass `--no-coverage` (+ `XDEBUG_MODE=off`).
- phpstan runs at **level 10** with hundreds of pre-existing violations and no baseline, so `composer test` always exits non-zero at the phpstan step. Use phpstan to check files you touched; don't attempt mass fixes.
- Only composer script is `test`. CONTRIBUTING.md mentions `composer check-style`, which does not exist.
- Every phpunit run executes `tests/bootstrap.php`, which shells out to `bin/export-plural-rules` and regenerates `tests/data.php` and `tests/data.json` — expect a few seconds of startup even for a single test.

## Do-not-touch zones

- `tests/Gettext/assets/*` — scanner fixtures whose exact line numbers and comment shapes are asserted by tests. Never reformat, restyle, or analyse them (already excluded from phpcs/phpstan).
- `src/Languages/*` — vendored `gettext/languages` subpackage with its own autoloader; deprecation fixes only, never restyled. Its data comes from CLDR via `bin/import-cldr-data`; plural-rule exports come from `bin/export-plural-rules`.
- `tests/data.php` / `tests/data.json` — machine-generated on every test run; never hand-edit.

## Layout

- `src/` is PSR-4 `Gettext\`: `Loader\` (Po/Mo/Json/Array + strict GNU `StrictPoLoader`), `Generator\` (same formats), `Scanner\` (PhpScanner uses nikic/php-parser, JsScanner uses mck89/peast), `Translator`/`GettextTranslator`, merge strategy constants in `Merge`.
- Tests are PSR-4 `Tests\` mirroring `src/` under `tests/Gettext/`; `MergeTest` compares output against committed files in `tests/Gettext/snapshots/`.

## Config notes

- Tracked configs are the `.dist` files (`phpunit.xml.dist`, `phpcs.xml.dist`, `phpstan.neon.dist`). Local un-suffixed copies are gitignored; keep them in sync if you must change behavior.
