# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.8.0] - 2026-05-30

### Added

- **Domain-level `is()` predicates.** Each domain class now owns its canonical "is a member of this domain?" predicate, removing the discoverability friction of having to remember whether a check lived on `Type` or on the domain class. New methods (all accept `mixed` and carry `@phpstan-assert` PHPDoc):
  - `Str::is` — true for strings (= `Type::isStr`).
  - `Arr::is` — true for arrays (= `Type::isArray`).
  - `Enum::is` — true for enum cases (`UnitEnum` instances; = `Type::isEnum`).
  - `Dt::is` — true for `DateTimeInterface` instances.
  - `Json::is` — true for strings that parse as JSON. Replaces `Json::isValid` (kept as `@deprecated` alias) and now accepts `mixed`.
  - `Num::is` — true for ints, floats, strict numeric strings, and `BcMath\Number` instances. Replaces `Num::isNumeric` (kept as `@deprecated` alias).
  - `Base64::is` — true for strings decodable by `decode` or `decodeUrl`.
  - `Regex::is` — true for syntactically valid PCRE patterns. (Never throws — unique among `Regex` methods.)
  - `Url::is` — true when the input passes `FILTER_VALIDATE_URL`.

### Changed

- **`Type::isStr` / `isInt` / `isFloat` / `isArray` / `isEnum` / `isNumeric` are now thin aliases** of the corresponding domain method (`Str::is`, `Num::isInt`, `Num::isFloat`, `Arr::is`, `Enum::is`, `Num::is`). No behaviour change at the call site — narrowing assertions and return values are identical.

### Deprecated

- **`Num::isNumeric`** — use `Num::is` instead. Alias kept until 2.0.0.
- **`Json::isValid`** — use `Json::is` instead. Alias kept until 2.0.0.

## [1.7.0] - 2026-05-29

### Added

- **`Enum`** — new tier-2 class for class-level enum operations. PHP ships `cases()`, `from()`, `tryFrom()` on the enum itself; this class fills the gaps: `names`/`values` (list cases in declaration order), `fromName`/`tryFromName` (name lookup — the gap PHP leaves open, since the native `from()`/`tryFrom()` look up by *value* and only work on backed enums), `random` (cryptographically-secure pick via `Rand::choice`), and `toArray` (form-friendly `[name => value]` map for backed enums, `[name => name]` for pure). `fromName`/`tryFromName`/`random` are `@template`-typed so PHPStan narrows the return to the concrete enum class. The instance-side predicate stays at `Type::isEnum`.

## [1.6.1] - 2026-05-29

### Fixed

- **`Num::isNumeric` / `Num::parseFloat*`** — strict whitespace contract. PHP's `is_numeric()` accepts surrounding whitespace (leading always, trailing since PHP 8.0), which was leaking through `Num::isNumeric` and `Num::parseFloat*`. They now reject surrounding whitespace, matching `Num::parseInt*` and `Num::parseNumber*` (already strict since 1.6.0). `Num::isNumeric(' 42 ')` → `false`; `Num::parseFloatOrNull(' 3.14 ')` → `null`.
- **`Type::isNumeric` / `Type::isNumericStr`** — same fix. `Type::isNumeric` again mirrors `Num::isNumeric` (as the PHPDoc states), and `Type::isNumericStr` matches the strict-numeric-string contract instead of inheriting `is_numeric()`'s whitespace tolerance.

### Added

- **`Str::isWhitespace(string)`** — true when every character of the input is ASCII whitespace (`[ \t\n\r\v\f]`); false for the empty string. Wraps `ctype_space()`. Used internally by the strict whitespace check in `Num` and `Type`, and exposed for callers that need the same predicate.

## [1.6.0] - 2026-05-29

### Changed

- **`Type` predicates now narrow types** — every predicate carries `@phpstan-assert` PHPDoc, so callers get the same static type-narrowing that native functions provide: `isStr`/`isBool`/`isInt`/`isFloat`/`isArray`/`isObject`/`isEnum`/`isCallable`/`isIterable`/`isNull`/`isScalar`/`isResource` assert both branches; `isNumeric`/`isNumericStr`/`isIntLike`/`isClassName`/`isInterfaceName` assert the truthy branch; and the generic `isInstanceOf`/`isA`/`isSubclassOf` are now `@template`-typed (`class-string<T>` target) so a true result narrows the value to `T`. No runtime behaviour change.
- **`Num::parseInt*` / `parseFloat*` / `parseNumber*` no longer `trim()` the input.** Parsing is strict: surrounding whitespace is rejected (`parseIntOrNull(' 42 ')` and `parseNumberOrNull(' 42 ')` now return `null`). `parseFloat*` is effectively unchanged, since its `(float)` cast already tolerated whitespace.

## [1.5.0] - 2026-05-28

### Added

