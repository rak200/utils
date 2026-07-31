# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.5.0] - 2026-07-31

### Added

- **`$preserveKeys` on `Arr::sort` and `Arr::sortBy`** — key-preserving sorts, the pure equivalent of `uasort` / `asort`. Every sort in the library re-indexed (`sort` / `sortBy` returned a `list`, `sortKeys` sorts *by* key), so "reorder the values, keep each one under its own key" was the one array operation a consumer could not rebuild from the existing helpers without dropping to the native — the reason `rak200/collections`' `OrderedSet::add()` keeps the only `uasort` in that library, its backing array being keyed by identity hashes that re-indexing would destroy. **Not the `Arr::sortAssoc()` the roadmap specified:** that entry rejected the parameter spelling on the grounds that it "cannot carry the conditional return type cleanly, since the key-preserving branch is `array<K, T>` while the default is `list<T>`". That objection was wrong — `Iter::toArray` already declares exactly `($preserveKeys is true ? array<TKey, TValue> : list<TValue>)` with a bound key template, and `reverse` / `slice` / `sortKeys` carry the same shape. So the flag reuses an idiom the library already had in four places, needs no new name, and extends to `sortBy` for free where the method-per-variant spelling would have cost two names. Both are stable for equal elements (PHP sorts have been stable since 8.0) and immutable, mutating only the by-value parameter, as `sortKeys` already did. The sharp edge, documented and pinned by tests and `tests/StaticAnalysis` fixtures on every branch: a `list` keeps its keys too, so `Arr::sort(['b', 'a'], preserveKeys: true)` is `[1 => 'a', 0 => 'b']` — typed `array<int<0, max>, T>`, **not** a `list`. **BC:** appending an optional parameter widens `sort`'s `@return list<T>` into a conditional whose default branch is still `list<T>`, so no existing call site changes type.

- **`Arr::flatMap`** — the eager twin of `Iter::flatMap`, closing a hole in the eager/lazy symmetry the two classes are built on. `Arr` had `map` and `flatten` but not their composition, and composing them loses the type: `Arr::flatten(Arr::map(…))` erases the element type because `flatten` is annotated `@return list<mixed>` — it has to be, arbitrary depth cannot be expressed generically — so consumers wrote the nested `foreach` by hand. Signed `callable(T, K): iterable<R>` returning `list<R>`, flattening **one level** only, matching the lazy twin; the callback's second argument is the key, which is what makes "flatten a map of lists while tracking which key each value came from" a one-liner (the shape `rak200/collections`' `MultiMap::flattenSnapshot()` needs — its `MultiMap::values()` is the simpler case). Any iterable is accepted as the callback's result, a `Generator` included.

- **`Filter::isEmail`** — the validating counterpart of the `Filter::email` sanitiser, closing the asymmetry with `Url::is`: the library could strip characters not allowed in an address but could not answer "is this one". Wraps `filter_var(..., FILTER_VALIDATE_EMAIL) !== false`, structure only — no DNS lookup, no deliverability check. **Home:** the roadmap proposed `Str::isEmail`; it ships on `Filter` instead. Every `Str` predicate asks about the *string itself* (is it a string, is it blank, are all its characters in an ASCII class), never about a domain format, so a validator reads wrong there — and `Type` is type-checking over `mixed`, the same category mismatch. `Filter` already owns the subject and the `filter_var` native; the cost is that its two documented groups (sanitisers, `to*` coercers) become three, with `is*` predicates now a group of its own — the class docblock and `docs/filter.md` say so. The totality guarantee is untouched: a `bool`-returning check never throws. The trap worth knowing is that sanitising does not imply the input was valid — `Filter::email('a@b.com<script>')` yields `'a@b.comscript'`, which `isEmail` then accepts, while the original input it came from is rejected; a dedicated test pins that pair. Also pinned: the empty string, surrounding whitespace and a dot-less domain (`user@localhost`) are all rejected, so the check is stricter than RFC 5321 allows.

- **`Arr::keyPosition` / `Arr::keyPositionOrNull`** — the positional axis `Arr::search` did not cover: `search` answers value => key, these answer key => its 0-based position in iteration order. Previously that cost two passes and an erased type (`array_search($key, Arr::keys($array), true)`), which is why `rak200/collections`' `Set::key()` and `OrderedSet::key()` both keep a native `array_search`. Follows the `search` / `searchOrNull` pair — bare throws `OutOfBoundsException`, `*OrNull` returns `null` — in a single pass over the keys, typed `@return int<0, max>`. **The subtlety worth knowing:** key matching follows `hasKey()`, i.e. PHP's own rule that a numeric-string key is normalised to an int on write, so `'1'` and `1` find the same key. A naive `foreach` + `===` would have reported "not found" for a key `hasKey()` says is present — an inconsistency inside one class — so the needle is normalised before comparison. A string that is not a canonical integer (`' 1'`, leading space) stays a distinct key and is correctly absent; a test asserts `keyPosition` and `hasKey` agree across every spelling.

- **`Arr::removeAt`** — remove elements *by position* and close the gap, the pure form of `array_splice`. `slice` could take a prefix or a suffix but not "everything except index i", and `except` works on keys, which re-indexing lists do not have stably, so this was the one array operation with no pure expression — hence the `array_splice` that `rak200/collections`' `MultiMap::removeValue()` still keeps. List-preserving conditional return type; the input is untouched. **Two roadmap premises were overridden after checking what the neighbours actually do.** The roadmap called for `InvalidArgumentException` on a negative `$length`, but `array_splice($a, 1, -1)` means "up to one element before the end" — the *same convention* `Arr::slice` already documents for its own `$length` — so rejecting it would make `removeAt` the only method in the class refusing what its sibling accepts on the same parameter; it is supported instead, which leaves the method **total, with no exceptions at all**. The roadmap also called an out-of-range `$index` a no-op, which is only true on the positive side: an index past the end removes nothing, but a far-negative one **clamps to the start** and removes the first element — `Arr::slice` clamps identically. Both edges are therefore inherited rather than redefined, and a data-provider test asserts parity with `array_splice` across the full index/length matrix on both a list and a string-keyed map.

- **`Num::toStr`** — the exact string form of a number, and the inverse of `Num::parseFloat`: `parseFloat(toStr($f)) === $f` for **every finite float**. The `(string)` cast does not round-trip, because it goes through `precision` (14 significant digits) and silently collapses distinct values — measured over ten boundary values, **six** fail, including `0.1 + 0.2` (casts to `0.3`), `1 / 3`, `PHP_FLOAT_EPSILON`, `PHP_FLOAT_MIN` and `PHP_FLOAT_MAX`. The exact form needed `var_export($f, true)` under `serialize_precision = -1`, a surprising thing to reach for. Integral floats keep their marker (`1.0`, where the cast gives `1`) and `-0.0` keeps its sign — pinned by a dedicated test, since `-0.0 === 0.0` is `true` and the round-trip assertion alone cannot see a lost sign. Ints and `BcMath\Number`s are already exact under a cast and pass through, a `Number` keeping its trailing zeros. **Non-finite floats throw `InvalidArgumentException`:** no string form of `NAN` / `INF` / `-INF` reads back through `parseFloat`, so there is no round-trippable answer to return, and the contract stays unqualified. Guard with `Num::isFinite()` when the input may be one. Not to be confused with `Filter::toStr`, the lenient `mixed` coercer for untrusted input — the shared name is all they have in common, and both docblocks now say so.

