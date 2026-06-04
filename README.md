# rak200/utils

[![CI](https://github.com/rak200/utils/actions/workflows/ci.yml/badge.svg)](https://github.com/rak200/utils/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/rak200/utils/graph/badge.svg)](https://codecov.io/gh/rak200/utils)
[![Latest tag](https://img.shields.io/github/v/tag/rak200/utils?sort=semver)](https://github.com/rak200/utils/tags)
[![PHP](https://img.shields.io/badge/php-8.4%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen?logo=php&logoColor=white)](https://phpstan.org/)
[![Code style](https://img.shields.io/badge/code%20style-PHP--CS--Fixer-blue?logo=php&logoColor=white)](.php-cs-fixer.dist.php)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![Open in GitHub Codespaces](https://img.shields.io/badge/Codespaces-open-blue?logo=github&logoColor=white)](https://codespaces.new/rak200/utils?quickstart=1)

General-purpose static utility helpers for PHP 8.4+.

PHP's standard library is broad but inconsistent in naming, type-strictness and multibyte handling. This package groups commonly-used helpers into a small set of final, stateless classes with short Laravel-style names.

## Requirements

- PHP 8.4+
- Extensions: `bcmath` (used by `Num` for `BcMath\Number` support) and `mbstring` (used by `Str`). Both are bundled with PHP and enabled by default on most distributions.
- No Composer runtime dependencies.

## Installation

Not published on Packagist — install straight from the GitHub repository as a Composer VCS package. Add the repository to the consuming project's `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/rak200/utils" }
    ]
}
```

then require it as usual:

```bash
composer require rak200/utils
```

## Classes

### Tier 1 — scalars and core structures

- **`Str`** — string operations (multibyte-safe by default): blank/`ctype` checks, case helpers (`title`/`toCamel`/`toSnake`/...), search, `before`/`after`, trim, split/join, `replace`/`replaceAt`, padding/wrapping (`wordWrap`/`wordCount`), `format`/`scan`, `levenshtein`/`similarity`.
- **`Arr`** — array operations: emptiness, count, first/last (value and key) lookup, find/search, filter/map/reduce, flatten, groupBy, partition, chunk, unique, reverse/slice/flip/combine, diff/intersect, countValues, append/prepend, immutable shift/pop (`[element, rest]`), fill, sort/sortKeys, zip, range.
- **`Num`** — number operations: type checks (incl. `isFinite`/`isNan`/`isInfinite`), parsing (incl. `parseNumber` returning `BcMath\Number`) and `toBase`, clamp/inRange, rounding, formatting, aggregates (`sum`/`product`/`avg`/...), sign, arithmetic (`pow`, `sqrt`, `floor`, `ceil`, `mod`). Aggregation and per-element methods accept and propagate `BcMath\Number` alongside `int|float`.
- **`Rand`** — randomness in one place: int/float/bytes/string, masked patterns, UUID v4 / UUID v7, ULID, nanoid. Alphabet constants: `Rand::NUM`, `Rand::HEX`, `Rand::ALPHA`, `Rand::ALNUM`.

### Tier 2 — contextual

- **`Regex`** — regex with consistent naming: `matches`, `match`/`matchAll`, `replace`/`replaceCallback`, `split`, `grep`, `quote`.
- **`Hash`** — hashing and password handling: `md5`/`sha*`/`crc32`/`hmac`, constant-time `equals`, `password`/`verify`.
- **`Bit`** — bit manipulation: `set`/`unset`/`toggle`/`has`, popcount, leading/trailing zeros, `rotateLeft`/`rotateRight`, base-2 string (`toStr`/`fromStr`).
- **`File`** — filesystem helpers: read/write/append, `touch`, exists/delete, path parts, `realpath`, mime type, size, line generator, CSV (`readCsv`/`writeCsv`), temp file, copy/move.
- **`Json`** — JSON with implicit `JSON_THROW_ON_ERROR`: `encode`, `decode`, `is`.
- **`Base64`** — standard and URL-safe (no-padding) encode/decode, plus the `is` predicate.
- **`Hex`** — hexadecimal encode/decode of binary strings (the byte-string counterpart to `Base64`), the `is` predicate, and `toBytes`/`fromBytes` for hex ↔ byte-value (`int`) lists.
- **`Dt`** — `DateTimeImmutable` helpers: construction, `isValid` calendar check, formatting (incl. `Dt::sql()`), arithmetic, comparison, period boundaries, diff in integer units.
- **`Url`** — URL parsing/building and query-string encode/decode: `parse`/`parseOrNull`/`build`, `encode`/`decode`, `encodeQuery`/`decodeQuery`, `is`/`isAbsolute`.
- **`Path`** — logical (no-disk) path manipulation: `join`, `normalize`, `relative`, `isAbsolute`, `basename`, `dirname`, `extension`, `filename`. Normalises to `/`; preserves Windows drive prefixes.
- **`Type`** — type introspection accepting `mixed`: type-name resolver (`of`), basic checks (`isStr`/`isBool`/`isInt`/`isFloat`/`isArray`/`isObject`/`isEnum`/`isCallable`/`isIterable`/`isNull`/`isScalar`/`isNumeric`/`isResource`), numeric strings (`isNumericStr`/`isIntLike` — both `@deprecated` since 2.1.0, removed in 3.0.0), class/trait checks (`isInstance`/`isA`/`isSubclass`/`isClassName`/`isInterfaceName`/`usesTrait`). Domain-mirroring predicates (`isStr`/`isInt`/`isFloat`/`isArray`/`isEnum`/`isNumeric`) are aliases of the canonical `is` on each domain class (`Str::is`, `Num::isInt`/`Num::isFloat`/`Num::is`, `Arr::is`, `Enum::is`). Topic-specific guards live with their tier-1 class: `Str::isBlank`/`Str::isNotBlank`, `Arr::isList`/`Arr::isAssoc`, `Num::isPositive`/`Num::isNegative`.
- **`Enum`** — class-level operations on enums: `names`/`values`, name lookup (`fromName`/`tryFromName` — the gap PHP leaves open), `random`, `[name => value]` map via `toArray`, plus the `is`/`isBacked` predicates.
- **`Filter`** — input sanitisation and lenient coercion of untrusted values: `escapeHtml`/`unescapeHtml`, `stripTags`, character whitelists (`digits`/`alpha`/`alnum`), `squish`/`stripControl`/`ascii`, `email`/`url`, and `mixed`-to-typed coercers with a default (`toStr`/`toInt`/`toFloat`/`toBool`).

## Documentation

Per-method reference with runnable examples lives in [`docs/`](docs/README.md).

## Conventions

- All classes are `final` with a `private` constructor — pure static API, no instances.
- Strict types everywhere (`declare(strict_types=1)`).
- Multibyte-safe string operations by default.
- "Not found" convention: bare method (`Arr::first`, `Num::parseInt`, ...) throws `RuntimeException`; the `*OrNull` variant returns `?T`.

## Versioning

Follows [Semantic Versioning](https://semver.org). The public API is stable from `1.0.0` onwards: breaking changes require a major version bump.

## Licence

MIT
