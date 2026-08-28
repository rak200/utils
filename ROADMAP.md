# Roadmap

Pending work, ordered. Released history lives in [CHANGELOG.md](CHANGELOG.md); a delivered entry
is **removed** by the pull request that delivers it, not annotated as done.

## Planned additions

**None outstanding.** Work this library's releases unlock **in consumer libraries** is tracked in
those repositories' roadmaps, not here.

## Type-annotation corrections (PHPDoc-only, BC-safe)

**None outstanding.** This section holds annotations *less precise than what the implementation
already guarantees*, which block a consumer from adopting the helper at all: the declared type is
wider than the call site's contract, so the "fix" would be a PHPStan suppression, which the
conventions forbid. Auditing a consumer library against the prefer-lib-over-native rule is what
surfaces them. They are PHPDoc-only, so they ship in a minor with no runtime change.

## Test coverage — the deliberate 34

The suite targets **pragmatic** line coverage, enforced by `.coverage-floor` at **97.89%**
(1624/1659) — the figure CI measures, under **pcov**. A local run under **xdebug** reports
1625/1659 (97.95%): the two drivers disagree on exactly one statement, and which one is not yet
identified. The floor carries the CI number because CI is what enforces it; a local run reading
one statement higher is expected and is not evidence of anything. Every reasonably-testable
branch is closed, error and `@throws` paths included
(an invalid input that makes a native emit a warning is tested with `@` suppressing that expected
warning, the idiom `Regex::is` uses). The 34 lines uncovered **under xdebug** are deliberate, and the two
categories below account for all of them — anything outside this inventory is a gap, not a
decision. Under pcov the count is 35, by the one-statement disagreement above, so the inventory
is exhaustive against the driver it was taken with and short by one against the other:

- **Unreachable defensive code (22)** — the 18 private `__construct() {}` of the static-only
  classes; the post-loop `return self::BITS;` in `Bit::leadingZeros` / `trailingZeros` (a non-zero
  value always finds a set bit inside the loop); and the `return;` after `yield from` in
  `Iter::doRange` / `doRepeat` (both delegate to an infinite generator, which never completes).
- **Branches that only fire when a native call fails despite valid preconditions (12)** —
  `File::read` / `delete` / `size` / `lines` / `temp` / `list` / `readCsv` / `writeCsv` and
  `File::mime`'s finfo branches; `Dt::fromEpochMs`'s `createFromFormat` failure (the format string
  is built from ints, so it always parses); and `Filter::ascii`'s iconv-unavailable fallback.

**The exhaustive count is the point**: it is what turns a coverage report into a decision.
`Num::div`'s `BcMath\Number` divide-by-zero guard sat uncovered for several releases precisely
because nobody could tell it apart from the deliberate list — and `minCoveredMsi` cannot flag it,
since mutants on an uncovered line are not counted.

**Deferred:** whether to pursue literal 100%, and how. Forcing it would need fragile,
platform-specific setups (read-only dirs via `chmod` / `icacls`, invalidated handles, mocking
`finfo`) that contradict the suite's clean style. Going literal would also unlock raising the
mutation gate from `minCoveredMsi: 100` to a full `minMsi: 100`.

## Contingent — additive, ships in any minor when demand appears

- **`Math`** — worth splitting out only if trigonometry, logarithms, number theory or scientific
  constants are ever added. Until then basic arithmetic (`pow` / `sqrt` / `floor` / `ceil` /
  `mod`) stays in `Num`, one class per topic. Trig / log / `exp` / `pi` / `deg2rad`, and
  number-theory helpers such as `gcd` / `lcm` (no native; `gcd` via Euclid, `lcm` derived from
  it), belong here, **not** in `Num`. There is no point creating an empty class.
- **`Email::local()` / `Email::domain()`** — splitting an address at the `@`. Deliberately *not*
  bundled with the 4.5.0 validator: extraction is not a `Filter` concern (that class is
  sanitisation, predicates and coercion), so these need an `Email` topic class of their own, and a
  class born to hold two methods needs a real consumer first. Open decisions to settle when one
  appears: behaviour with no `@` at all, with multiple `@` (quoted local parts are legal), and
  throw vs `*OrNull`. Note the split would move `Email::is()` there too, leaving
  `Filter::isEmail()` as a deprecated alias — so it is not a free addition.

## Out of scope — by design, won't do

Deliberate exclusions, not pending work: these contradict the library's pure / immutable /
stateless contract. Impure concerns belong in a sibling library (`rak200/http-input`).

- **Mutable / pointer / in-place natives** — `array_pop` / `array_shift` / `array_splice`,
  in-place `sort`, `end` / `reset` / `next` / `current`, `settype`. The useful cases already have
  immutable equivalents: `array_push` / `array_unshift` → `Arr::append` / `prepend`, in-place
  `sort` → `Arr::sort` / `sortBy` / `sortKeys`, `settype` → `Filter::to*`, `end` / `reset` /
  `current` → `Arr::first` / `last`. The *mutating* `array_shift` / `array_pop` stay out, but
  their pure `[element, rest]` form ships as `Arr::shift` / `Arr::pop` (+ `*OrNull`); for a single
  half use `Arr::first` / `last` or `Arr::slice`. The same qualifier covers `array_splice`: the
  in-place native stays out, its pure form ships as `Arr::removeAt`. These pure forms may still
  *use* the in-place native internally on a by-value parameter — `removeAt` calls `array_splice`,
  `sortKeys` calls `ksort` — which is not a contradiction: the exclusion is of the mutating
  operation in the public API, and delegating keeps every edge case identical to the native. See
  [docs/arr.md](docs/arr.md#dropping-the-first--last-element-array_shift--array_pop).
- **Global / impure / low-level** — `setlocale`, `ini_*`, raw stream / resource handling.