- **Documented how to check a value against a class name you cannot annotate** (`docs/type.md`) — no new API, and that is the point. `Type::isInstance()` / `isA()` declare `@param class-string<T>`, so PHPStan rejects a name that arrives as a runtime string (a type discriminator from configuration, a constructor argument). The supported way in was already shipped and simply undocumented: `Type::isClassName($n) || Type::isInterfaceName($n)` narrows the string to `class-string` — both predicates carry `@phpstan-assert-if-true class-string` — after which `isInstance()` type-checks. The composition covers class, interface **and** enum (an enum is a class). Documented alongside the trap that makes the single-predicate form look right: `isClassName` alone is `class_exists()`, which is false for an interface, so it type-checks perfectly while silently rejecting every interface. A new `tests/StaticAnalysis/TypeNarrowing.php` pins the narrowing, which is otherwise invisible — the guard keeps working at runtime even if the annotations regress.

### Changed

- **`Arr::search()` now carries the array's own key type.** `@template K of array-key` / `@return K`, so over a `list` the result is `int<0, max>` where it used to be `int|string` — **more precise than the `array_search()` it replaces**, which adds `|false` for the miss that this method turns into an exception. It closes the oddity of a helper being *less* precise than the native it wraps. **This one is not PHPDoc-only, and the trade is worth stating:** the body now calls `array_keys($array, $value, $strict)` instead of `array_search()`, because the latter's stub yields `int|string|false` over `array<K, mixed>` — it does not propagate the key template, so no annotation can make a body built on it prove `K`. `array_keys()` returns `list<K>` and does, and it is still one native call, so the comparison semantics are the native's own. The cost is that it has no early exit and materialises a key for *every* match where only the first is used, which flattens the profile: measured over 10,000 elements, ~76 µs against `array_search()`'s 31 µs when the match is last (2.5×) but ~83 µs against its 2.4 µs when the match is first (35×), and ~142 µs when every element matches. For the short lists this helper is typically pointed at that is immaterial; for scanning large arrays in a hot path, `array_search()` directly is still the right call. Behaviour is unchanged — the comparison is `===` / `==` exactly as `$strict` selects, verified identical to the native across 24 comparisons covering `'0'` vs `0`, `null` vs `''`, `'1e1'` vs `'10'` and array values.
- **`Arr::searchOrNull()` still hands callers the wider `int|string|null`.** Its result is intrinsically "key or null", and PHPStan does not resolve a template inside a union with `null` — not as a plain return, and not inside a conditional branch. The one spelling that does resolve, `($array is non-empty-array ? K : null)`, is false: a non-empty array may not contain the value, and claiming otherwise would make callers' `=== null` checks look like dead code. So the signature is spelled `@return null|K` to state the intent (and to pick up the precision for free should PHPStan ever resolve it), while the analyser silently degrades it to `int|string|null` — the docblock says so in as many words, and a `tests/StaticAnalysis` fixture pins the degraded type on purpose so nobody "fixes" it into the spelling that type-checks by lying.
- **`Arr::firstKeyOrNull()` and `Arr::lastKeyOrNull()` now carry the array's own key type.** PHPDoc only — the bodies are untouched, so there is no runtime change. Both declared `@return null|K` and **silently got `int|string|null`**: a template bounded by `array-key` collapses into the surrounding null-union and loses `K`, so over a `list` callers saw `int|string|null` where the honest answer is `int<0, max>|null`. They now declare `@return ($array is non-empty-array ? K : null)`, which also **drops the `null` arm outright for a statically non-empty array** — fed a `non-empty-list`, the result is plain `int<0, max>` and the caller's `=== null` check disappears rather than becoming unreachable. Note the contrast with `Arr::searchOrNull()` above, which is the *same* defect with the opposite verdict: the deciding question is not whether the conditional type-checks (it does in both cases) but whether it is **true**. A non-empty array always has a first and a last key; it may perfectly well not contain the value you searched for. New `tests/StaticAnalysis` fixtures pin all six results — the four here plus the two bare halves, which were already correct — and were verified to fail when the old annotation is put back. One test-side consequence, worth knowing before it surprises someone: `assertNull(Arr::firstKeyOrNull([]))` over a *literal* `[]` is now statically folded to `null` and PHPStan reports it as always true, so those two assertions go through a variable, the same idiom `testFirstOrNullReturnsNullOnEmpty()` already used.
- **`Num::sum()`, `product()`, `min()` and `max()` now preserve `int` through a conditional return type.** PHPDoc only — the bodies are untouched. All four declared a flat `float|int|Number`, so an all-int input lost its int-ness and could not be returned from an `int`-typed method; they now declare `@return ($values is iterable<int> ? int : float|int|Number)`, following the precedent set in 4.1.0 by the list-preserving conditionals across `Arr`. The key type is irrelevant (`iterable<int>` binds the value only) and a lazy source qualifies exactly as an array does. `product()` was not named in the roadmap but is `sum()`'s direct sibling — same loop, same `Number` widening, same conditional — and splitting the pair would have been an asymmetry with no reason behind it. **The caveat, stated because the type cannot:** on `sum()` / `product()` the int branch holds only up to `PHP_INT_MAX`, past which PHP promotes to float. That is knowingly accepted — it is the identical claim PHPStan's own stub makes for `array_sum()`, and refusing it would leave the helper *less* precise than the native it replaces, the exact oddity this line of work exists to remove. Unlike the conditional rejected on `Arr::searchOrNull()` in this same release, it makes no caller's code look unreachable; it only types as `int` a result that overflow could widen. `min()` / `max()` carry no caveat at all — they return one of the elements rather than computing over them. Documented on all four docblocks, in `docs/num.md`, and pinned by a new `tests/StaticAnalysis/NumNarrowing.php` covering both branches, verified to fail when the conditionals are removed.
- **`Arr::count()` and `Iter::count()` now declare `@return int<0, max>`.** PHPDoc only — no runtime change, no behaviour change, and every existing call site keeps working. Both return a count that is never negative but declared a plain `int`, which made them unusable inside a `Countable::count()` implementation — PHPStan analyses that method as returning `int<0, max>`, and a plain `int` does not satisfy it. That is the single most common place a consumer would reach for them. New `tests/StaticAnalysis` fixtures pin all four ranges (the two counts plus the new `keyPosition` pair), since a reverted annotation leaves the whole runtime suite green.

### Fixed

