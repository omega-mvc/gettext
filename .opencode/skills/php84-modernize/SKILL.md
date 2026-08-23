---
name: php84-modernize
description: >
  Step-by-step workflow for refactoring this gettext library to idiomatic PHP 8.4:
  deprecation fixes, native types, modern syntax. Use when the user asks to
  modernize, refactor, or port PHP code in this repo to 8.4.
---

# PHP 8.4 Modernize Workflow

Follow `REFACTORING.md` (repo root) — it defines the phases and exact targets.
This skill is the operating procedure.

## Rules

1. Work phase by phase (see `REFACTORING.md`). Never mix change categories in one commit:
   - Phase 0: QA tooling (phpcs/phpstan/php-cs-fixer configs) — do this first if configs are still missing.
   - Phase 1: implicit nullable params → explicit `?T` (PHP 8.4 deprecation).
   - Phase 2: native property/return/param types.
   - Phase 3: idioms (`match`, `str_starts_with`, promotion, `readonly`, typed constants).
   - Phase 4: optional 8.3/8.4 features — only with explicit user request.
2. After EVERY file edited: run the test suite (use the `run-php-tests` skill).
   A red suite means stop, fix, re-run before touching anything else.
3. Interfaces before implementations when adding types.
4. Never hand-edit `tests/Gettext/snapshots/*.php`; regenerate via `$forceCreate = true`
   in `MergeTest::assertSnapshot()` and commit the regenerated file.
5. Do not restyle `src/Languages/**` (vendored subpackage with own autoloader).
6. Do not add `"files": ["src/functions.php"]` to composer.json.

## Hard constraints

- No behavior changes: snapshots + ~2k tests must stay green byte-for-byte.
- Keep diffs minimal — this is a fork of php-gettext v5; reviewability matters.
- `Merge` constants are bitwise flags: keep them as class constants, never an enum.

## Verification

```bash
vendor/bin/phpunit                          # after every file
vendor/bin/phpunit tests/Gettext/MergeTest.php   # snapshot-sensitive areas
grep -rnE '\w+ \$\w+ = null' src --include='*.php' | grep -v '?'   # Phase 1 done?
```
