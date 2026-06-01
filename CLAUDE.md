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
- **Prefer the library's own helpers over native PHP functions** when a clean semantic equivalent exists (`Str::repeat` over `str_repeat`, `Str::trim` over `trim`, `Str::lower` over `mb_strtolower`, `Str::contains`/`substring`/`split`/`length`, `Arr::has` over `array_key_exists`, `Arr::is` over `is_array`, …) — many helpers exist precisely to fix native shortcomings. Keep the native only when there is no equivalent (`ord`, `fmod`, `iconv`, `htmlspecialchars`, case-insensitive `stripos`) **or** when the wrapper would break the method's contract — e.g. `Filter` sanitizers keep `preg_replace(...) ?? ''` rather than `Regex::replace`, because `Regex::replace` throws on the `null` that invalid UTF-8 yields under the `/u` modifier, which would violate `Filter`'s "never throws" guarantee.
- **Member order within a class:** constants → properties → constructor → non-magic methods → magic methods. Don't drop a constant beside its first use mid-class.

## Testing

PHPUnit 13 is configured via `phpunit.xml` with a single `Unit` suite. The strict flags `failOnWarning` and `failOnRisky` are enabled.

Run:
- `composer test` — runs the test suite
- `vendor/bin/phpunit tests/StrTest.php` — single file

Test classes mirror the source namespace (e.g. `Rak200\Utils\Str` → `Rak200\Utils\Tests\StrTest`). Test methods follow PSR-12 camelCase (e.g. `testReturnsBlankForWhitespaceOnly`), **not** snake_case.

Tests assert exact behaviour for the contract — return values, thrown exceptions, edge cases (empty input, multibyte input, boundary conditions). Time-sensitive tests (`Rand::uuidV7`, `Dt::now`) assert structural properties rather than exact values.

## Versioning

Follows [Semantic Versioning](https://semver.org). The public API is stable from `1.0.0` onwards: breaking changes require a major bump. The current version lives in `composer.json` and the README.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Update `CHANGELOG.md`: add a new `## [x.y.z] - YYYY-MM-DD` section and a comparison link at the bottom
3. Update the version reference in `README.md`
4. Update the `/docs/`
5. Commit, then `git tag x.y.z` and `git push origin master && git push origin x.y.z`.

## Roadmap

Planned additions and corrections. Released items live in `CHANGELOG.md`.

> The prefer-lib-over-native sweep shipped in 1.11.0 (conservative pass over `Num`, `Base64`, `Path`, `Filter`, `Str::slug`, `Dt`). Deliberately byte-level natives were retained — the `substr_replace`-based `Str::replaceFirst`/`replaceLast`, `Path` drive/segment slicing, and `Rand` cryptographic byte operations — per the *Conventions* caveat (keep the native when the wrapper would break the method's contract).

### Native-function gaps (candidate helpers)

Prefer-lib-over-native is ongoing — these native functions still lack a lib equivalent and fit the pure, static API. Method names are proposals (alternatives in the *Notes*).

| Class | Proposed method | Native PHP | Notes |
|---|---|---|---|
| `Str` | `title` | `ucwords` / `mb_convert_case` | Title-case (capitalize each word) |
| `Str` | `replaceAt(value, start, length, repl)` | `substr_replace` | today internal-only |
| `Str` | `replace` + `$ignoreCase` flag | `str_ireplace` | case-insensitive replace |
| `Str` | `before` / `after` | `strstr` / `strrchr` | slice around a delimiter |
| `Str` | `wordWrap` | `wordwrap` | wrap to a column width |
| `Str` | `wordCount` | `str_word_count` | count words |
| `Str` | `format(template, ...args)` | `sprintf` / `vsprintf` | printf-style formatting |
| `Str` | `scan(string, format)` | `sscanf` | parse a string by a format (inverse of `format`) |
| `Str` | `isDigits` / `isAlpha` / `isAlnum` | `ctype_*` | predicates (≠ the `Filter` sanitizers) |
| `Str` | `levenshtein` / `similarity` | `levenshtein` / `similar_text` | lower priority |
| `Arr` | `reverse` | `array_reverse` | |
| `Arr` | `slice` | `array_slice` | |
| `Arr` | `flip` | `array_flip` | |
| `Arr` | `combine` | `array_combine` | already used internally by `Str::translate` |
| `Arr` | `diff` / `intersect` | `array_diff` / `array_intersect` | by value (≠ key-based `pick`/`except`) |
| `Arr` | `search` (a.k.a. `keyOf`) | `array_search` | key of first matching value (≠ predicate-based `find`) |
| `Arr` | `countValues` | `array_count_values` | frequency map |
| `Arr` | `count` | `count` | only `isEmpty`/`isNotEmpty` today |
| `Arr` | `append` / `prepend` | `array_push` / `array_unshift` | immutable (return a new array) |
| `Arr` | `firstKey` / `lastKey` | `array_key_first` / `array_key_last` | `first`/`last` return the value |
| `Arr` | `sortKeys` | `ksort` / `krsort` | `sort`/`sortBy` order by value |
| `Arr` | `fill` | `array_fill` / `array_fill_keys` | |
| `Num` | `isNan` / `isInfinite` | `is_nan` / `is_infinite` | complement `isFinite` |
| `Num` | `product` | `array_product` | companion to `sum` |
| `Num` | `toBase(int, base)` | `base_convert` / `dechex` / `decoct` | inverse of `parseInt($s, $base)` |
| `Regex` | `grep` | `preg_grep` | filter an array by pattern |
| `Bit` | `rotateLeft` / `rotateRight` | — (no native) | bit-topic |
| `Dt` | `isValid` | `checkdate` | |
| `File` | `realpath` / `touch` / CSV | `realpath` / `touch` / `fgetcsv` / `fputcsv` | more specialised |

### Deferred

- **`Math`** — only worth splitting out if trigonometry, logarithms, number theory, or scientific constants are ever added. Until then, basic arithmetic (`pow`/`sqrt`/`floor`/`ceil`/`mod`) stays in `Num` to keep one class per topic. Trig / log / `exp` / `pi` / `deg2rad`, and number-theory helpers such as `gcd` / `lcm` (no native; `gcd` via Euclid, `lcm` derived from it), belong here, **not** in `Num`.
- **Mutable / pointer / in-place natives** — `array_pop` / `array_shift` / `array_splice`, in-place `sort`, `end` / `reset` / `next` / `current`, `settype` — break the pure / immutable contract; intentionally left unwrapped.
- **Global / impure / low-level** — `setlocale`, `ini_*`, raw stream / resource handling — out of scope for a pure helper library.
