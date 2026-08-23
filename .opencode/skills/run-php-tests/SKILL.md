---
name: run-php-tests
description: >
  How to verify changes in this gettext repo: ensure QA tooling configs exist,
  run PHPUnit / phpcs / phpstan, run a single test, and the strict PHPUnit
  rules that fail runs. Use before claiming any change is done.
---

# Running Tests & Verification

## Step 0 — Ensure QA config files exist (always do this first)

Check at repo root for: `phpcs.xml.dist`, `phpstan.neon.dist`,
`.php-cs-fixer.dist.php`. If any is missing, create it exactly as below,
then continue. Never skip verification just because configs were missing.

`phpstan.neon.dist` — MUST be level 10:

```neon
parameters:
    level: 10
    paths:
        - src
        - tests
    excludePaths:
        # Vendored gettext/languages subpackage: out of scope for static analysis.
        - src/Languages
```

`phpcs.xml.dist` — full PSR-12:

```xml
<?xml version="1.0"?>
<ruleset name="omega-mvc-gettext">
    <description>Full PSR-12 coding standard for omega-mvc/gettext.</description>

    <rule ref="PSR12"/>

    <file>src</file>
    <file>tests</file>

    <!-- Vendored gettext/languages subpackage: deprecation fixes only, never restyled. -->
    <exclude-pattern>src/Languages/*</exclude-pattern>
    <exclude-pattern>vendor/*</exclude-pattern>
</ruleset>
```

`.php-cs-fixer.dist.php` already exists (`@auto` rules); don't recreate it.

## Full verification

```bash
composer test          # phpunit + phpcs + phpstan
```

Current expectations while the PHP 8.4 refactor is in progress (see `REFACTORING.md`):

- `vendor/bin/phpunit` must be green after every change.
- `phpcs` reports PSR-12 violations on legacy files — many are auto-fixable
  with `vendor/bin/phpcbf`; review the diff afterwards.
- `phpstan` runs at **level 10** and currently reports a large legacy backlog.
  Your job: never increase the count for code you touch; decrease it when
  working on Phase 2/3 items.

## PHPUnit only (the hard safety net)

```bash
vendor/bin/phpunit
```

~2k tests / ~50k+ assertions, fast.

## Single file / single test

```bash
vendor/bin/phpunit tests/Gettext/MergeTest.php
vendor/bin/phpunit --filter testScanAndLoadStrategy
vendor/bin/phpunit tests/Gettext/Languages/RulesTest.php
```

## Strictness that fails runs

- Any output from tests (`echo`, `var_dump`) → failure (`beStrictAboutOutputDuringTests`).
- Risky tests, warnings, PHPUnit deprecations → failure (`failOnRisky`, `failOnWarning`, `failOnPhpunitDeprecation`).

## Gotchas

- The bootstrap regenerates `tests/data.php` + `tests/data.json` by exec'ing
  `bin/export-plural-rules` on every run; if it fails, NO tests run at all.
- Snapshot mismatches appear as assertSame failures in `MergeTest`.
  Fix code, don't edit snapshots; regenerate only if output legitimately
  changed (`$forceCreate = true`), then commit the regenerated snapshot.
