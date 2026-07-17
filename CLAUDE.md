# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The **cross-library rak200 PHP conventions** (baseline & tooling, dev dependencies, CI, code style, naming, `use function` inventory, first-class callables, correctness-over-efficiency, safe defaults, testing, versioning, README badges) are shared and imported below. This file keeps only what is specific to **utils**.

@~/.claude/rak200-php-conventions.md

## Project Overview

**rak200/utils** is a standalone PHP 8.4+ library of static utility helpers. It groups commonly-used helpers into a small set of `final` classes with short Laravel-style names, replacing scattered global functions with a discoverable, type-strict API. No runtime dependencies.

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
    └── StaticAnalysis/  # PHPStan-only assertType fixtures (no Test suffix; never run by PHPUnit)
```

Production classes live under `Rak200\Utils\` (PSR-4 from `src/`); test classes live under `Rak200\Utils\Tests\` (PSR-4 from `tests/`, dev-only).

## Conventions (utils-specific)

The general PHP conventions live in the imported shared file above. What follows is specific to this library:

- **Static-only classes.** Every class is `final` with a `private` constructor and only `public static` methods — pure functions, no instances, no state. Public API takes/returns native PHP types; no custom wrapper objects.
- **Purity is the contract.** No mutable / in-place / pointer natives, no global / impure / low-level state — see [Out of scope](#out-of-scope-by-design--wont-do). Impure concerns belong in a separate sibling library (e.g. `rak200/http-input`), not here.
- **Per-class docs.** utils ships a full per-class reference under `docs/` (index: [docs/README.md](docs/README.md)); every new or changed public method must be reflected there, following the layout in the shared conventions.
- **`Filter`'s prefer-lib-over-native carve-out.** The general rule is in the shared file; the notable utils exception: `Filter` sanitizers keep `preg_replace(...) ?? ''` rather than `Regex::replace`, because `Regex::replace` throws on the `null` that invalid UTF-8 yields under the `/u` modifier — which would violate `Filter`'s "never throws" guarantee.

## Testing

General testing conventions are in the shared file. utils specifics:

- PHPUnit is configured via `phpunit.xml` with a single `Unit` suite.
- Single file: `vendor/bin/phpunit tests/StrTest.php`.
- Time-sensitive tests (`Rand::uuidV7`, `Dt::now`) assert structural properties rather than exact values.

## Roadmap

Planned additions and corrections. Released items live in `CHANGELOG.md`.

### Test coverage (pragmatic; literal 100% is a deferred decision)

The suite targets **pragmatic** line coverage — currently **~97.5%** — closing every reasonably-testable branch, error/`@throws` paths included (an invalid input that makes the native emit a warning is tested with `@` suppressing that expected warning, the same idiom `Regex::is` uses). The lines left uncovered are deliberate:

- **Unreachable defensive code** — the 17 private `__construct() {}` of the static-only classes, and the post-loop `return self::BITS;` in `Bit::leadingZeros`/`trailingZeros` (a non-zero value always finds a set bit inside the loop).
- **Branches that only fire when a native call fails despite valid preconditions** — `File::read`/`delete`/`size`/`lines`/`temp`/`list`/`readCsv`/`writeCsv` and `File::mime`'s finfo branches (the file exists / handle is valid but `file_get_contents`/`unlink`/`filesize`/`fopen`/`tempnam`/`glob`/`fputcsv`/`finfo_*` returns `false`), `Dt::fromEpochMs`'s `createFromFormat` failure (the format string is built from ints, so it always parses), and `Filter::ascii`'s iconv-unavailable fallback (iconv is present).

Forcing these to **literal 100%** would need fragile, platform-specific setups (read-only dirs via `chmod`/`icacls`, invalidated handles, mocking `finfo`) that contradict the suite's clean style. **Deferred:** decide later whether to pursue literal 100%, and how.

### Mutation testing (adopt Infection)

Wire up `infection/infection` as `rak200/caster` does — `infection.json5.dist`, a `composer infection` script, and a floor-only CI step (see the shared conventions' *Mutation testing (Infection)* section). Provably-equivalent survivors are ignored in-code with `@infection-ignore-all` anchored on the smallest node; the threshold is never lowered. Gate **`minCoveredMsi: 100`** now (it scopes to covered code, so it is independent of the ~97.5% line coverage); **`minMsi: 100`** is contingent on the literal-100%-coverage decision above (uncovered lines' mutants can't be killed).

### Contingent (additive — ships in any minor when there's demand)

- **`Math`** — only worth splitting out if trigonometry, logarithms, number theory, or scientific constants are ever added. Until then, basic arithmetic (`pow`/`sqrt`/`floor`/`ceil`/`mod`) stays in `Num` to keep one class per topic. Trig / log / `exp` / `pi` / `deg2rad`, and number-theory helpers such as `gcd` / `lcm` (no native; `gcd` via Euclid, `lcm` derived from it), belong here, **not** in `Num`. Purely additive — there's no point creating an empty class, so it lands in a minor release when real demand appears.
- **Lib-scoped catch (marker interface).** 4.0.0 mapped every throw-site to the precise SPL type (`InvalidArgumentException` malformed input, `OutOfBoundsException` lookup miss, `UnderflowException` empty source, `UnexpectedValueException` bad callback result, `RuntimeException` environment failures), so callers can already branch on the failure *kind*. What SPL cannot express is "a failure from *this* library" as one catch — that would need a marker interface (e.g. `Rak200\Utils\Exception\UtilsException`) implemented by thin subclasses of each SPL type used. Only worth it when a real call-site needs the lib-scoped catch; messages and the SPL base types would be unchanged (BC-safe minor).

### Out of scope (by design — won't do)

Deliberate exclusions, not pending work: these contradict the library's pure / immutable / stateless contract. Impure concerns belong in a separate sibling library (e.g. `rak200/http-input`), not here.

- **Mutable / pointer / in-place natives** — `array_pop` / `array_shift` / `array_splice`, in-place `sort`, `end` / `reset` / `next` / `current`, `settype` — break the pure / immutable contract. The useful cases already have immutable equivalents: `array_push` / `array_unshift` → `Arr::append` / `prepend`, in-place `sort` → `Arr::sort` / `sortBy` / `sortKeys`, `settype` → `Filter::to*`, `end` / `reset` / `current` → `Arr::first` / `last`. The *mutating* `array_shift` / `array_pop` stay out, but their pure `[element, rest]` form ships as `Arr::shift` / `Arr::pop` (+ `*OrNull`) — both halves returned without touching the input; for a single half use `Arr::first` / `last` (element) or `Arr::slice` (remainder). See [docs/arr.md](docs/arr.md#dropping-the-first--last-element-array_shift--array_pop).
- **Global / impure / low-level** — `setlocale`, `ini_*`, raw stream / resource handling — out of scope for a pure helper library.