- **`Iter::flatMap` no longer swallows a bad callback result.** A callback returning a non-iterable produced a PHP warning and the element was **silently dropped** — `foreach` over a non-iterable warns and skips rather than raising a TypeError, so the helper returned short data instead of failing. Both `flatMap`s now throw `UnexpectedValueException` with the offending type, following `Arr::keyBy`'s precedent for a bad callback result. On `Iter::flatMap` the throw surfaces while the generator is **consumed**, not when it is called. Note the deliberate divergence from the library's usual rule of trusting a declared callback type (as `Arr::groupBy` does): PHPStan reports the guard as unreachable — correctly, since the type is PHPDoc on a public API and PHP does not enforce it — so it is kept behind a justified `@phpstan-ignore`, because the failure it prevents is silent data loss rather than a loud error.

## [4.4.0] - 2026-07-25

### Added

- **`Enum::fromValue` / `Enum::tryFromValue`** — lookup by **backed value**, the counterpart of `fromName` / `tryFromName`. The native `from()` / `tryFrom()` are strictly typed and reject `'2'` against an int-backed enum (and `2` against a string-backed one) — exactly the shape scalars arrive in from query strings, form posts and JSON. These coerce the scalar to the enum's backing type first, then delegate. Accepts an `int` or a `string`; for an int-backed enum a string goes through `Num::parseIntOrNull`, strictly (surrounding whitespace rejected, matching `Num::is`). `tryFromValue` is total — a pure enum and an enum with no cases both yield `null`, so it chains into `tryFromName` as a fallback with no prior guard — while `fromValue` distinguishes the failures: `InvalidArgumentException` for a class that is not a backed enum, `OutOfBoundsException` for a value no case carries. New `tests/StaticAnalysis` fixtures pin the `@template T` resolution under `composer phpstan`.
- **`Dt::fromInterface`** — normalises any `DateTimeInterface` to `DateTimeImmutable`, the entry point for a date-time arriving from outside (a mutable `DateTime` included), since dates in this library are always immutable. An already-immutable instance is returned **as-is**, where the native `DateTimeImmutable::createFromInterface()` always allocates a new object.
- **`Dt::toEpoch` / `Dt::toEpochFloat`** — the inverse of `fromEpoch`, which had no return path. `toEpoch` gives whole seconds (sub-second component dropped); `toEpochFloat` keeps the microsecond fraction. `toEpochFloat` is built from the whole seconds plus the microseconds read separately, never from `format('U.u')`: that pattern glues a negative seconds part to the always-positive microsecond fraction, so a pre-epoch instant comes out skewed by up to a second (`-0.5s` renders as `-1.5`). The trap is pinned by a test.

## [4.3.0] - 2026-07-25

### Added

- **`Enum::intOrNull` / `Enum::strOrNull`** — value-extracting complements of `isInt` / `isStr`, both signed `(UnitEnum $case)`. `intOrNull` returns the case's backing when it is an `int` (else `null`); `strOrNull` returns it when it is a `string` (else `null`). Unlike the `BackedEnum`-typed predicates — which only *narrow* — these take the broad `UnitEnum` and *read* the value directly, so a pure-enum case or a wrong-typed backing collapses to `null` with no prior `isBacked` guard: a total, throw-free read (return type `?int` / `?string`). New `tests/StaticAnalysis` fixtures pin the `int|null` / `string|null` return types under `composer phpstan`.

## [4.2.1] - 2026-07-18

Test-quality release: Infection mutation testing is wired up and gated at **100% covered MSI**. No behaviour change.

### Added

- **Infection mutation-testing gate** — `infection/infection` as a dev dependency, `infection.json5.dist` gating **`minCoveredMsi: 100`** (`minMsi` is deliberately absent: the deliberately-uncovered lines' mutants cannot be killed — see the roadmap's coverage decision), `composer infection` / `composer infection-diff` scripts, and a covered-MSI badge in the README.
- **Differential CI gate** — because the full run is ~27 min, CI mutates only the changed lines on pull requests (`composer infection -- --git-diff-lines --git-diff-base=origin/master --ignore-msi-with-no-mutations`, floor job, blocking), keeping the full run off the PR/push path and behind a manual `workflow_dispatch` trigger. Locally, `composer infection-diff` mutates just your uncommitted changes against `master`.
- **80 new tests** killing the initial run's 362 escaped mutants. Highlights: multibyte inputs across `Str` (`capitalize`, `sub`, `indexOf`, `trunc`, `mask`, `translate`, `replaceAt`); `BcMath\Number` × float widening across `Num` arithmetic (a float operand throws `TypeError` if the widening branch is bypassed, so every arm is pinned); boundary values (`clamp` with `min == max`, `floor`/`ceil` at precision ±2, `chunk`/`drop`/`slice`/`nth` at their limits, `chr` at 0 and 0x10FFFF, `wordWrap` at the default width 75, `json_decode` at nesting depth 511/512); exception-message pinning wherever a bypassed guard would surface as a different message from a deeper layer; key preservation for `Iter::take`/`drop`/`slice`/`unique`; and deterministic structure asserts for `Rand` (UUID version/variant nibble independence over 40 draws, Fisher-Yates movement of first/last positions over 30 draws, millisecond-accurate `uuidV7Time` round-trip windows).

### Changed

- **Provably-equivalent mutants are suppressed in-code** with narrowly-anchored `@infection-ignore-all` comments (71 across 14 classes), each stating its equivalence argument — operator-overload fallbacks (`Number` ↔ int), guards natives already enforce (`is_a` on mixed, `similar_text` always writing its ref), `10 ** 0 = 1` identity fallthroughs, refcount-closed file handles, umask-masked `mkdir` modes, distribution-only randomness shifts, and platform-dependent iconv transliteration (libiconv renders `é` as `'e`, so slug tests assert shape, not exact output).
- **Behaviour-neutral simplifications** that eliminate whole mutant classes instead of suppressing them: the six `Dt::add*` sign-prefix ternaries collapse to `sprintf('%+d', …)`; `Rand`'s UUID formatter takes the rest-of-string `Str::sub($hex, 20)` so an oversized byte array now breaks the 8-4-4-4-12 shape (making extra-`random_bytes` mutants detectable); `Path::basename` drops the `$suffix !== $base` guard subsumed by its strict length check; `Num::expandScientific` passes `ignoreCase:` as a named argument; two redundant `(float)` casts in `Num::floor`/`ceil` removed.

## [4.2.0] - 2026-07-17

### Added

- **`Enum::isInt` / `Enum::isStr`** — value-type predicates on a case already known to be backed, the `BackedEnum`-typed counterparts of `isBackedInt` / `isBackedStr`. The `BackedEnum` parameter makes the property assert well-formed, so `$case->value` narrows exactly in **both** branches (`isInt`: `int` when true, `string` when false; `isStr` mirrors) — the narrowing the `UnitEnum`-typed pair cannot express, since asserting on `$case->value` is rejected when the parameter type lacks the property.

