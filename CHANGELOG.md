# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-05-26

### Added

- **`Type`** — new tier-2 class with type-checking predicates that accept `mixed`, intended as guards on values whose shape is not yet known. Methods: basic checks (`isStr`/`isBool`/`isInt`/`isFloat`/`isArray`/`isObject`/`isCallable`/`isIterable`/`isNull`/`isScalar`/`isNumeric`/`isResource`), numeric strings (`isNumericStr`/`isIntLike`), class checks (`isInstanceOf`/`isClassName`/`isInterfaceName`/`isA` — the last being a shortcut for `is_a()` with `$allow_string = true`, accepting both objects and class-name strings).
- **`Str`** — `isNonEmptyStr(mixed)`: true for any string with at least one character (whitespace counts).
- **`Arr`** — `isList(mixed)`, `isAssoc(mixed)`, `isNonEmptyArray(mixed)`. Topic-typed guards complementing the existing `isEmpty(array)`/`isNotEmpty(array)`.
- **`Num`** — `isPositiveInt(mixed)`, `isNegativeInt(mixed)`, `isNonNegativeInt(mixed)`. Strict — reject floats and numeric strings.

### Changed

- **`Str::isBlank` / `Str::isNotBlank`** — parameter widened from `string` to `mixed` so the methods double as type guards. Backwards-compatible for existing string callers (same return value for every string input). `isBlank(null)` now returns `true`; non-string, non-null values return `false`. `isNotBlank` returns `true` only for strings with at least one non-whitespace character (non-strings → `false`).

## [1.0.0] - 2026-05-25

First stable release. The public API is now covered by SemVer: breaking changes require a major version bump.

### Changed

- **`Rand::uuid()` renamed to `Rand::uuidV4()`** *(breaking)* — `uuid()` no longer exists; callers must pick `uuidV4()` (random) or `uuidV7()` (time-ordered) explicitly. Reason: a bare `uuid()` name was ambiguous about which variant was being generated, and silently changing what it returned in a later release would have been a foot-gun. `uuidV7()` is generally the better default for new code (sortable, indexes better).

## [0.3.0] - 2026-05-24

### Added

- **`Url`** — URL parsing/building (`parse`/`parseOrNull`/`build`), RFC 3986 percent-encoding (`encode`/`decode`), query-string encode/decode (`encodeQuery`/`decodeQuery`), and `isAbsolute`. `Url::build(Url::parse($u))` round-trips.
- **`Path`** — logical (no-disk) path manipulation: `join`, `normalize`, `relative`, `isAbsolute`, plus `basename`, `dirname`, `extension`, `filename`. Accepts `/` or `\` on input, always emits `/`. Windows drive prefixes (`C:`) are preserved.
- **PHPDoc** — added on the few remaining private helpers (`Rand::pickFromAlphabet`, `Rand::formatUuid`, `Bit::checkBitIndex`, `Dt::immutable`).

### Changed

- **`use function` imports** — every `src/` file now imports the native PHP functions it calls via inline `use function` statements (compile-time resolution; enables the same special-function optimisations as global-namespace calls).

## [0.2.0] - 2026-05-24

### Added

- **`Num`** — arithmetic helpers: `pow`, `sqrt`, `floor($v, $precision = 0)`, `ceil($v, $precision = 0)`, `mod` (truncated, follows PHP `%` semantics).
- **`Num`** — `BcMath\Number` (PHP 8.4) support: `isNumeric` recognises instances; `sum`, `avg`, `min`, `max`, `abs`, `sign`, `clamp`, `inRange`, `pow`, `sqrt`, `floor`, `ceil`, `mod`, `round`, `format` accept and propagate `BcMath\Number` alongside `int|float` (no narrowing to float). New `parseNumber` / `parseNumberOrNull` for arbitrary-precision parsing.
- **`Str`** — `substring`, `indexOf`, `lastIndexOf`, `count` (substring count), `truncate`, `replaceFirst`, `replaceLast`, `slug`.
- **`Arr`** — `contains`, `pluck`, `keyBy`, `sort`, `sortBy`, `merge`, `pick`, `except`.
- **`Rand`** — `bool`, `choice`, `shuffle` (Fisher-Yates with CSPRNG).
- **`Dt`** — `isWeekend`, `isWeekday`, `dayOfWeek`, `dayOfYear`, `weekOfYear`.
- **`File`** — `isFile`, `isDirectory`, `mkdir`, `list`.
- **Docs** — `## Contents` TOC added to `docs/base64.md`; "← Reference" back-link added to the top of every class doc, and a "← rak200/utils" link at the top of `docs/README.md`.

### Changed

- **`Arr::reduce`** *(breaking)* — callback signature changed to `(carry, value, key)` for consistency with `map`/`filter`/`find`/`groupBy`/`partition`. Existing 2-arg callbacks remain compatible (PHP silently drops extra arguments).
- **`Str::splitWords`** — case-transition regex switched to unicode classes (`\p{Ll}` / `\p{Nd}` / `\p{Lu}`) so case-conversion helpers handle non-ASCII letters (e.g. `Str::toSnakeCase('óÁgua') === 'ó_água'`).
- **`Dt::isEqual`** — now compares the absolute UTC instant (microsecond precision) rather than relying on `==` structural comparison; two times in different timezones that point at the same instant compare equal.
- **`Str::contains`** — removed redundant `$needle === ''` branch (PHP 8.0+ `str_contains` already returns `true` for an empty needle).
- **`composer.json`** — declared `ext-bcmath` and `ext-mbstring` requirements.

## [0.1.2] - 2026-05-23

### Added

- `docs/` folder: one markdown file per class with a runnable example for every public method (`bare` and `*OrNull` variants documented together). Index in `docs/README.md`.
- README link to the new reference docs.

### Changed

- `.gitattributes`: `/docs export-ignore` so the new docs ship with the GitHub repo but stay out of the `composer archive` tarball.

## [0.1.1] - 2026-05-23

### Added

- PHPDoc on every class and public method (class summary, parameter / return / throws notes where useful).
- `@author rak200 <rak.ricardo@windowslive.com>` on every class.
- `.gitattributes` with `export-ignore` rules so `composer archive` / dist tarballs skip dev files (`tests/`, `phpunit.xml`, CI/dotfiles, `CLAUDE.md`) and force LF line endings on text files.

## [0.1.0] - 2026-05-23

### Added

- Initial release.
- Tier 1 classes: `Str`, `Arr`, `Num`, `Rand`.
- Tier 2 classes: `Regex`, `Hash`, `Bit`, `File`, `Json`, `Base64`, `Dt`.
- Alphabet constants on `Rand`: `NUM`, `HEX`, `ALPHA`, `ALNUM`.
- UUID v4, UUID v7, ULID (Crockford base32, bit-stream encoded) and nanoid generators on `Rand`.

[1.1.0]: https://github.com/rak200/utils/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/rak200/utils/compare/0.3.0...1.0.0
[0.3.0]: https://github.com/rak200/utils/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/rak200/utils/compare/0.1.2...0.2.0
[0.1.2]: https://github.com/rak200/utils/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/rak200/utils/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/rak200/utils/releases/tag/0.1.0
