# CLAUDE.md

Guidance for Claude Code when working in this repository.

@.rak200/CONVENTIONS.md
@vendor/rak200/coding-standard-php/CONVENTIONS.md

> If `.rak200/` is empty, the clone skipped its submodule:
> `git submodule update --init --recursive`. If the second import is missing, run
> `composer install` — PHP development needs it anyway.

## What this repository is

**rak200/utils** is a standalone PHP 8.4+ library of static utility helpers. It groups
commonly-used helpers into a small set of `final` classes with short Laravel-style names,
replacing scattered global functions with a discoverable, type-strict API. No runtime Composer
dependencies — only `ext-bcmath` (for `Num`'s `BcMath\Number` support) and `ext-mbstring`.

## Architecture

```
src/
├── Str.php       # strings (Tier 1)
├── Arr.php       # arrays (Tier 1)
├── Iter.php      # lazy iterables / generators (Tier 1)
├── Num.php       # numbers (Tier 1)
├── Rand.php      # randomness, uuid, ulid, nanoid (Tier 1)
├── Regex.php     # regular expressions (Tier 2)
├── Hash.php      # hashing + passwords (Tier 2)
├── Bit.php       # bit manipulation (Tier 2)
├── File.php      # filesystem (Tier 2)
├── Json.php      # JSON (Tier 2)
├── Base64.php    # Base64 encode/decode (Tier 2)
├── Hex.php       # hexadecimal encode/decode of binary strings (Tier 2)
├── Dt.php        # DateTimeImmutable helpers (Tier 2)
├── Url.php       # URL parse/build, query encode/decode (Tier 2)
├── Path.php      # logical path manipulation, no disk access (Tier 2)
├── Type.php      # type-checking predicates accepting mixed (Tier 2)
├── Enum.php      # class-level enum operations (Tier 2)
├── Filter.php    # input sanitisation + mixed-to-typed coercion (Tier 2)
└── Exception/    # UtilsException marker + domain exceptions
tests/            # mirrors src/, one *Test.php per class
└── StaticAnalysis/  # PHPStan-only assertType fixtures; no Test suffix, never run by PHPUnit
```

Production classes live under `Rak200\Utils\` (PSR-4 from `src/`); test classes under
`Rak200\Utils\Tests\` (PSR-4 from `tests/`, dev-only).

## Conventions specific to this library

- **Static-only classes.** Every class is `final` with a `private` constructor and only
  `public static` methods — pure functions, no instances, no state. The public API takes and
  returns native PHP types; no wrapper objects. The one deliberate carve-out is `src/Exception/`:
  exception classes are instantiable by nature — empty-bodied domain classes over their SPL
  parents plus the `UtilsException` marker (`IOException` is the one abstract grouping node),
  carrying no behaviour of their own. See [docs/exceptions.md](docs/exceptions.md).
- **Purity is the contract.** No mutable / in-place / pointer natives, no global / impure /
  low-level state. Impure concerns belong in a sibling library (`rak200/http-input`), not here.
  The exclusions are recorded in [ROADMAP.md](ROADMAP.md) under *Out of scope*, so a rejected
  idea is not re-litigated.
- **`Filter`'s carve-out from prefer-lib-over-native.** `Filter` sanitizers keep
  `preg_replace(...) ?? ''` rather than `Regex::replace`, because `Regex::replace` throws on the
  `null` that invalid UTF-8 yields under the `/u` modifier — which would violate `Filter`'s
  "never throws" guarantee.

## Testing specifics

- A single PHPUnit `Unit` suite, configured in `phpunit.xml`. One file:
  `vendor/bin/phpunit tests/StrTest.php`.
- Time-sensitive tests (`Rand::uuidV7`, `Dt::now`) assert structural properties, never literals.
- **The 34 uncovered lines are an inventory, not a gap** — see [ROADMAP.md](ROADMAP.md),
  *Test coverage*. Anything outside that inventory is a gap.
