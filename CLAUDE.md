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

## Where the rules are

In the two imports above and in this repository's own documents. This file restates none of them.

- **Static-only classes, and the `src/Exception/` carve-out** — [README.md](README.md)
  §*Conventions*, and [docs/exceptions.md](docs/exceptions.md).
- **Purity is the contract**, and what that rules out — [ROADMAP.md](ROADMAP.md) §*Out of scope*.
  The exclusions are recorded so a rejected idea is not re-litigated.
- **Why `Filter` calls `preg_replace` directly** instead of `Regex::replace` — the class docblock
  in [src/Filter.php](src/Filter.php). It is the one carve-out from prefer-lib-over-native, and
  the reason is what makes "no method throws" true on invalid UTF-8.
- **The 34 uncovered lines are an inventory, not a gap** — [ROADMAP.md](ROADMAP.md) §*Test
  coverage*. Anything outside that inventory is a gap.

## Running one thing

A single PHPUnit `Unit` suite, configured in `phpunit.xml`. One file:
`vendor/bin/phpunit tests/StrTest.php`.
