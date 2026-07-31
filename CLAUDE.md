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
│   ├── Filter.php    # input sanitisation + mixed-to-typed coercion (Tier 2)
│   └── Exception/    # UtilsException marker + domain exceptions (IOException → Filesystem branch)
└── tests/            # mirrors src/ layout (one *Test.php per class)
    └── StaticAnalysis/  # PHPStan-only assertType fixtures (no Test suffix; never run by PHPUnit)
```

Production classes live under `Rak200\Utils\` (PSR-4 from `src/`); test classes live under `Rak200\Utils\Tests\` (PSR-4 from `tests/`, dev-only).

## Conventions (utils-specific)

The general PHP conventions live in the imported shared file above. What follows is specific to this library:

- **Static-only classes.** Every class is `final` with a `private` constructor and only `public static` methods — pure functions, no instances, no state. Public API takes/returns native PHP types; no custom wrapper objects. The one deliberate carve-out is `src/Exception/`: exception classes are instantiable by nature — empty-bodied domain classes over their SPL parents plus the `UtilsException` marker (`IOException` is the one abstract grouping node), carrying no behaviour of their own (see [docs/exceptions.md](docs/exceptions.md)).
- **Purity is the contract.** No mutable / in-place / pointer natives, no global / impure / low-level state — see [Out of scope](#out-of-scope-by-design--wont-do). Impure concerns belong in a separate sibling library (e.g. `rak200/http-input`), not here.
- **Per-class docs.** utils ships a full per-class reference under `docs/` (index: [docs/README.md](docs/README.md)); every new or changed public method must be reflected there, following the layout in the shared conventions.
- **`Filter`'s prefer-lib-over-native carve-out.** The general rule is in the shared file; the notable utils exception: `Filter` sanitizers keep `preg_replace(...) ?? ''` rather than `Regex::replace`, because `Regex::replace` throws on the `null` that invalid UTF-8 yields under the `/u` modifier — which would violate `Filter`'s "never throws" guarantee.

## Testing

General testing conventions are in the shared file. utils specifics:

- PHPUnit is configured via `phpunit.xml` with a single `Unit` suite.
- Single file: `vendor/bin/phpunit tests/StrTest.php`.
- Time-sensitive tests (`Rand::uuidV7`, `Dt::now`) assert structural properties rather than exact values.

### Mutation testing — differential CI (utils-specific divergence)

A full Infection run over `src/` takes ~27 minutes, so utils **diverges from the shared conventions' full-run-in-CI prescription** — waiting for it on every push was not viable. What changes is *when and what* gets mutated; the `minCoveredMsi: 100` gate itself is unchanged.

- **Pull requests (blocking):** only the **changed lines** are mutated — `composer infection -- --git-diff-lines --git-diff-base=origin/master --ignore-msi-with-no-mutations`, floor `8.4` job. Needs `fetch-depth: 0` on checkout so `origin/master` is available to diff against; `--ignore-msi-with-no-mutations` lets a docs/tests-only PR pass instead of failing on zero mutants.
- **Push to `master`:** no mutation step at all (`master` is branch-protected, so changes arrive through a PR that already passed the diff gate).
- **Full run:** manual only, via the workflow's `workflow_dispatch` trigger. Run it before a significant release — it is the safety net for cross-file MSI drift, which the diff gate cannot see (a change in file A that stops a test from killing a mutant in untouched file B).
- **Locally:** `composer infection-diff` mutates just your uncommitted changes against `master`.

Ported to http-input (0.4.1). Still to port if it keeps proving out: `~/.claude/rak200-php-conventions.md` + caster.

## Roadmap

Planned additions and corrections. Released items live in `CHANGELOG.md`.

### Planned additions

None outstanding — the 4.5.0 cycle cleared the list. New entries go here; the sections below hold what is deferred, dropped, or waiting on a release.

### Investigated and dropped

- **`Arr::searchOrNull()` binding the key type** (dropped 2026-07-29; the `search()` half shipped). PHPStan does not resolve a template inside a union with `null`. Three shapes measured: `null|K` — does not resolve; `($array is non-empty-array ? K|null : null)` — does not resolve; `($array is non-empty-array ? K : null)` — **resolves, but is false**, since a non-empty array may simply not contain the value, and adopting it would make every caller's `=== null` check look like dead code to the analyser. The wide type is the honest one, so the signature is spelled `@return null|K` to state the intent while the analyser degrades it to `int|string|null`, and a `tests/StaticAnalysis` fixture pins the degraded type on purpose so nobody "fixes" it into the lie that type-checks. **The deciding question is not whether the conditional type-checks — it does — but whether it is true.** That is what separated this from `firstKeyOrNull()` / `lastKeyOrNull()`, which carried the identical `null|K` defect but where the conditional *is* true (a non-empty array always has a first key): those two shipped the conditional in 4.5.0, needing no guard — the annotation alone proved out against the untouched one-line bodies.

- **A class-name membership predicate accepting an unvalidated string** (dropped 2026-07-28). The premise was that `Type::isInstance()` / `isA()` are unusable when the class name is a runtime string, because the fallback `isClassName($n) && isInstance($v, $n)` misses interfaces (`class_exists` is false for them). That half is true. What the roadmap missed is that **`isInterfaceName` already exists and also declares `@phpstan-assert-if-true class-string`**, so `isClassName($n) || isInterfaceName($n)` narrows the name and `isInstance()` then type-checks — covering class, interface and enum (an enum is a class) with no new API. Verified with a PHPStan probe; pinned by `tests/StaticAnalysis/TypeNarrowing.php` and documented in `docs/type.md`. It was a documentation gap, not a missing helper.
  - Also settled, so it is not re-proposed: **loosening `isA` with a conditional cannot work.** Widening to `@param class-string<T>|string` normalises to plain `string`, and PHPStan then reports `Template type T of method Type::isA() is not referenced in a parameter` — the template stops resolving, the narrowing leaks as an unresolved placeholder, and `Filter::toStr()` in this very library breaks (3 repo-wide errors). Recovering `T` from a runtime string is impossible by construction.
  - The alternative shape — a new method duplicating `isInstance` with a relaxed contract — was rejected as public API existing solely to satisfy the analyser.

### Downstream follow-ups (waiting on a published release)

- **collections' `HashesValues` and `Num::toStr()` — expect *no* change, and here is why.** `hashValue()` does `'f:' . var_export($value, true)` over an arbitrary float, and the roadmap listed it as the consumer that would adopt `Num::toStr()`. It will not: `toStr` throws on NAN/INF (a deliberate choice — no string form of those reads back through `parseFloat`), so the call site would need `Num::isFinite($v) ? Num::toStr($v) : var_export($v, true)`, which is **longer than the native it replaces**. Keeping `var_export` there is correct. Recorded so a future prefer-lib-over-native pass does not "fix" it and make that file worse.

- **collections' `MultiMap::removeValue()` can also drop its `array_search` for `Arr::search()`** — once 4.5.0 is published. The helper now carries the array's key type, so the `int` the call site needs survives; today it keeps the native precisely because the helper did not. Weigh it against the cost noted in the changelog: `search` calls `array_keys()` rather than `array_search()`, so it has no early exit and pays a flat full-scan (~80 µs per 10,000 elements) where the native returns in ~2 µs on an early match. For `MultiMap`'s per-key lists (short by construction) that is irrelevant.

- **collections' `MultiMap::removeValue()` switches to `Arr::removeAt()`** — once 4.5.0 is published. `array_splice($this->items[$key], $idx, 1)` becomes `$this->items[$key] = Arr::removeAt($this->items[$key], $idx);`, dropping that library's only `use function array_splice;`. Note the assignment: the helper is pure where the native spliced in place.

- **collections' `Set::key()` and `OrderedSet::key()` switch to `Arr::keyPositionOrNull()`** — once 4.5.0 is published. Both are byte-identical `array_search(parent::key(), Arr::keys($this->items), true)` plus the `=== false` dance, and both drop their `use function array_search;`. One wrinkle: `parent::key()` returns `int|string|null`, so the call still needs a null guard — the helper takes `int|string`.

- **collections' `MultiMap::values()` and `MultiMap::flattenSnapshot()` collapse to single `Arr::flatMap()` calls** — once 4.5.0 is published. Both are hand-rolled nested `foreach`es today; `flattenSnapshot` is the one that needs the key, which is why the callback is signed `callable(T, K)`.

- **collections' `OrderedSet::add()` switches to `Arr::sort(..., preserveKeys: true)`** — once 4.5.0 is published. That `uasort` (`src/OrderedSet.php`) is the only one in that library, so the switch drops its `use function uasort;` import outright. The call becomes `$this->items = Arr::sort($this->items, $this->comparator, preserveKeys: true);` — note the assignment: the helper is pure, where the native sorted the property in place.

- **http-input's `Rule::email()` switches to `Filter::isEmail()`** — once 4.5.0 is published. That verifier is the last native `filter_var` in that library, and its `use function filter_var;` is a one-member import group, so the switch drops the whole group and closes the prefer-lib-over-native carve-out documented there. Its sibling `Rule::url()` already delegates to `Url::is()`, so the two end up symmetric.

### Type-annotation corrections (PHPDoc-only, BC-safe)

**Cleared by 4.5.0 — nothing outstanding.** The section existed for annotations **less precise than what the implementation already guarantees**, which block a consumer from adopting the helper at all: the declared type is wider than the call site's contract, so the "fix" would be a PHPStan suppression, which the conventions forbid. It was surfaced auditing `rak200/collections` 0.8.0 (2026-07-25) against the prefer-lib-over-native rule, and every entry shipped in 4.5.0 — the `int<0, max>` pair on `Arr::count()` / `Iter::count()`, the `Arr::search()` key type, the `firstKeyOrNull()` / `lastKeyOrNull()` pair, and the `int`-preserving conditionals on `Num`. New entries go here; they are PHPDoc-only, so they ship in a minor with no runtime change.

Two limits are recorded so they are not re-proposed as if they were oversights:

- **The `Num` conditionals do not recover the `max(0, $x)` idiom**, where the target is `int<0, max>` — PHPStan derives that range from the literal `0` in the native's own stub, and no conditional over `iterable<int>` can express it. Consumers clamping into an `int<0, max>` property (`MultiSet::count()`, `LinkedList::remove()` in `rak200/collections`) keep the native `max()` either way; the win delivered was `Num::sum()` becoming usable in plain `int`-returning methods such as `MultiMap::total()`.
- **`Num::sum()` / `product()` claim `int` for an all-int input even though overflow past `PHP_INT_MAX` promotes to float**, which is knowingly accepted rather than overlooked: it is the identical claim PHPStan's own stub makes for `array_sum()`, and widening would leave the helper less precise than the native it replaces. It differs from the conditional rejected on `Arr::searchOrNull()` in that it makes no caller's code look unreachable. Do not "correct" it — extend the docblock instead. `min()` / `max()` are exact, since they return an element rather than computing.

### Test coverage (pragmatic; literal 100% is a deferred decision)

The suite targets **pragmatic** line coverage — measured at **97.89%** (1620/1655) for 4.5.0 — closing every reasonably-testable branch, error/`@throws` paths included (an invalid input that makes the native emit a warning is tested with `@` suppressing that expected warning, the same idiom `Regex::is` uses). The lines left uncovered are deliberate:

- **Unreachable defensive code** — the 17 private `__construct() {}` of the static-only classes, and the post-loop `return self::BITS;` in `Bit::leadingZeros`/`trailingZeros` (a non-zero value always finds a set bit inside the loop).
- **Branches that only fire when a native call fails despite valid preconditions** — `File::read`/`delete`/`size`/`lines`/`temp`/`list`/`readCsv`/`writeCsv` and `File::mime`'s finfo branches (the file exists / handle is valid but `file_get_contents`/`unlink`/`filesize`/`fopen`/`tempnam`/`glob`/`fputcsv`/`finfo_*` returns `false`), `Dt::fromEpochMs`'s `createFromFormat` failure (the format string is built from ints, so it always parses), and `Filter::ascii`'s iconv-unavailable fallback (iconv is present).

Forcing these to **literal 100%** would need fragile, platform-specific setups (read-only dirs via `chmod`/`icacls`, invalidated handles, mocking `finfo`) that contradict the suite's clean style. **Deferred:** decide later whether to pursue literal 100%, and how — going literal would also unlock raising the Infection gate from the current `minCoveredMsi: 100` to the full `minMsi: 100` (uncovered lines' mutants cannot be killed, which is why `infection.json5.dist` deliberately omits `minMsi`).

### Contingent (additive — ships in any minor when there's demand)

- **`Math`** — only worth splitting out if trigonometry, logarithms, number theory, or scientific constants are ever added. Until then, basic arithmetic (`pow`/`sqrt`/`floor`/`ceil`/`mod`) stays in `Num` to keep one class per topic. Trig / log / `exp` / `pi` / `deg2rad`, and number-theory helpers such as `gcd` / `lcm` (no native; `gcd` via Euclid, `lcm` derived from it), belong here, **not** in `Num`. Purely additive — there's no point creating an empty class, so it lands in a minor release when real demand appears.
- **`Email::local()` / `Email::domain()`** — splitting an address at the `@`. Deliberately *not* bundled with the 4.5.0 validator: extraction is not a `Filter` concern (that class is sanitisation, predicates and coercion), so these would need an `Email` topic class of their own, and a class born to hold two methods needs a real consumer first. Open decisions to settle when one appears: behaviour with no `@` at all, with multiple `@` (quoted local parts are legal), and throw vs `*OrNull` — none of which is worth deciding speculatively. Note the split would land `Email::is()` there too, moving the validator and leaving `Filter::isEmail()` as a deprecated alias, so this is not a free addition.
### Out of scope (by design — won't do)

Deliberate exclusions, not pending work: these contradict the library's pure / immutable / stateless contract. Impure concerns belong in a separate sibling library (e.g. `rak200/http-input`), not here.

- **Mutable / pointer / in-place natives** — `array_pop` / `array_shift` / `array_splice`, in-place `sort`, `end` / `reset` / `next` / `current`, `settype` — break the pure / immutable contract. The useful cases already have immutable equivalents: `array_push` / `array_unshift` → `Arr::append` / `prepend`, in-place `sort` → `Arr::sort` / `sortBy` / `sortKeys`, `settype` → `Filter::to*`, `end` / `reset` / `current` → `Arr::first` / `last`. The *mutating* `array_shift` / `array_pop` stay out, but their pure `[element, rest]` form ships as `Arr::shift` / `Arr::pop` (+ `*OrNull`) — both halves returned without touching the input; for a single half use `Arr::first` / `last` (element) or `Arr::slice` (remainder). The same qualifier covers `array_splice`: the in-place native stays out, its pure form ships as `Arr::removeAt` (remove from any position, discarding what was removed). Note that these pure forms may still *use* the in-place native internally on a by-value parameter — `removeAt` calls `array_splice`, `sortKeys` calls `ksort` — which is not a contradiction: the exclusion is of the mutating operation in the public API, and delegating is what keeps every edge case identical to the native. See [docs/arr.md](docs/arr.md#dropping-the-first--last-element-array_shift--array_pop).
- **Global / impure / low-level** — `setlocale`, `ini_*`, raw stream / resource handling — out of scope for a pure helper library.