### Changed

- **Assert annotations for `Arr::isList` and `Enum::isBackedInt` / `isBackedStr`** (PHPDoc-only, BC-safe). `Arr::isList` now declares `@phpstan-assert-if-true list<mixed> $value`, so a guarded `foreach` narrows on `isList` alone without pairing it with `Arr::is`. `Enum::isBackedInt` / `isBackedStr` replace the malformed compound `@phpstan-assert-if-true BackedEnum $case && … $case->value` (PHPStan silently dropped the `$case->value` clause) with the well-formed `@phpstan-assert-if-true BackedEnum $case`, so the true branch narrows a `UnitEnum` to `BackedEnum` and makes `$case->value` readable. PHPStan has no int- vs string-backed enum type, so calling either predicate on a subject already typed `BackedEnum` still reports "always evaluates to true" — a PHPStan limitation, unchanged by this cleanup. New `tests/StaticAnalysis` fixtures pin the narrowing under `composer phpstan` (a reverted annotation leaves the runtime suite green but breaks these). No runtime behaviour change.

## [4.1.0] - 2026-07-15

### Changed

- **List-preserving conditional return types across `Arr`** — passing a `list` no longer erases list-ness under PHPStan. `map`, `reverse`, `slice`, `append`, `prepend`, `shift` / `shiftOrNull`, `pop` / `popOrNull` (the remainder half), and `sortKeys` now declare conditional return types (e.g. `($array is list<T> ? list<TResult> : array<K, TResult>)`), following the precedent of `Arr::pluck` / `Iter::toArray`. `reverse` / `slice` narrow only under the default `$preserveKeys = false`; `sortKeys` only ascending. Downstream code no longer needs a localized `@var` to restate list-ness (first real hits: rak200/http-input's `Validator::messages()` / `Result::messages()`). PHPDoc-only — no runtime behaviour change. Audited exclusion: `merge` preserves list-ness at runtime, but PHPStan cannot evaluate conditional return types over a variadic parameter — re-index with `Arr::values()` where the narrowed type matters.

## [4.0.0] - 2026-07-11

Exception semantics release: every throw-site now uses the precise SPL exception for its failure kind.

### Changed

- **Breaking: invalid-input throws are now `InvalidArgumentException`** (extends `LogicException`, so a `catch (RuntimeException)` no longer captures them) — malformed input and out-of-domain arguments across the library: parse failures (`Num::parseInt`/`parseFloat`/`parseNumber`, `Dt::parse`, `Url::parse`, `Rand::uuidV7Time`/`ulidTime`), malformed encodings (`Base64`/`Hex`/`Bit` decode, invalid UTF-8, invalid regex patterns), and argument-domain guards (`must be non-negative`, `cannot be empty`, base/bit-index ranges, division/modulo by zero, negative square roots, mismatched lengths)
- **Lookup misses now throw `OutOfBoundsException`** (extends `RuntimeException` — existing catches keep working): `Arr::find`/`get`/`getKey`/`search`, `Iter::find`/`nth`, `Enum::fromName`, `Regex::match`, `Arr::pluck`/`keyBy` on a missing column
- **Operations on an empty source now throw `UnderflowException`** (extends `RuntimeException`): `Arr::first`/`last`/`firstKey`/`lastKey`/`shift`/`pop`, `Iter::first`/`last`, `Num::min`/`max`/`avg`, `Dt::min`/`max`, `Rand::choice`, `Enum::random`, `Str::ord` on `''`
- **A user callback returning an unusable value now throws `UnexpectedValueException`** (extends `RuntimeException`): `Arr::keyBy` resolved-key type
- `RuntimeException` remains only for environment/native failures (`File::*`, fileinfo, temp files)
- `Num::parseNumber` / `Num::parseNumberOrNull` widened to accept `float|int|string|Number`: a `Number` passes through, an int converts directly, a finite float is expanded to its exact decimal form (so a value whose string form is scientific notation, e.g. `1.0E-7`, converts cleanly), and non-finite floats (`NAN`, `INF`) throw `InvalidArgumentException` / return `null`

## [3.1.0] - 2026-07-11

### Added

- **`Iter::isEmpty` / `Iter::isNotEmpty`** — lazy emptiness probes, the `Iter` counterparts of `Arr::isEmpty` / `Arr::isNotEmpty`. Both examine at most one element (safe on infinite sources), but probing advances the source: a `Generator` has its first element consumed.

## [3.0.0] - 2026-07-09

First major since 2.0.0 — two staged breaking changes land, plus symmetric literal-key getters.

### Added

- **`Arr::getKey` / `Arr::getKeyOrNull`** — literal-key reads that never split on dots (a key containing a dot is read as-is), the value-returning counterparts to `Arr::hasKey`. `getKey` throws when the key is absent; `getKeyOrNull` returns `null`. A present `null` value is returned, not treated as missing.

### Changed

- **`Arr::has` / `get` / `getOrNull` are now pure dot-path lookups.** The transitional *literal-first* fallback (shipped 2.2.0) is removed: a string `$path` always traverses on `.`, and an int or dotless string is a single-segment lookup — aligning the read side with the already-pure-dot-path `set` / `forget`. **BC break:** a key that literally contains a dot (`['a.b' => 1]`) is no longer matched by `has` / `get` — it is traversed (`a` → `b`). Migrate literal-key access to `hasKey` (existence) and the new `getKey` / `getKeyOrNull` (reads).

### Removed

- **`Type::isNumericStr` and `Type::isIntLike`** — `@deprecated` since 2.1.0. Replace `isNumericStr($v)` with `Type::isStr($v) && Type::isNumeric($v)`, and `isIntLike($v)` with `Type::isInt($v) || (Type::isStr($v) && Regex::matches('/^[+-]?\d+$/', $v))`.

## [2.4.0] - 2026-06-28

Strictness alignment between the `Arr`/`Iter` twins, plus `Iter` correctness fixes.

### Changed

- **`Arr::unique` now uses strict comparison** (was loose `SORT_REGULAR`). Values of different types no longer collapse — `Arr::unique([1, '1'])` is `[1, '1']`, not `[1]`. This aligns it with `Iter::unique` (already strict): both twins now deduplicate identically.

### Fixed

- **`Iter::zip`** no longer advances the longer sources one element past the shortest. It stops as soon as a source is exhausted without pulling an extra element from the others — so a source whose next element has a side effect or throws is no longer touched beyond the shortest input.
- **`Iter::range`** with a bounded `$end` near `PHP_INT_MAX` / `PHP_INT_MIN` no longer overflows into an infinite loop yielding floats; it now terminates correctly at the bound.
- **Docs:** the "lazy transforms preserve keys" summary now names the re-indexing transforms (`flatMap`, `flatten`, `values`, `zip`, `chunk`), and the infinite-source caveats for the eager terminals and for `slice` without a length are documented.

## [2.3.0] - 2026-06-07

Adds `Iter`, the lazy counterpart of `Arr`.

### Added

- **`Iter`** — a Tier 1 class of lazy iterable helpers operating on `array|Traversable` and returning `Generator`, so transforms compose without materialising intermediate arrays and sources may be infinite. It reads as the eager/lazy twin of `Arr`.
  - **Sources:** `range` (unbounded when `$end` is null), `repeat`, `cycle`, `iterate`, `times`.
  - **Lazy transforms:** `map`, `filter`, `flatMap`, `take`, `drop`, `takeWhile`, `dropWhile`, `chunk`, `flatten`, `zip`, `concat`, `keys`, `values`, `unique`, `slice`, `tap`. Transforms preserve keys.
  - **Terminals:** `first` / `last` / `find` / `nth` (each with an `*OrNull` variant), `reduce`, `count`, `contains`, `any`, `every`, and `toArray(bool $preserveKeys = false)` (re-indexes by default).
  - **Single-pass contract:** a `Generator` is consumed once — passing the same one to two terminals throws `Cannot traverse an already closed generator`; re-derive the pipeline from its source.
  - Two deliberate divergences from `Arr`: `Iter::zip` stops at the **shortest** input (vs `Arr::zip` padding to the longest), and `Iter::flatten` descends into **any** nested iterable, not only arrays.

## [2.2.0] - 2026-06-07

Roadmap sweep of the additive helpers queued for the existing classes.

### Added

- **`Str::mask(value, start = 0, length = null, mask = '*', keep = '')`** — masks part of a string for safe PII display. Within the window (negative `$start` / `$length` like `Str::replaceAt`), every character not in `$keep` becomes the first character of `$mask`, preserving length. `$keep` passes formatting separators through, so a Brazilian CPF masks to `***.***.***-09` in one call; non-contiguous patterns compose two calls.
- **`Num::add` / `sub` / `mul` / `div`** — the four basic operations over `int|float|BcMath\Number`, widening to `Number` when any operand is one (the previously-private `add` / `multiply` are now public, joined by `sub` / `div`). `div` follows PHP's `/` and throws on a zero divisor.
- **`Num::lerp(a, b, t)` / `Num::remap(value, inMin, inMax, outMin, outMax)`** — linear interpolation and range re-mapping; `Number`-aware, no clamping (compose with `Num::clamp`). `remap` throws when the input range is empty.
- **`Dt::period(start, end, step = P1D, inclusive = true)`** — a lazy `Generator<int, DateTimeImmutable>` walking a date range by a `DateInterval` (ascending; throws on a non-advancing step).
- **`Dt::isPast` / `Dt::isFuture`** — strict comparison of a date/time against the current instant.
- **`Arr::get` / `getOrNull` / `set` / `forget` / `dot` / `undot`** — nested dot-path access (`'a.b.c'`). `get` bare throws / `getOrNull` returns `null`; `set` / `forget` return a new array (immutable); `dot` flattens nested → dot-keyed, `undot` is the inverse.
- **`Arr::hasKey(array, key)`** — the literal-key check (the pre-dot-path behaviour of `Arr::has`), stable across the planned 3.0.0 change.
- **`Rand::isUuid(value, version = null)` / `Rand::isUlid(value)`** — validate UUID (canonical 8-4-4-4-12 hex, optional version + RFC 4122 variant) and ULID (26-char Crockford Base32) strings.
- **`Rand::uuidV7Time` / `uuidV7TimeOrNull` / `ulidTime` / `ulidTimeOrNull`** — extract the embedded millisecond timestamp from a UUID v7 / ULID as a `DateTimeImmutable`.

### Changed

- **`Arr::has` now resolves a dot-path** `'a.b.c'`, checking the literal key first (so existing checks — including keys that contain a dot — keep working), then traversing. `Arr::get` / `getOrNull` follow the same literal-first rule. Backward-compatible.

### Deprecated

- **The literal-first fallback of `Arr::has` / `get` / `getOrNull`** for keys that contain a dot — transitional. In 3.0.0 these become pure dot-path lookups; use `Arr::hasKey` for a literal-key check that stays stable.

## [2.1.0] - 2026-06-04

### Deprecated

- **`Type::isNumericStr` / `Type::isIntLike`** — both `@deprecated since 2.1.0`, to be removed in 3.0.0. They still work unchanged for now. Replace `isNumericStr($v)` with `Type::isStr($v) && Type::isNumeric($v)` (`Num::is` already requires a strict numeric string — no surrounding whitespace), and `isIntLike($v)` with `Type::isInt($v) || (Type::isStr($v) && Regex::matches('/^[+-]?\d+$/', $v))`.

## [2.0.0] - 2026-06-03

First major release since 1.0.0. **Breaking** — every `@deprecated` alias accumulated across the 1.x line is removed, and one default is flipped. Each removed name has a drop-in canonical replacement (listed below), so migration is a mechanical rename.

### Removed

All methods previously marked `@deprecated ... will be removed in 2.0.0` are gone. Replace each with its canonical name:

- **`Str`** — `length` → `len`, `byteLength` → `byteLen`, `substring` → `sub`, `truncate` → `trunc`, `toCamelCase` → `toCamel`, `toPascalCase` → `toPascal`, `toSnakeCase` → `toSnake`, `toKebabCase` → `toKebab`, and the redundant `mixed` guard `isNonEmptyStr` (use a typed `string`, or `Str::is($v) && $v !== ''`).
- **`Num`** — `isInteger` → `isInt`, `isNumeric` → `is`, and the int-only guards `isPositiveInt` / `isNegativeInt` / `isNonNegativeInt` (use `isPositive` / `isNegative` for int/float/`Number`, or `Num::isInt($v) && $v >= 0`).
- **`Arr`** — `isNonEmptyArray` (use a typed `array` with `isNotEmpty`, or `Arr::is($v) && $v !== []`).
- **`Dt`** — `diffInDays` → `diffDays`, `diffInSeconds` → `diffSeconds`, `diffInMinutes` → `diffMinutes`, `diffInHours` → `diffHours`.
- **`File`** — `isDirectory` → `isDir`, `extension` → `ext`, `mimeType` → `mime`, `tempFile` → `temp`.
- **`Path`** — `extension` → `ext`.
- **`Filter`** — `collapseWhitespace` → `squish`, `removeControlChars` → `stripControl`, `toString` → `toStr`.
- **`Json`** — `isValid` → `is`.
- **`Type`** — `isInstanceOf` → `isInstance`, `isSubclassOf` → `isSubclass`.
- **`Hash`** — `verifyPassword` → `verify`.

### Changed

- **`Str::join` is now a plain `implode()`-style join.** The signature drops to `join(iterable $items, string $separator = '')` — the `$prefix` / `$suffix` / `$lastSeparator` / `$skipBlanks` parameters are removed (and with `$skipBlanks` goes the deprecated blank-dropping default and its `E_USER_DEPRECATED`). For dropping blank items, prefix/suffix wrapping, or an Oxford-style final separator, use `Str::joinNatural()` (unchanged).

## [1.16.0] - 2026-06-02

### Added

- **`Arr::pluck` gains an optional `$indexKey`** — the three-argument form of `array_column`: `Arr::pluck($rows, 'name', 'id')` returns `['id' => 'name', ...]`, keying each plucked value by that column of the same item (following `Arr::keyBy` — the item must hold `$indexKey`, later collisions overwrite, otherwise it throws). The no-`$indexKey` call is unchanged (0-indexed list). The other `array_column` shapes already map to existing helpers: extract-a-column is `Arr::pluck($rows, $col)`, and rows-keyed-by-a-column is `Arr::keyBy($rows, $col)`.

## [1.15.1] - 2026-06-02

Type-correctness pass — the codebase is now clean under `phpstan analyse` at **level max**. No runtime behaviour or public-API change.

### Changed

- **`Arr::diff` / `Arr::intersect`** — value type narrowed to `@template T of int|string`, matching the string-cast contract of `array_diff` / `array_intersect` (and the existing `Arr::countValues` convention). Phpdoc only.
- **`Type::isSubclassOf`** (deprecated alias) — now carries the `@template`/`class-string` and `@phpstan-assert-if-true` annotations so it type-checks while forwarding to `Type::isSubclass`.
- **`Rand::formatUuid`** — `@param` relaxed from `list<int>` to `int[]` (it only needs ordered int byte values; `Hex::fromBytes` accepts `array<int>`), so the post-mask `$bytes[6]`/`$bytes[8]` writes no longer trip the `list` shape.

### Fixed

- **`Regex::grep` / `Str::scan`** — annotate the genuinely-known result type that PHPStan's value-type-erasing `preg_grep` / `sscanf` stubs (`'array|false'` / `'array|int|null'`) drop. Runtime behaviour unchanged.
- **Test suite type-correctness** — `ArrTest` asserts the array returned by the immutable `Arr::append` (instead of an unused pure call); `Type` predicate tests exercise the canonical `isInstance` / `isSubclass` (with the deprecated-alias forwarding folded into the same data-provider cases) and the narrowing fixture uses canonical `isInstance`.

## [1.15.0] - 2026-06-02

### Added

- **`Arr::shift` / `Arr::shiftOrNull` and `Arr::pop` / `Arr::popOrNull`** — immutable counterparts to `array_shift` / `array_pop`. Each returns a `[element, rest]` pair (the first/last element plus the remainder) without mutating the input; the remainder follows `Arr::slice` semantics (integer keys renumbered, string keys kept). Bare throws `RuntimeException` on an empty array; the `*OrNull` variant returns `null`. They fill the one immutable case that couldn't be expressed in a single existing call — for just one half, `Arr::first` / `last` (element) or `Arr::slice` (remainder) still apply.

## [1.14.1] - 2026-06-02

Documentation only — no library code or API change.

### Changed

- **Clarified the `CLAUDE.md` roadmap's deferred section.** Split it into *Contingent* (the additive `Math` class — ships in a minor when there's demand, **not** gated on 2.0.0) and *Out of scope (by design)* (the mutable/pointer/in-place natives and global/impure/low-level functions — deliberate exclusions that contradict the pure/immutable contract, with the immutable equivalents that already cover the useful cases noted). No behaviour or API change; 2.0.0 stays scoped to removals + the `Str::join` default flip.

