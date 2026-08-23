# AGENTS.md

PHP ^8.4 library providing gettext-based i18n/translation support (`omega-mvc/gettext`, fork of php-gettext/Gettext v5). PSR-4: `Gettext\` → `src/`, `Tests\` → `tests/`. License GPL-3.0-or-later.

## Verification commands

- Full gate: `composer test` = `phpunit` + `phpcs` + `phpstan`.
- PHPUnit is the hard safety net: `vendor/bin/phpunit` (~2k tests, ~50k+ assertions), green after every change.
- Single file / single test: `vendor/bin/phpunit tests/Gettext/MergeTest.php` or add `--filter testName`.
- QA configs live at the root (`phpcs.xml.dist` = full PSR-12, `phpstan.neon.dist` = level 10, `.php-cs-fixer.dist.php`). If any is missing, recreate it — templates are in `.opencode/skills/run-php-tests/SKILL.md`. `src/Languages/` is excluded from phpcs/phpstan.
- While the 8.4 refactor is mid-flight: a large phpstan level-10 backlog (~743 errors, was 939 at baseline; fixtures excluded) is expected and must only shrink; `src/` and `tests/` are phpcs-clean.
- `composer cs-fix` runs php-cs-fixer (`@auto` rules).

## Unexpected working-tree changes

- If you find modified/new files you did NOT change in this session (e.g., the user ran `phpcbf`, `composer update`, or edited code outside OpenCode), stop and ASK before treating them as stale, reverting them, or building on stale baselines.
- On confirmation, re-verify current state (`vendor/bin/phpunit`, fresh phpcs/phpstan runs) but treat the user's changes as legitimate: never undo them silently. Instead update baselines, counts, and doc claims to match reality.

## Tooling notes

- `rg` (ripgrep) and `tig` are installed and available in PATH; prefer `rg` for content search and `tig` for git history inspection.

## Test quirks

- **Never reformat or edit** `tests/Gettext/assets/**` (scanner fixtures: tests assert exact line numbers and extracted-comment shapes) and `tests/data.php` (regenerated every run). Both are excluded from phpcs; if you find them modified, restore from git.
- `tests/bootstrap.php` regenerates `tests/data.php` and `tests/data.json` on every run by executing `bin/export-plural-rules`; if that subprocess fails, the whole suite aborts before any test runs.
- `tests/Gettext/MergeTest.php` uses snapshot files in `tests/Gettext/snapshots/*.php` (exported via brick/varexporter). To update a snapshot, pass `$forceCreate = true` to `assertSnapshot()` and commit the regenerated file.
- `phpunit.xml` is strict: `failOnWarning`, `failOnRisky`, `beStrictAboutOutputDuringTests` — any echo/output from tests or risky assertions fail the run.

## Structure notes

- `src/Languages/` is an embedded standalone sub-package (gettext/languages) with its **own** SPL autoloader (`src/Languages/autoloader.php`) — its classes are NOT autoloaded through Composer. The `bin/export-plural-rules` and `bin/import-cldr-data` CLI scripts require this autoloader directly. CLDR source data lives in `src/Languages/cldr-data/`.
- `src/functions.php` defines global helper functions (`__()`, `n__()`, etc.) but has no `"files"` entry in composer.json — nothing loads it automatically. Do NOT add that entry: global `__()` would collide with consumer projects.
- `composer validate` may warn about schema-level details (e.g., the hardcoded `version` field); the lockfile is in sync.

## PHP 8.4 refactoring project

`REFACTORING.md` (repo root) is the active plan for modernizing this codebase to idiomatic PHP 8.4. Read it before any refactor work. Key rules:

- One change category per commit; run `vendor/bin/phpunit` after every file touched.
- ~~Phase 1 target~~ DONE: all 4 implicit nullable params fixed (zero deprecations in suite). Next: Phase 2 native types.
- Never convert `Merge` bitflag constants to an enum (they're combined with `|`).
- Don't restyle `src/Languages/` beyond deprecation fixes — vendored subpackage.

## OpenCode assets

- Skills: `.opencode/skills/php84-modernize` (refactor workflow), `.opencode/skills/run-php-tests` (verification commands).
- Subagent: `.opencode/agents/php84-auditor.md` — read-only audit of legacy constructs/deprecations; use via `@php84-auditor`.