- **`Type::isSubclassOf`** — true when a value (object or class-name string) is a proper subclass of a given class, or implements it when the target is an interface. Shortcut for `is_subclass_of()` with `$allow_string = true`. Unlike `Type::isA`, the exact same class returns `false` (a class is not its own subclass).

## [1.4.1] - 2026-05-28

### Fixed

- **`File::mimeType`** — dropped the `finfo_close()` call, which is deprecated as of PHP 8.5 (finfo objects are freed automatically when they go out of scope). No behaviour change; silences the deprecation on PHP 8.5+ and remains correct on 8.4.

## [1.4.0] - 2026-05-28

### Added

- **`Type::of`** — returns the resolved type name of a value (wraps `get_debug_type()`): `int`/`float`/`string`/`bool`/`null`/`array`, the fully-qualified class name for objects, or a `resource (...)` label.
- **`Type::isEnum`** — true when the value is an enum case (an instance of `UnitEnum`, which every pure and backed enum implements). Enum class-name strings are not accepted.
- **`Type::usesTrait`** — true when a value (object or class-name string) uses a given trait. By default only traits applied directly on the class count; `recursive: true` also matches traits inherited from parent classes and traits used by other traits (nested).

## [1.3.0] - 2026-05-27

### Added

- **PHPStan** — added `phpstan/phpstan ^2.1` as a dev dependency, configured at `level: max` with no baseline. `composer phpstan` runs the analysis (with a 512M memory limit). Library and tests are clean at the highest strictness level.
- **PHPDoc generics** — `Arr` (every higher-order method: `find`, `findOrNull`, `filter`, `map`, `reduce`, `groupBy`, `partition`, `chunk`, `unique`, `sort`, `sortBy`, `keyBy`, `pick`, `except`, `first`, `firstOrNull`, `last`, `lastOrNull`, `keys`, `values`) and `Rand::choice` / `Rand::shuffle` now carry `@template` types, so callers get precise return-type inference (e.g. `Arr::find(array<int>, ...)` returns `int`, not `mixed`).

### Changed

- **`Str::join`** — `@param` narrowed from `iterable<mixed>` to `iterable<int|float|string|bool|\Stringable|null>`. Runtime behaviour is unchanged (the native type is still `iterable`), but static analysis now flags callers that pass arrays or non-stringable objects, which would have failed at the implicit `(string)` cast anyway.
- **`Url::decodeQuery`** — `@return` widened from `array<string, mixed>` to `array<int|string, mixed>` to reflect that `parse_str` produces integer keys for numeric-only query parameters (e.g. `?123=foo`).
- **`Arr::keyBy`** — throws when the resolved key value (from a column lookup or a callable) is not an `int` or `string`, instead of triggering an undefined-behaviour array offset. The pre-existing throw for missing columns is retained.
- **`Num`** — internal refactor of union arithmetic (`sum`, `avg`, `pow`, `mod`, `numberFloorCeil`) so the type system can follow the `int|float|Number` branches explicitly. New private helpers `Num::add()` (centralised widening addition) and `Num::pow10()` (precision-safe powers of ten via digit concatenation). Removed two stale `@phpstan-ignore-next-line` comments. No behaviour change.

## [1.2.0] - 2026-05-27

### Added

- **`Str`** — `toCamel`, `toPascal`, `toSnake`, `toKebab`. Shorter, more idiomatic case-conversion helpers.
- **`Num`** — `isInt`. Mirrors PHP's native `is_int()` and aligns with the existing `Num::isFloat`.
- **`File`** — `isDir`. Mirrors PHP's native `is_dir()` and matches `File::isFile` in length.

### Deprecated

- **`Str::toCamelCase`, `Str::toPascalCase`, `Str::toSnakeCase`, `Str::toKebabCase`** — use `Str::toCamel`/`toPascal`/`toSnake`/`toKebab` instead. The old names remain as delegating aliases and will be removed in 2.0.0.
- **`Num::isInteger`** — use `Num::isInt` instead. Removal in 2.0.0.
- **`File::isDirectory`** — use `File::isDir` instead. Removal in 2.0.0.

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

[1.8.0]: https://github.com/rak200/utils/compare/1.7.0...1.8.0
[1.7.0]: https://github.com/rak200/utils/compare/1.6.1...1.7.0
[1.6.1]: https://github.com/rak200/utils/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/rak200/utils/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/rak200/utils/compare/1.4.1...1.5.0
[1.4.1]: https://github.com/rak200/utils/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/rak200/utils/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/rak200/utils/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/rak200/utils/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/rak200/utils/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/rak200/utils/compare/0.3.0...1.0.0
[0.3.0]: https://github.com/rak200/utils/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/rak200/utils/compare/0.1.2...0.2.0
[0.1.2]: https://github.com/rak200/utils/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/rak200/utils/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/rak200/utils/releases/tag/0.1.0
