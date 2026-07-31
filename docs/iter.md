# Iter

[← Reference](README.md)

Lazy iterable helpers — the streaming counterpart of [`Arr`](arr.md). Every transform returns a `Generator` and pulls from its source one element at a time, so transforms compose without building intermediate arrays and sources may be infinite. Terminals consume the source to produce a concrete value — those that read it to the end (`toArray`, `last`, `reduce`, `count`, …) never return on an infinite source, so bound it with [`take`](#take--drop) first.

```php
use Rak200\Utils\Iter;
```

> **Single-pass — read this first.** A `Generator` can be iterated only once. Passing the *same* generator to two terminals fails: the first drains it, and PHP then throws `Cannot traverse an already closed generator` on the second. Re-derive the pipeline from its source instead of reusing a generator. Most lazy transforms **preserve keys** (`flatMap`, `flatten`, `values`, `zip`, `chunk` re-index), so [`toArray`](#toarray) re-indexes by default (pass `true` to keep keys).

## Contents

### Sources

- [`range`](#range)
- [`repeat`](#repeat)
- [`cycle`](#cycle)
- [`iterate`](#iterate)
- [`times`](#times)

### Lazy transforms

- [`map`](#map)
- [`filter`](#filter)
- [`flatMap`](#flatmap)
- [`take` / `drop`](#take--drop)
- [`takeWhile` / `dropWhile`](#takewhile--dropwhile)
- [`chunk`](#chunk)
- [`flatten`](#flatten)
- [`zip`](#zip)
- [`concat`](#concat)
- [`keys` / `values`](#keys--values)
- [`unique`](#unique)
- [`slice`](#slice)
- [`tap`](#tap)

### Terminals

- [`first` / `firstOrNull`](#first--firstornull)
- [`last` / `lastOrNull`](#last--lastornull)
- [`find` / `findOrNull`](#find--findornull)
- [`nth` / `nthOrNull`](#nth--nthornull)
- [`reduce`](#reduce)
- [`isEmpty` / `isNotEmpty`](#isempty--isnotempty)
- [`count`](#count)
- [`contains`](#contains)
- [`any` / `every`](#any--every)
- [`toArray`](#toarray)

---

## `range`

Yields integers from `$start`, advancing by `$step`. With `$end` null the sequence is **infinite**; otherwise it stops once it passes `$end` (a negative `$step` walks backwards), like [`Arr::range`](arr.md#range). Throws when `$step` is zero.

```php
Iter::toArray(Iter::range(0, 10, 2));            // [0, 2, 4, 6, 8, 10]
Iter::toArray(Iter::range(5, 1, -1));            // [5, 4, 3, 2, 1]
Iter::toArray(Iter::take(Iter::range(0), 4));    // [0, 1, 2, 3]  (infinite, bounded)
```

[↑ Back to top](#iter)

---

## `repeat`

Yields `$value` `$times` times, or forever when `$times` is null. Throws when `$times` is negative.

```php
Iter::toArray(Iter::repeat('x', 3));                 // ['x', 'x', 'x']
Iter::toArray(Iter::take(Iter::repeat('x'), 2));     // ['x', 'x']  (infinite, bounded)
```

[↑ Back to top](#iter)

---

## `cycle`

Yields the values of the source over and over, forever. Values are buffered on the first pass, so a single-pass source (a fresh, not-yet-iterated `Generator`) can be cycled; an empty source yields nothing rather than looping forever.

```php
Iter::toArray(Iter::take(Iter::cycle([1, 2, 3]), 7));  // [1, 2, 3, 1, 2, 3, 1]
Iter::toArray(Iter::cycle([]));                        // []
```

[↑ Back to top](#iter)

---

## `iterate`

Yields `$seed`, then `$fn($seed)`, then `$fn($fn($seed))`, … — an infinite sequence built by repeatedly applying `$fn`.

```php
Iter::toArray(Iter::take(Iter::iterate(1, fn (int $n): int => $n * 2), 4));  // [1, 2, 4, 8]
```

[↑ Back to top](#iter)

---

## `times`

Yields `$fn(0)`, `$fn(1)`, …, `$fn($count - 1)`. Throws when `$count` is negative.

```php
Iter::toArray(Iter::times(3, fn (int $i): int => $i * $i));  // [0, 1, 4]
Iter::toArray(Iter::times(0, fn (int $i): int => $i));       // []
```

[↑ Back to top](#iter)

---

## `map`

Lazily applies `$callback` (called with value and key) to each element, **preserving keys**.

```php
Iter::toArray(Iter::map([1, 2, 3], fn (int $n): int => $n * 2));  // [2, 4, 6]

$result = Iter::map(['a' => 1, 'b' => 2], fn (int $v, string $k): string => $k . $v);
Iter::toArray($result, true);                                     // ['a' => 'a1', 'b' => 'b2']
```

[↑ Back to top](#iter)

---

## `filter`

Lazily yields the elements for which `$predicate` (called with value and key) returns true, **preserving keys**.

```php
$result = Iter::filter(['a' => 1, 'b' => 2, 'c' => 3], fn (int $n): bool => $n > 1);
Iter::toArray($result, true);  // ['b' => 2, 'c' => 3]
```

[↑ Back to top](#iter)

---

## `flatMap`

Maps each element to an iterable via `$callback` (called with value and key) and yields the results flattened one level, re-indexed. The lazy twin of [`Arr::flatMap`](arr.md#flatmap).

```php
Iter::toArray(Iter::flatMap([1, 2, 3], fn (int $n): array => [$n, $n * 10]));
// [1, 10, 2, 20, 3, 30]
```

Returning a non-iterable from `$callback` throws `BadCallbackException` — raised while the result is being **consumed**, not when `flatMap` is called, since this is a generator.

[↑ Back to top](#iter)

---

## `take` / `drop`

`take` lazily yields the first `$count` elements then stops — the standard way to bound an infinite source. `drop` yields everything after the first `$count`. Both preserve keys and throw when `$count` is negative.

```php
Iter::toArray(Iter::take([1, 2, 3, 4], 2));  // [1, 2]
Iter::toArray(Iter::drop([1, 2, 3, 4], 2));  // [3, 4]
```

[↑ Back to top](#iter)

---

## `takeWhile` / `dropWhile`

`takeWhile` yields elements while `$predicate` (value, key) holds, stopping at the first failure. `dropWhile` skips that leading run, then yields the rest. Both preserve keys.

```php
Iter::toArray(Iter::takeWhile([1, 2, 3, 4, 1], fn (int $n): bool => $n < 3));  // [1, 2]
Iter::toArray(Iter::dropWhile([1, 2, 3, 4, 1], fn (int $n): bool => $n < 3));  // [3, 4, 1]
```

[↑ Back to top](#iter)

---

## `chunk`

Lazily yields chunks (0-indexed lists) of at most `$size` elements; the final chunk may be smaller. Throws when `$size` is less than 1.

```php
Iter::toArray(Iter::chunk([1, 2, 3, 4, 5], 2));                       // [[1, 2], [3, 4], [5]]
Iter::toArray(Iter::take(Iter::chunk(Iter::range(1), 2), 3));         // [[1, 2], [3, 4], [5, 6]]
```

[↑ Back to top](#iter)

---

## `flatten`

Lazily yields the elements of nested iterables flattened down to `$depth` levels (re-indexed). Unlike [`Arr::flatten`](arr.md#flatten), **any** nested iterable (array or `Traversable`) is descended into, not only arrays. Throws when `$depth` is negative.

```php
Iter::toArray(Iter::flatten([1, [2, 3], [4, [5]]]));     // [1, 2, 3, 4, 5]
Iter::toArray(Iter::flatten([1, [2, [3]]], 1));          // [1, 2, [3]]
```

[↑ Back to top](#iter)

---

## `zip`

Lazily yields tuples (0-indexed lists) pairing the elements of the inputs position by position. Unlike [`Arr::zip`](arr.md#zip) (which pads to the **longest** input with null), the lazy zip stops at the **shortest** — the only well-defined choice when an input may be infinite.

```php
Iter::toArray(Iter::zip([1, 2, 3], ['a', 'b']));                  // [[1, 'a'], [2, 'b']]
Iter::toArray(Iter::zip(Iter::range(1), ['a', 'b', 'c']));        // [[1, 'a'], [2, 'b'], [3, 'c']]
```

[↑ Back to top](#iter)

---

## `concat`

Lazily yields the elements of each iterable in turn, **preserving keys** (so `toArray` re-indexes to avoid collisions).

```php
Iter::toArray(Iter::concat([1, 2], [3, 4]));                          // [1, 2, 3, 4]
Iter::toArray(Iter::concat(['a' => 1], ['b' => 2]), true);           // ['a' => 1, 'b' => 2]
```

[↑ Back to top](#iter)

---

## `keys` / `values`

`keys` lazily yields the keys of the source as values; `values` yields the values, re-indexed.

```php
Iter::toArray(Iter::keys(['a' => 1, 'b' => 2]));    // ['a', 'b']
Iter::toArray(Iter::values([5 => 'x', 9 => 'y']));  // ['x', 'y']
```

[↑ Back to top](#iter)

---

## `unique`

Lazily yields the first occurrence of each distinct value (strict comparison), preserving keys. Buffers the values seen so far (O(n) memory).

```php
Iter::toArray(Iter::unique([1, '1', 1, 2, 2, 3]));  // [1, '1', 2, 3]
```

[↑ Back to top](#iter)

---

## `slice`

Lazily yields `$length` elements starting at `$offset` (preserving keys); a null `$length` runs to the end. Both bounds must be non-negative — counting from the end is impossible on a lazy or infinite source — and throw otherwise. With a null `$length`, slicing an infinite source never ends, so cap it with [`take`](#take--drop) when materialising.

```php
Iter::toArray(Iter::slice([10, 20, 30, 40, 50], 1, 2));  // [20, 30]
Iter::toArray(Iter::slice([10, 20, 30], 1));             // [20, 30]
```

[↑ Back to top](#iter)

---

## `tap`

Lazily yields each element unchanged (preserving keys) after invoking `$callback` on it (value, key) for its side effect — a peek into a lazy pipeline, the immutable stand-in for an impure `each`.

```php
$seen = [];
$out = Iter::toArray(Iter::tap([1, 2, 3], function (int $v) use (&$seen): void {
    $seen[] = $v;
}));
// $out  === [1, 2, 3]
// $seen === [1, 2, 3]
```

[↑ Back to top](#iter)

---

## `first` / `firstOrNull`

Returns the first element, consuming the source until one is found. Bare throws on an empty source; `*OrNull` returns `null`.

```php
Iter::first([5, 6, 7]);     // 5
Iter::firstOrNull([]);      // null
```

[↑ Back to top](#iter)

---

## `last` / `lastOrNull`

Returns the last element, consuming the **entire** source. Bare throws on an empty source; `*OrNull` returns `null`.

```php
Iter::last([5, 6, 7]);      // 7
Iter::lastOrNull([]);       // null
```

[↑ Back to top](#iter)

---

## `find` / `findOrNull`

Returns the first element matching `$predicate` (value, key), consuming the source until one matches. Bare throws when none match; `*OrNull` returns `null`.

```php
Iter::find([1, 2, 3, 4], fn (int $n): bool => $n % 2 === 0);     // 2
Iter::findOrNull([1, 3], fn (int $n): bool => $n % 2 === 0);     // null
```

[↑ Back to top](#iter)

---

## `nth` / `nthOrNull`

Returns the element at 0-based position `$n`, consuming the source up to it. Bare throws when `$n` is beyond the last element; `*OrNull` returns `null`. Both throw when `$n` is negative (counting from the end is impossible on a lazy source).

```php
Iter::nth([10, 20, 30], 1);     // 20
Iter::nthOrNull([10], 5);       // null
```

[↑ Back to top](#iter)

---

## `reduce`

Reduces the source to a single value by repeatedly applying `$callback` (accumulator, value, key), consuming the source.

```php
Iter::reduce([1, 2, 3, 4], fn (int $c, int $v): int => $c + $v, 0);  // 10
```

[↑ Back to top](#iter)

---

## `isEmpty` / `isNotEmpty`

`isEmpty` returns true when the source yields no elements; `isNotEmpty` is its negation. Both probe at most one element — an infinite source is fine — but probing **advances** the source: a `Generator` has its first element consumed. The lazy counterparts of [`Arr::isEmpty` / `Arr::isNotEmpty`](arr.md#isempty--isnotempty).

```php
Iter::isEmpty([]);                              // true
Iter::isEmpty(Iter::take(Iter::range(1), 0));   // true
Iter::isNotEmpty(Iter::range(1));               // true  (probes a single element)
```

[↑ Back to top](#iter)

---

## `count`

Returns the number of elements, consuming the source. Typed `int<0, max>`, so it can be returned straight out of a `Countable::count()` implementation, exactly like [`Arr::count`](arr.md#count).

```php
Iter::count([1, 2, 3]);                          // 3
Iter::count(Iter::take(Iter::range(1), 5));      // 5
```

[↑ Back to top](#iter)

---

## `contains`

Returns true when `$value` occurs in the source (strict comparison by default), short-circuiting at the first match.

```php
Iter::contains([1, 2, 3], 2);          // true
Iter::contains([1, 2, 3], '2');        // false  (strict)
Iter::contains([1, 2, 3], '2', false); // true   (loose)
```

[↑ Back to top](#iter)

---

## `any` / `every`

`any` returns true when `$predicate` (value, key) holds for at least one element; `every` returns true when it holds for all (vacuously true for an empty source). Both short-circuit.

```php
Iter::any([1, 2, 3], fn (int $n): bool => $n % 2 === 0);    // true
Iter::every([2, 4, 6], fn (int $n): bool => $n % 2 === 0);  // true
Iter::every([], fn (int $n): bool => $n > 0);               // true  (vacuous)
```

[↑ Back to top](#iter)

---

## `toArray`

Materialises the source into an array, consuming it. Re-indexes to a 0-based list by default; pass `$preserveKeys = true` to keep keys (later duplicates then overwrite earlier ones).

```php
Iter::toArray(['a' => 1, 'b' => 2]);        // [1, 2]
Iter::toArray(['a' => 1, 'b' => 2], true);  // ['a' => 1, 'b' => 2]
```

[↑ Back to top](#iter)