## [1.14.0] - 2026-06-02

Brings forward the 2.0.0 roadmap work in a **backwards-compatible** way: shorter method names, a full prefer-lib-over-native sweep, and two new helper pairs. Every rename ships a `@deprecated` alias, so nothing breaks in 1.x — the removals stay queued for 2.0.0.

### Added

- **`Str::toBytes(string): list<int>` / `Str::fromBytes(list<int>): string`** — convert between a binary string and its byte values (0–255), the raw-string counterpart to `Hex::toBytes` / `fromBytes`. `fromBytes` throws on a value outside 0–255.
- **`Num::isPositive` / `Num::isNegative`** — sign predicates for `int|float|Number` (`> 0` / `< 0`; zero is neither), generalising the deprecated int-only `isPositiveInt` / `isNegativeInt`.
- **Shorter canonical names** (each with a `@deprecated` alias under the old name): `Str::len` (was `length`), `Str::byteLen` (`byteLength`), `Str::sub` (`substring`), `Str::trunc` (`truncate`); `Num` — n/a; `Dt::diffDays` / `diffSeconds` / `diffMinutes` / `diffHours` (`diffIn*`); `File::mime` (`mimeType`), `File::temp` (`tempFile`), `File::ext` (`extension`); `Path::ext` (`extension`); `Filter::squish` (`collapseWhitespace`), `Filter::stripControl` (`removeControlChars`), `Filter::toStr` (`toString`); `Hash::verify` (`verifyPassword`); `Type::isInstance` (`isInstanceOf`), `Type::isSubclass` (`isSubclassOf`).

