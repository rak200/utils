# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**rak200/utils** is a standalone PHP 8.4+ library of static utility helpers. It groups commonly-used helpers into a small set of `final` classes with short Laravel-style names, replacing scattered global functions with a discoverable, type-strict API. No runtime dependencies.

Dev dependencies:
- `phpunit/phpunit ^13.1` for the test suite

## Structure

```
utils/
├── src/
│   ├── Str.php       # strings (Tier 1)
│   ├── Arr.php       # arrays (Tier 1)
│   ├── Num.php       # numbers (Tier 1)
│   ├── Rand.php      # randomness, uuid, ulid, nanoid (Tier 1)
│   ├── Regex.php     # regular expressions (Tier 2)
│   ├── Hash.php      # hashing + passwords (Tier 2)
│   ├── Bit.php       # bit manipulation (Tier 2)
│   ├── File.php      # filesystem (Tier 2)
│   ├── Json.php      # JSON (Tier 2)
│   ├── Base64.php    # Base64 encode/decode (Tier 2)
│   └── Dt.php        # DateTimeImmutable helpers (Tier 2)
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

## Testing

PHPUnit 13 is configured via `phpunit.xml` with a single `Unit` suite. The strict flags `failOnWarning` and `failOnRisky` are enabled.

Run:
- `composer test` — runs the test suite
- `vendor/bin/phpunit tests/StrTest.php` — single file

Test classes mirror the source namespace (e.g. `Rak200\Utils\Str` → `Rak200\Utils\Tests\StrTest`). Test methods follow PSR-12 camelCase (e.g. `testReturnsBlankForWhitespaceOnly`), **not** snake_case.

Tests assert exact behaviour for the contract — return values, thrown exceptions, edge cases (empty input, multibyte input, boundary conditions). Time-sensitive tests (`Rand::uuidV7`, `Dt::now`) assert structural properties rather than exact values.

## Versioning

Follows [Semantic Versioning](https://semver.org). The `0.x` line is **unstable** while the API stabilises; the current version lives in `composer.json` and the README.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Update `CHANGELOG.md`: add a new `## [x.y.z] - YYYY-MM-DD` section and a comparison link at the bottom
3. Update the version reference in `README.md`
4. Update the `/docs/`
5. Commit, then `git tag x.y.z` and `git push origin master && git push origin x.y.z`.

## Roadmap

Planned additions and corrections. While on the `0.x` line, breaking changes are allowed. Released items live in `CHANGELOG.md`.

### Deferred

- **`Math`** — only worth splitting out if trigonometry, logarithms, or scientific constants are ever added. Until then, basic arithmetic (`pow`/`sqrt`/`floor`/`ceil`/`mod`) stays in `Num` to keep one class per topic.
