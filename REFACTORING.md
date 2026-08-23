# PHP 8.4 Refactoring Project

Complete modernization of `omega-mvc/gettext` to idiomatic PHP 8.4: zero deprecations,
native types everywhere, modern syntax — without behavior changes.

## Ground rules

1. **One change category per commit** (e.g., "fix implicit nullables", "type properties in Translation"). Never mix.
2. **Run `vendor/bin/phpunit` after every file touched.** The suite is fast (~2k tests) and is the only safety net until Phase 0 adds static analysis.
3. **No behavior changes.** Refactor only what tests prove equivalent; snapshots in `tests/Gettext/snapshots/` guard PO/MO/merge output byte-for-byte.
4. **Minimal diffs.** This is a fork of php-gettext/Gettext v5; keep upstream diff reviewable.
5. **Never hand-edit snapshot files** — regenerate with `$forceCreate = true` in `assertSnapshot()` and commit the result.
6. **Do NOT add a `"files"` autoload entry for `src/functions.php`.** Global `__()` etc. must stay opt-in to avoid collisions in consumer projects.
7. **`src/Languages/` is a vendored subpackage** (gettext/languages) with its own SPL autoloader: apply deprecation fixes only, do not restyle or restructure it.

## Phases

### Phase 0 — QA tooling (DONE, with follow-up)
- ~~Resync lockfile~~, ~~create configs~~: `phpcs.xml.dist` (full PSR-12), `phpstan.neon.dist` (**level 10**), `.php-cs-fixer.dist.php` (`@auto`) now exist at the root; `composer test` runs all three.
- Status after user's manual `phpcbf` pass: **`src/` is phpcs-clean (0 errors)**. Remaining: 36 line-length warnings (>120 chars), all under `tests/` — including generated/fixture files (`tests/data.php`, `tests/Gettext/assets/*`, `bootstrap.php`) where excluding LineLength may be preferable to reformatting.
- Follow-up: phpstan L10 backlog is the progress metric — it must only go down.
  - Baseline: 939 → 935 after Phase 1 → **767** after the docblock sweep
    (`missingType.iterableValue` + `missingType.generics` all fixed via precise
    `@param`/`@return`/`@var`/`@implements` annotations; the typed iterables also
    cleared ~109 cascading mixed-type errors).

### Phase 1 — Kill PHP 8.4 deprecations (DONE)
All implicit nullable parameters fixed to explicit `?T`:
- `src/Loader/JsonLoader.php` — `loadString()`, `loadArray()`
- `src/Scanner/JsFunctionsScanner.php` — constructor
- `src/Scanner/JsNodeVisitor.php` — constructor

Gate passed: `vendor/bin/phpunit` reports plain OK with **zero deprecations**;
`grep -rnE '\w+ \$\w+ = null' src | grep -v '?'` finds nothing.

### Phase 2 — Native types
~70 untyped properties and many missing return types across `src/` (docblock-only typing, PHP 5 era).
- Order: interfaces first (`LoaderInterface`, `GeneratorInterface`, `FormatterInterface`, `TranslatorInterface`), then implementations.
- Known return-type gaps: `getIterator()` → `\Iterator`, `jsonSerialize()`, `__debugInfo(): array`, node-visitor methods (`PhpNodeVisitor`, `JsNodeVisitor`), `Translation` accessors returning `self` inconsistently vs `static`.
- Type properties with the narrowest type that matches current usage (`?string`, `array`, collection classes). Initialize-before-use matters in the state-machine parsers `MoLoader` / `StrictPoLoader` — check property init order before adding non-nullable types.
- Adding types to `protected` props breaks subclasses that redeclare them untyped: acceptable (8.4-only, v1.0.0 major), but note it in the PR description.
- Gate: `vendor/bin/phpunit` green after each class; `phpstan` level not regressed.

### Phase 3 — Modernize idioms (8.0–8.3)
- `match` instead of value-dispatching `switch`: `PoLoader`, `StrictPoLoader`, `JsNodeVisitor`, `PhpNodeVisitor`, `Languages/*`.
- `str_starts_with()` / `str_contains()` instead of `strpos()` comparisons (`src/Languages/autoloader.php`, scanner prefix checks).
- Constructor promotion for simple holders: `ParsedFunction`, `JsNodeVisitor`, `PhpFunctionsScanner`, `Formatter`.
- `readonly` only for props never reassigned anywhere (watch `Translation::__clone()` and merge code that mutates clones).
- `Merge` constants are bitwise flags used with `|` and checked via bitmask: keep as `final` class constants (optionally typed `public const int`). **Do not convert to an enum** — enums don't support bitwise OR.
- Arrow functions for short closures in generators/loaders where it reads better; skip cosmetic churn elsewhere.

### Phase 4 — Opportunistic 8.3/8.4 features (optional, separate PRs)
- Property hooks only if they replace internal boilerplate without removing public `getX()`/`withX()` API — default answer is "no" for `Translation`/`Translations`.
- `new Foo()->bar()` chaining without parentheses, `#[\Override]` on interface implementations, typed constants.
- Skip anything speculative (no lazy objects, no async).

### Phase 5 — Docs & final gates
- ~~Fix `README.md` install command~~ (done: `omega-mvc/gettext`) and author homepage in composer.json.
- ~~Fix `CONTRIBUTING.md`: references nonexistent `composer check-style` script~~ (real gate: `test`; fixer: `cs-fix`).
- Full gate: `composer test` green (phpunit + phpcs + phpstan) on PHP 8.4.

## Gotchas

- `tests/bootstrap.php` re-runs `bin/export-plural-rules` on every suite run and aborts everything if that subprocess fails; it also requires `src/Languages/autoloader.php` manually.
- `phpunit.xml` is strict: any output from tests (`echo`, `var_dump`) or risky assertions fails the whole run; `failOnWarning` too.
- Snapshot format is brick/varexporter PHP arrays — deterministic, committed under version control.
- `Gettext\Languages\*` classes bypass Composer autoloading entirely.
- PHPUnit config has `requireCoverageMetadata=true`: coverage runs require coverage metadata — on PHPUnit 13 that means **attributes** (e.g. `#[CoversClass(Foo::class)]`), not `@covers` docblock annotations (none exist in the suite today). Plain test runs don't need it.
- Coverage driver: Xdebug is installed but **not configured**; set up `xdebug.mode=coverage` (or install pcov) before the coverage sprint.