### Changed

- **Prefer-lib-over-native sweep completed (internal; no behaviour change).** The intra-codebase natives that the 1.13.0 helpers made replaceable now route through the lib: `Str` array ops (`array_combine`/`array_reverse`/`array_map`/`array_filter`/`array_values`/`count` → `Arr::*`), `Path` (`array_slice`→`Arr::slice`, `count`→`Arr::count`, `ctype_alpha`→`Str::isAlpha`, `preg_match`→`Regex::matchOrNull`), `Type::isIntLike` (`preg_match`→`Regex::matches`), and `Rand` (array ops → `Arr::*`).
- **`Rand`'s byte/crypto carve-out lifted to just `random_int` / `random_bytes` / `microtime`.** UUID/ULID generation now works in the `int[]` domain via the new `Str::toBytes` / `fromBytes`, with `Hex::encode` and `Bit::toStr` / `fromStr` for the hex/base conversions.

### Deprecated

- The pre-rename names listed under **Added** (`length`, `byteLength`, `substring`, `truncate`, `diffIn*`, `mimeType`, `tempFile`, `extension`, `collapseWhitespace`, `removeControlChars`, `toString`, `verifyPassword`, `isInstanceOf`, `isSubclassOf`) — all `@deprecated since 1.14.0`, removed in 2.0.0.
- **`Str::isNonEmptyStr`, `Arr::isNonEmptyArray`, `Num::isPositiveInt` / `isNegativeInt` / `isNonNegativeInt`** — redundant under strict typing. Use a typed parameter (or `Str::is`/`Arr::is` guards), and `Num::isPositive` / `isNegative` for the sign checks. Note `!isNegative()` is **not** equivalent to `isNonNegativeInt` (it is true for non-ints; the exact replacement is `Num::isInt($v) && $v >= 0`). Removed in 2.0.0.

## [1.13.2] - 2026-06-01

Convention-conformance pass (internal; no behaviour change) — fixes the violations surfaced by auditing the codebase against the conventions adopted in 1.13.1.

