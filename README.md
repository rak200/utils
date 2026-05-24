# rak200/utils

General-purpose static utility helpers for PHP 8.4+.

PHP's standard library is broad but inconsistent in naming, type-strictness and multibyte handling. This package groups commonly-used helpers into a small set of final, stateless classes with short Laravel-style names.

## Requirements

- PHP 8.4+
- Extensions: `bcmath` (used by `Num` for `BcMath\Number` support) and `mbstring` (used by `Str`). Both are bundled with PHP and enabled by default on most distributions.
- No Composer runtime dependencies.

## Installation

```bash
composer require rak200/utils
```

## Classes

### Tier 1 — scalars and core structures

- **`Str`** — string operations (multibyte-safe by default): blank checks, case helpers, search, trim, split/join, padding, case conversions (`toCamelCase`/`toSnakeCase`/...).
- **`Arr`** — array operations: emptiness, first/last lookup, find, filter/map/reduce, flatten, groupBy, partition, chunk, unique, zip, range.
- **`Num`** — number operations: type checks, parsing (incl. `parseNumber` returning `BcMath\Number`), clamp/inRange, rounding, formatting, aggregates, sign, arithmetic (`pow`, `sqrt`, `floor`, `ceil`, `mod`). Aggregation and per-element methods accept and propagate `BcMath\Number` alongside `int|float`.
- **`Rand`** — randomness in one place: int/float/bytes/string, masked patterns, UUID v4 / UUID v7, ULID, nanoid. Alphabet constants: `Rand::NUM`, `Rand::HEX`, `Rand::ALPHA`, `Rand::ALNUM`.

### Tier 2 — contextual

- **`Regex`** — regex with consistent naming: `matches`, `match`/`matchAll`, `replace`/`replaceCallback`, `split`, `quote`.
- **`Hash`** — hashing and password handling: `md5`/`sha*`/`crc32`/`hmac`, constant-time `equals`, `password`/`verifyPassword`.
- **`Bit`** — bit manipulation: `set`/`unset`/`toggle`/`has`, popcount, leading/trailing zeros.
- **`File`** — filesystem helpers: read/write/append, exists/delete, path parts, mime type, size, line generator, temp file, copy/move.
- **`Json`** — JSON with implicit `JSON_THROW_ON_ERROR`: `encode`, `decode`, `isValid`.
- **`Base64`** — standard and URL-safe (no-padding) encode/decode.
- **`Dt`** — `DateTimeImmutable` helpers: construction, formatting (incl. `Dt::sql()`), arithmetic, comparison, period boundaries, diff in integer units.
- **`Url`** — URL parsing/building and query-string encode/decode: `parse`/`parseOrNull`/`build`, `encode`/`decode`, `encodeQuery`/`decodeQuery`, `isAbsolute`.
- **`Path`** — logical (no-disk) path manipulation: `join`, `normalize`, `relative`, `isAbsolute`, `basename`, `dirname`, `extension`, `filename`. Normalises to `/`; preserves Windows drive prefixes.

## Documentation

Per-method reference with runnable examples lives in [`docs/`](docs/README.md).

## Conventions

- All classes are `final` with a `private` constructor — pure static API, no instances.
- Strict types everywhere (`declare(strict_types=1)`).
- Multibyte-safe string operations by default.
- "Not found" convention: bare method (`Arr::first`, `Num::parseInt`, ...) throws `RuntimeException`; the `*OrNull` variant returns `?T`.

## Versioning

Follows [Semantic Versioning](https://semver.org). The `0.x` line is unstable while the API stabilises.

## Licence

MIT
