# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**rak200/utils** is a standalone PHP 8.4+ library of static utility helpers. It groups commonly-used helpers into a small set of `final` classes with short Laravel-style names, replacing scattered global functions with a discoverable, type-strict API. No runtime dependencies.

Dev dependencies:
- `phpunit/phpunit ^13.1` for the test suite
- `phpstan/phpstan ^2.1` for static analysis (level max)
- `friendsofphp/php-cs-fixer ^3.75` for code style (see [Code style](#code-style))

## Structure

```
utils/
├── src/
│   ├── Str.php       # strings (Tier 1)
│   ├── Arr.php       # arrays (Tier 1)
│   ├── Iter.php      # lazy iterables / generators (Tier 1)
│   ├── Num.php       # numbers (Tier 1)
│   ├── Rand.php      # randomness, uuid, ulid, nanoid (Tier 1)
│   ├── Regex.php     # regular expressions (Tier 2)
│   ├── Hash.php      # hashing + passwords (Tier 2)
│   ├── Bit.php       # bit manipulation (Tier 2)
│   ├── File.php      # filesystem (Tier 2)
│   ├── Json.php      # JSON (Tier 2)
│   ├── Base64.php    # Base64 encode/decode (Tier 2)
│   ├── Hex.php       # hexadecimal encode/decode of binary strings (Tier 2)
│   ├── Dt.php        # DateTimeImmutable helpers (Tier 2)
│   ├── Url.php       # URL parse/build, query encode/decode (Tier 2)
│   ├── Path.php      # logical path manipulation, no disk access (Tier 2)
│   ├── Type.php      # type-checking predicates accepting mixed (Tier 2)
│   ├── Enum.php      # class-level enum operations (Tier 2)
│   └── Filter.php    # input sanitisation + mixed-to-typed coercion (Tier 2)
└── tests/            # mirrors src/ layout (one *Test.php per class)
```

Production classes live under `Rak200\Utils\` (PSR-4 from `src/`); test classes live under `Rak200\Utils\Tests\` (PSR-4 from `tests/`, dev-only).

## Conventions

- Every class is `final` with a `private` constructor and only `public static` methods — pure functions, no instances, no state.
- `declare(strict_types=1)` at the top of every file.
- **Documentation is mandatory.** Every class carries a PHPDoc class summary (one short paragraph) plus the `@author rak200 <rak.ricardo@windowslive.com>` tag. Every `public` method carries a PHPDoc that states what it does — `@param`/`@return`/`@throws` tags are added only when they convey information beyond the type signature (units, semantics, edge-case behaviour, exception condition). Private helpers are documented only when the implementation is non-obvious. Each per-class doc under `docs/<class>.md` must list every public method in its `## Contents` TOC and include at least one runnable example per method (`bare` + `*OrNull` variants documented together).
- String operations default to multibyte-safe (`mb_*` family).
- "Not found" convention: bare method throws `RuntimeException`; the `*OrNull` variant returns `?T`. Keep the two variants consistent in every class.
- Cryptographically-secure randomness only in `Rand` (`random_int`, `random_bytes`). Never `rand()` / `mt_rand()`.
- `Dt` works strictly with `DateTimeImmutable` — no mutable `DateTime`.
- Public API takes/returns native PHP types where possible; no custom wrapper objects.
- **Efficiency is never the goal; correctness comes first and benchmarks are never chased — but needless work is still avoided.** No micro-optimising for raw speed's sake. Two costs, though, are worth removing *even when the tighter form reads worse*: a redundant second pass over the same data, and an intermediate array that a single pass would avoid — prefer a single pass over "build an intermediate array, then fold it," accepting the loss of readability. The other sanctioned lever is *laziness*: offer generator-returning / streaming variants for paths that may handle large or unbounded data (as `File::lines` and the `Iter` class do).
- **Prefer the library's own helpers over native PHP functions** when a clean semantic equivalent exists (`Str::repeat` over `str_repeat`, `Str::trim` over `trim`, `Str::lower` over `mb_strtolower`, `Str::contains`/`sub`/`split`/`len`, `Arr::has` over `array_key_exists`, `Arr::is` over `is_array`, …) — many helpers exist precisely to fix native shortcomings. Keep the native only when **(a)** there is no equivalent (`ord`, `fmod`, `iconv`, `htmlspecialchars`, case-insensitive `stripos`); **(b)** the wrapper would break the method's contract — e.g. `Filter` sanitizers keep `preg_replace(...) ?? ''` rather than `Regex::replace`, because `Regex::replace` throws on the `null` that invalid UTF-8 yields under the `/u` modifier, violating `Filter`'s "never throws" guarantee; or **(c)** the method *is* the wrapper for that native — `Str::byteLen` is built on `strlen`, `Str::len` on `mb_strlen`, `Str::repeat` on `str_repeat`, so it cannot replace the native it is made of.
- **Every native function a class still calls is imported via `use function`.** The `use function` block at the top of each file is the auditable inventory of the natives deliberately kept under the rule above — reviewable at a glance, and (with first-class callables, below) the only place a native name appears. Functions only; constants stay unqualified.
- **Pass callables with first-class syntax, never as strings.** When handing a function or method to be invoked (callbacks for `array_map` / `usort` / `array_filter` / …), use `func(...)` / `self::method(...)` — not `'func'` or `['Class', 'method']`. This keeps the reference statically checked, IDE-navigable, and bound by `use function`. Does **not** apply to APIs that take a function *name as data* (`function_exists`, `is_callable`) or when the symbol is computed at runtime.
- **Naming — the shortest name that stays unambiguous and discoverable** (brevity is the tie-breaker, not the goal). Apply in order of precedence: **(1)** existing conventions/families are invariant and outrank brevity — the `*OrNull` suffix, the `is*` predicate prefix, and verb families (`parse*` / `to*`) are never shortened away; **(2)** prefer a consolidated cross-language synonym to the PHP-specific name (`join` over `implode`, `slice` over `array_slice`), and keep a word whole when the full form *is* the consolidated one (`toString` / `toInt` / `toBool`, not `toStr`); **(3)** prefer a widely-recognised abbreviation (`Str` / `Num` / `Arr` / `Dt` / `Id` / `Url` / `Dir` / `Tmp` — `fooBarStr` over `fooBarString`), never an obscure one (`fmt` / `cnt` / `lvl`); **(4)** drop a word the qualified name already carries (`File::mimeType` → `File::mime`; a hypothetical `Dt::checkDate` → `Dt::check`) — *but rule 1 wins*, so a boolean check keeps its `is*` form (the calendar validator is `Dt::isValid`, not `Dt::check`), and a type suffix that disambiguates a `mixed`-accepting guard from a typed predicate stays (`Str::isNonEmptyStr` vs `Str::isEmpty(string)`; `Arr::isNonEmptyArray` vs `Arr::isNotEmpty(array)`). API-breaking renames land only on a major bump and ship a `@deprecated` alias kept for one major cycle (as `toCamelCase`→`toCamel` and `isNumeric`→`is` already do).
- **Member order within a class:** constants → properties → constructor → non-magic methods → magic methods. Don't drop a constant beside its first use mid-class. (Enforced by php-cs-fixer's `ordered_class_elements`, configured magic-last — see [Code style](#code-style).)

## Code style

Formatting is enforced by **PHP-CS-Fixer** (`friendsofphp/php-cs-fixer`) with the `@PhpCsFixer` ruleset — the strictest preset — over `src/` and `tests/`, configured in [.php-cs-fixer.dist.php](.php-cs-fixer.dist.php). Run:

- `composer cs-check` — report violations (`--dry-run --diff`); CI runs this on the PHP 8.4 job and fails on any deviation
- `composer cs-fix` — apply fixes in place

The preset is used as-is except for a few **deliberate, load-bearing overrides** that stop it fighting the conventions above (chiefly the `use function` inventory and the member order). Each override carries its rationale inline in [.php-cs-fixer.dist.php](.php-cs-fixer.dist.php) — don't drop one without reading why it's there.

Run PHP-CS-Fixer on PHP 8.4 to match the project floor; on a newer runtime it prints a version warning (and needs `PHP_CS_FIXER_IGNORE_ENV=1`), harmless but noisy.

## Testing

PHPUnit 13 is configured via `phpunit.xml` with a single `Unit` suite. The strict flags `failOnWarning` and `failOnRisky` are enabled.

Run:
- `composer test` — runs the test suite
- `vendor/bin/phpunit tests/StrTest.php` — single file

Test classes mirror the source namespace (e.g. `Rak200\Utils\Str` → `Rak200\Utils\Tests\StrTest`). Test methods follow PSR-12 camelCase (e.g. `testReturnsBlankForWhitespaceOnly`), **not** snake_case.

Tests assert exact behaviour for the contract — return values, thrown exceptions, edge cases (empty input, multibyte input, boundary conditions). Time-sensitive tests (`Rand::uuidV7`, `Dt::now`) assert structural properties rather than exact values.

## Versioning

Follows [Semantic Versioning](https://semver.org). The public API is stable from `1.0.0` onwards: breaking changes require a major bump. The current version lives in `composer.json`.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Update `CHANGELOG.md`: add a new `## [x.y.z] - YYYY-MM-DD` section and a comparison link at the bottom
3. README needs no version edit — its GitHub-tag version badge updates automatically once the new tag is pushed
4. Update the `/docs/`
5. **Prune the Roadmap** — *remove* every entry delivered in this release from the `## Roadmap` section below (do not merely annotate it as delivered); the `CHANGELOG.md` is the historical record of what shipped, the Roadmap is only what's still pending.
6. Commit, then `git tag x.y.z` and `git push origin master && git push origin x.y.z`.

### README badges

Badges must stay honest — each one is a claim the repo has to back up. Three categories by how that honesty is maintained:

- **Live (self-honest — never hand-edit):** `CI`, `Coverage`, `Latest tag` — driven by GitHub Actions / Codecov / the GitHub tag API, so they can't drift.
- **Static, mirror a source of truth (update the badge whenever that source changes):**
  - PHP-version badge ⇄ `composer.json` `"php"` constraint
  - PHPStan-level badge ⇄ `phpstan.neon.dist` `level`
  - License badge ⇄ `composer.json` `"license"` + `LICENSE`
- **Static, stable claims (revisit only if the practice itself changes):** Code style (PHP-CS-Fixer), SemVer, Keep a Changelog.

Before adding a badge, prefer one that is *verifiable* — backed by a file in the repo or a live service — over a vanity/activity metric.

## Roadmap

Planned additions and corrections. Released items live in `CHANGELOG.md`.

### Test coverage (pragmatic; literal 100% is a deferred decision)

The suite targets **pragmatic** line coverage — currently **~97.5%** — closing every reasonably-testable branch, error/`@throws` paths included (an invalid input that makes the native emit a warning is tested with `@` suppressing that expected warning, the same idiom `Regex::is` uses). The lines left uncovered are deliberate:

- **Unreachable defensive code** — the 17 private `__construct() {}` of the static-only classes, and the post-loop `return self::BITS;` in `Bit::leadingZeros`/`trailingZeros` (a non-zero value always finds a set bit inside the loop).
- **Branches that only fire when a native call fails despite valid preconditions** — `File::read`/`delete`/`size`/`lines`/`temp`/`list`/`readCsv`/`writeCsv` and `File::mime`'s finfo branches (the file exists / handle is valid but `file_get_contents`/`unlink`/`filesize`/`fopen`/`tempnam`/`glob`/`fputcsv`/`finfo_*` returns `false`), `Dt::fromEpochMs`'s `createFromFormat` failure (the format string is built from ints, so it always parses), and `Filter::ascii`'s iconv-unavailable fallback (iconv is present).

Forcing these to **literal 100%** would need fragile, platform-specific setups (read-only dirs via `chmod`/`icacls`, invalidated handles, mocking `finfo`) that contradict the suite's clean style. **Deferred:** decide later whether to pursue literal 100%, and how.

### Contingent (additive — ships in any minor when there's demand)

- **`Math`** — only worth splitting out if trigonometry, logarithms, number theory, or scientific constants are ever added. Until then, basic arithmetic (`pow`/`sqrt`/`floor`/`ceil`/`mod`) stays in `Num` to keep one class per topic. Trig / log / `exp` / `pi` / `deg2rad`, and number-theory helpers such as `gcd` / `lcm` (no native; `gcd` via Euclid, `lcm` derived from it), belong here, **not** in `Num`. Purely additive — there's no point creating an empty class, so it lands in a minor release when real demand appears.
- **Exception hierarchy (concept review).** Today all 100 throw-sites use the bare `RuntimeException`, so a caller cannot catch "a failure from *this* library" distinctly, nor tell failure kinds apart. Introduce a marker interface (e.g. `Rak200\Utils\Exception\UtilsException extends Throwable`) implemented by a small set of concrete classes that **extend `RuntimeException`** — so every existing `catch (RuntimeException)` keeps working and the change ships in a minor (BC-safe). Minimal viable set: `NotFoundException` (the bare-method throw of the "not found" convention) and `InvalidInputException` (malformed input — bad JSON/regex/base). Granularity is the open decision: the marker interface alone already buys lib-scoped `catch`, so add concrete subtypes only where a real call-site needs to branch on the kind. Messages are unchanged — this is about *type*, not text. See the dedicated reasoning in chat history if reviving.

### Planned for the next major (3.0.0)

- **`Arr::has` / `get` / `getOrNull` become pure dot-path.** They ship in 2.2.0 with a *literal-first* fallback (the literal key is checked before dot-traversal) so keys containing a dot keep resolving — BC-safe. In 3.0.0 that fallback is removed and they become pure `'a.b.c'` lookups; `Arr::hasKey` (added in 2.2.0) is the stable literal-key check to migrate to.

### Out of scope (by design — won't do)

Deliberate exclusions, not pending work: these contradict the library's pure / immutable / stateless contract. Impure concerns belong in a separate sibling library (e.g. `rak200/http-input`), not here.

- **Mutable / pointer / in-place natives** — `array_pop` / `array_shift` / `array_splice`, in-place `sort`, `end` / `reset` / `next` / `current`, `settype` — break the pure / immutable contract. The useful cases already have immutable equivalents: `array_push` / `array_unshift` → `Arr::append` / `prepend`, in-place `sort` → `Arr::sort` / `sortBy` / `sortKeys`, `settype` → `Filter::to*`, `end` / `reset` / `current` → `Arr::first` / `last`. The *mutating* `array_shift` / `array_pop` stay out, but their pure `[element, rest]` form ships as `Arr::shift` / `Arr::pop` (+ `*OrNull`) — both halves returned without touching the input; for a single half use `Arr::first` / `last` (element) or `Arr::slice` (remainder). See [docs/arr.md](docs/arr.md#dropping-the-first--last-element-array_shift--array_pop).
- **Global / impure / low-level** — `setlocale`, `ini_*`, raw stream / resource handling — out of scope for a pure helper library.