### Changed

- **`use function` inventory corrected** — `Str` now imports `min` (used by `replaceAt`, previously resolved via the global fallback), and `Type` drops the now-unused `is_array` / `is_float` imports (it delegates to `Arr::is` / `Num::isFloat`).
- **`Dt::diffInMinutes` / `Dt::diffInHours` use `Num::intDiv`** instead of the native `intdiv`, matching `Dt::fromEpochMs` and the prefer-lib-over-native rule; `Dt` no longer imports `intdiv`.
- **`Arr::zip` passes its callbacks with first-class syntax** (`count(...)` / `array_values(...)`) instead of string callables, per the new convention.

## [1.13.1] - 2026-06-01

Documentation only — no library code or API change.

### Changed

- **Documented four API conventions in `CLAUDE.md`** ahead of the planned 2.0.0 pass: (1) the prefer-lib-over-native rule now spells out a third carve-out — a helper that *wraps* a native keeps it (`Str::byteLength`→`strlen`, `Str::length`→`mb_strlen`); (2) every native a class still calls must be declared in its `use function` block (the auditable inventory of deliberately-kept natives); (3) callables are passed with first-class syntax `func(...)` / `self::method(...)`, never as strings; (4) naming favours the shortest name that stays unambiguous and discoverable, with an explicit precedence order (conventions/families > consolidated synonym > intuitive abbreviation > redundancy removal) and a `@deprecated`-alias policy for breaking renames. These guide the 2.0.0 work; no public method is renamed in 1.x.

## [1.13.0] - 2026-06-01

Roadmap sweep: wraps the native-function gaps catalogued in the roadmap as pure, static helpers across `Str`, `Arr`, `Num`, `Regex`, `Bit`, `Dt`, and `File`.

### Added

- **`Str`** — `byteLength` (raw byte length, the byte-level counterpart to the character-counting `length`); `title` (title-case each word, via `mb_convert_case`); `isDigits` / `isAlpha` / `isAlnum` (ASCII `ctype_*` predicates, false for the empty string); `before` / `after` (slice around the first occurrence of a delimiter); `replaceAt(value, start, length, replacement)` (multibyte-aware ranged replace, with negative `$start` / `$length` and length-0 insert); `wordWrap` (column wrap; throws on `$width < 1`); `wordCount` (Unicode-aware word count); `format` (printf-style, `vsprintf`) and `scan` (the inverse, `sscanf`); `levenshtein` (edit distance) and `similarity` (`similar_text` percentage).
- **`Arr`** — `count`; `reverse`; `slice`; `flip`; `combine` (throws on length mismatch); `diff` / `intersect` (by value, keys preserved); `search` / `searchOrNull` (key of the first matching value); `countValues`; `append` / `prepend` (immutable add at either end); `firstKey` / `firstKeyOrNull` / `lastKey` / `lastKeyOrNull`; `sortKeys` (asc/desc, association preserved); `fill` (count + value → list) and `fillKeys` (keys + value → map).
- **`Num`** — `isNan` / `isInfinite` (the complements of `isFinite`); `product` (companion to `sum`, `1` for empty input); `toBase(int, base)` (render an `int` in base 2–36, the inverse of `parseInt`).
- **`Regex::grep(pattern, values, invert = false)`** — keep the array elements that match (or, inverted, that don't), preserving keys.
- **`Bit::rotateLeft` / `Bit::rotateRight`** — circular bit shift over the full `PHP_INT_SIZE * 8`-bit width; `$by` is taken modulo the bit width (so any integer, including negatives, is accepted) and the two are inverses.
- **`Dt::isValid(year, month, day)`** — true when the components form a valid Gregorian calendar date (`checkdate`).
- **`File`** — `touch` (set mtime / create empty file), `realpath` (canonicalise an existing path; throws when missing), and `readCsv` / `writeCsv` (CSV ↔ list of row arrays; `readCsv` skips blank lines; `$escape` defaults to `''`).

### Changed

- **`Str::replace` gained an `$ignoreCase` flag** — `Str::replace($subject, $search, $replacement, ignoreCase: true)` does a case-insensitive replace (via `str_ireplace`). Backward-compatible optional parameter.
- **`Str::split` gained a default `$separator = ''`** — `Str::split($value)` now splits into individual characters (the previous empty-separator behaviour, now the default). Backward-compatible.
- **Prefer-lib-over-native (internal; no behaviour change).** Now that `Str::byteLength` exists, the byte-level `strlen` calls in `Path` (`isAbsolute` / `relative` / `basename` / `driveOf`) and `Str::replaceFirst` / `replaceLast` use it instead of the raw native — `Path` no longer imports `strlen`. The `Rand` alphabet-length `strlen` stays as the documented cryptographic byte-operation carve-out.
- **Other internal cleanups (no behaviour change).** Error-message and format-string `sprintf(...)` calls across `Dt` / `File` / `Num` / `Path` / `Rand` / `Regex` / `Url` were replaced with string interpolation; `Json::is` now uses native `json_validate` (it no longer builds the decoded value); `Hex::decode` folds its two validity checks into one; and `Filter::toInt` routes through `Num::isFinite` / `Num::floor`.

## [1.12.0] - 2026-05-31

### Added

- **`Hex`** — new tier-2 class for hexadecimal encoding of binary strings (the byte-string ↔ hex-string counterpart to `Base64`): `encode` (lowercase, two digits per byte), `decode` (accepts upper- and lowercase; throws on odd length or a non-hex character), the `is` predicate, and `toBytes` / `fromBytes` to bridge hex and a `list<int>` of byte values (0–255).
- **`Str::joinNatural(items, separator = '', prefix = '', suffix = '', lastSeparator = null)`** — natural-language join: drops blank items, wraps the result with `$prefix` / `$suffix`, and joins the final two parts with an optional Oxford-style `$lastSeparator`. This is the previous behaviour of `Str::join`, now under its own name.

### Changed

- **`Str::join` gained a `$skipBlanks` flag and a default `$separator = ''`, and can now mirror `implode()` / concatenate.** `Str::join($items, skipBlanks: false)` is a plain `implode()`-style join with nothing dropped (omit the separator to concatenate); `$prefix` / `$suffix` / `$lastSeparator` still apply.

### Deprecated

- **`Str::join()` with the default `$skipBlanks = true`** (silently dropping blank items) — now emits an `E_USER_DEPRECATED` and will be removed in 2.0.0. Use `Str::joinNatural()` to keep that behaviour, or pass `skipBlanks: false`.

## [1.11.0] - 2026-05-31

### Added

- **`Str::ord` / `Str::chr`** — convert between a character and its Unicode code point (multibyte-aware). `ord` throws on an empty string or invalid UTF-8; `chr` throws outside `0`–`0x10FFFF`.
- **`Str::translate(string, string $from, string $to)`** — replace characters by position from a `$from`→`$to` map (multibyte-aware, single-pass); throws when the two strings differ in length.
- **`Str::span(string, string $chars, int $start = 0, ?int $length = null): int`** — length of the leading run of the string made up only of characters in `$chars` (byte-level, via `strspn`); equals the string length exactly when every character is in the set.
- **`Num::intDiv(int, int): int`** — integer division truncated toward zero, the companion to `Num::mod`; throws on a zero divisor.
- **`Num::isFinite(mixed): bool`** — true for a finite number (int, `Number`, finite float, or a numeric string whose float value is finite); `INF`, `-INF`, `NAN`, overflowing numeric strings, and non-numbers are false.
- **`Bit::toStr(int, int $width = 0): string` / `Bit::fromStr(string): int`** — convert between an `int` and its base-2 string, with optional left-padding to a fixed width. `fromStr` reads an unsigned binary string and throws on empty/invalid input or a value beyond `PHP_INT_MAX`.

### Changed

- **`Str::indexOf` / `Str::lastIndexOf` gained an `$ignoreCase` flag** for case-insensitive search; `lastIndexOf` also gained an `$offset` to bound the search. Backward-compatible optional parameters.
- **Prefer-lib-over-native sweep (internal; no behaviour change).** Pre-existing code now uses the library's own helpers instead of raw natives where the semantics match exactly: `Num::formatNumber` / `parseIntOrNull` (`substr` / `explode` / `str_pad` / `str_starts_with` / `str_contains` / `str_split` → `Str::*`), `Num::expandScientific` (`stripos` → `Str::indexOf(..., ignoreCase: true)`), `Base64` (`str_repeat` / `rtrim` → `Str::repeat` / `trimEnd`), `Path` (`str_replace` / `explode` / `ltrim` / `rtrim` / `str_contains` / `strtoupper` / `end` → `Str::*` / `Arr::last`), `Filter::collapseWhitespace` and `Str::slug` (`trim` → `Str::trim`), and `Dt::fromEpochMs` (`intdiv` → `Num::intDiv`). Deliberately byte-level natives are kept as-is — the `substr_replace`-based `Str::replaceFirst` / `replaceLast`, `Path` drive/segment slicing, and `Rand` cryptographic byte operations.

## [1.10.0] - 2026-05-30

### Added

- **`Filter`** — new tier-2 class for input sanitisation and lenient coercion of untrusted values. Every method is total (none throws). Two groups:
  - **Sanitisers** (`string → string`): `escapeHtml`/`unescapeHtml` (`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8), `stripTags`, character whitelists `digits`/`alpha`/`alnum` (Unicode-aware), `collapseWhitespace`, `removeControlChars` (`\p{Cc}`), `ascii` (best-effort `iconv` transliteration), and `email`/`url` (`FILTER_SANITIZE_*`).
  - **Coercers** (`mixed → typed`, with a caller-supplied default): `toString`, `toInt`, `toFloat`, `toBool` (HTML-form semantics — `"on"`/`"yes"`/`"1"` → true). Built for request data, where every value arrives as an untrusted string and a fallback is wanted rather than an exception. Distinct from the strict `Num::parse*` string parsers: `Filter::to*` accept `mixed`, trim, and reuse `Num::parseIntOrNull`/`parseFloatOrNull` internally.

### Changed

- **`Num::parseNumber` / `parseNumberOrNull` now accept scientific notation** (e.g. `1.5e3`, `2e-10`), matching the strings `Num::is()` already reports as numeric — previously `Num::is('1e3')` was `true` but `parseNumberOrNull('1e3')` returned `null`, since `BcMath\Number`'s constructor rejects exponents. Scientific input is expanded to its exact decimal form, so arbitrary precision is preserved (`1.5e-3` → `0.0015`). The guard also switched from raw `is_numeric` to the strict numeric-string check, so it lines up exactly with `Num::is()`. An exponent so large its decimal form is impractical (guarded at 65536 digits) still yields `null` / throws — well-formed but not representable.
- **`Enum::isBackedInt` / `isBackedStr` narrow the case value** — their `@phpstan-assert-if-true` now also asserts `int $case->value` / `string $case->value`, so PHPStan knows the backing type inside the guarded branch. No runtime change.

## [1.9.0] - 2026-05-30

### Added

- **`Enum::isBacked(mixed): bool`** — domain predicate, true when the value is a backed enum case (a `BackedEnum` instance, i.e. an enum declared with a `: int` or `: string` backing type). Pure cases and non-enum values return false. Carries `@phpstan-assert` PHPDoc; complements the existing `Enum::isBackedInt` / `Enum::isBackedStr`.

### Changed

- **Test suites use PHPUnit `#[DataProvider]` for the `is*` predicates.** Feeding cases through a `mixed`-typed provider parameter (instead of inline literal calls) stops PHPStan from constant-folding the guard calls, so the `ignoreErrors` block in `phpstan.neon.dist` could be removed entirely — the analysis now passes at level max with no suppressions. No production behaviour change.

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

[4.5.0]: https://github.com/rak200/utils/compare/4.4.0...4.5.0
[4.4.0]: https://github.com/rak200/utils/compare/4.3.0...4.4.0
[4.3.0]: https://github.com/rak200/utils/compare/4.2.1...4.3.0
[4.2.1]: https://github.com/rak200/utils/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/rak200/utils/compare/4.1.0...4.2.0
[4.1.0]: https://github.com/rak200/utils/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/rak200/utils/compare/3.1.0...4.0.0
[3.1.0]: https://github.com/rak200/utils/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/rak200/utils/compare/2.4.0...3.0.0
[2.4.0]: https://github.com/rak200/utils/compare/2.3.0...2.4.0
[2.3.0]: https://github.com/rak200/utils/compare/2.2.0...2.3.0
[2.2.0]: https://github.com/rak200/utils/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/rak200/utils/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/rak200/utils/compare/1.16.0...2.0.0
[1.16.0]: https://github.com/rak200/utils/compare/1.15.1...1.16.0
[1.15.1]: https://github.com/rak200/utils/compare/1.15.0...1.15.1
[1.15.0]: https://github.com/rak200/utils/compare/1.14.1...1.15.0
[1.14.1]: https://github.com/rak200/utils/compare/1.14.0...1.14.1
[1.14.0]: https://github.com/rak200/utils/compare/1.13.2...1.14.0
[1.13.2]: https://github.com/rak200/utils/compare/1.13.1...1.13.2
[1.13.1]: https://github.com/rak200/utils/compare/1.13.0...1.13.1
[1.13.0]: https://github.com/rak200/utils/compare/1.12.0...1.13.0
[1.12.0]: https://github.com/rak200/utils/compare/1.11.0...1.12.0
[1.11.0]: https://github.com/rak200/utils/compare/1.10.0...1.11.0
[1.10.0]: https://github.com/rak200/utils/compare/1.9.0...1.10.0
[1.9.0]: https://github.com/rak200/utils/compare/1.8.0...1.9.0
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
