# Arr

Array helpers.

```php
use Rak200\Utils\Arr;
```

## Contents

- [`isEmpty` / `isNotEmpty`](#isempty--isnotempty)
- [`first` / `firstOrNull`](#first--firstornull)
- [`last` / `lastOrNull`](#last--lastornull)
- [`find` / `findOrNull`](#find--findornull)
- [`filter`](#filter)
- [`map`](#map)
- [`reduce`](#reduce)
- [`flatten`](#flatten)
- [`groupBy`](#groupby)
- [`partition`](#partition)
- [`chunk`](#chunk)
- [`unique`](#unique)
- [`has`](#has)
- [`keys`](#keys)
- [`values`](#values)
- [`zip`](#zip)
- [`range`](#range)

---

## `isEmpty` / `isNotEmpty`

```php
Arr::isEmpty([]);            // true
Arr::isEmpty([1, 2]);        // false
Arr::isNotEmpty([1, 2]);     // true
```

---

## `first` / `firstOrNull`

Bare throws on an empty array; `*OrNull` returns `null`.

```php
Arr::first([10, 20, 30]);           // 10
Arr::first(['a' => 1, 'b' => 2]);   // 1
Arr::firstOrNull([]);               // null
```

---

## `last` / `lastOrNull`

```php
Arr::last([10, 20, 30]);            // 30
Arr::last(['a' => 1, 'b' => 2]);    // 2
Arr::lastOrNull([]);                // null
```

---

## `find` / `findOrNull`

First element matching the predicate (which receives value and key).

```php
Arr::find([1, 2, 3, 4], fn(int $n) => $n > 2);         // 3
Arr::findOrNull([1, 2, 3], fn(int $n) => $n > 99);     // null
```

---

## `filter`

Predicate receives value and key. Keys are preserved.

```php
Arr::filter([1, 2, 3, 4], fn(int $n) => $n % 2 === 0);
// [1 => 2, 3 => 4]
```

---

## `map`

Callback receives value and key. Keys are preserved.

```php
Arr::map([1, 2, 3], fn(int $n) => $n * 10);
// [10, 20, 30]

Arr::map(['a' => 1, 'b' => 2], fn(int $n, string $k) => "$k:$n");
// ['a' => 'a:1', 'b' => 'b:2']
```

---

## `reduce`

```php
Arr::reduce([1, 2, 3, 4], fn($acc, $n) => $acc + $n, 0);       // 10
Arr::reduce(['a', 'b', 'c'], fn($acc, $s) => $acc . $s, '');   // 'abc'
```

---

## `flatten`

Flattens nested arrays down to `$depth` levels (default: complete flatten).

```php
Arr::flatten([1, [2, [3, [4]]]]);        // [1, 2, 3, 4]
Arr::flatten([1, [2, [3, [4]]]], 1);     // [1, 2, [3, [4]]]
```

---

## `groupBy`

Group elements by the value returned by the classifier.

```php
Arr::groupBy([1, 2, 3, 4, 5, 6], fn(int $n) => $n % 2 === 0 ? 'even' : 'odd');
// ['odd' => [1, 3, 5], 'even' => [2, 4, 6]]
```

---

## `partition`

Split into `[matching, non-matching]`.

```php
Arr::partition([1, 2, 3, 4], fn(int $n) => $n % 2 === 0);
// [[2, 4], [1, 3]]
```

---

## `chunk`

```php
Arr::chunk([1, 2, 3, 4, 5], 2);      // [[1, 2], [3, 4], [5]]
```

---

## `unique`

Loose comparison, re-indexed as a list.

```php
Arr::unique([1, 2, 2, 3, 3, 3]);     // [1, 2, 3]
Arr::unique(['a', 'b', 'a', 'c']);   // ['a', 'b', 'c']
```

---

## `has`

True when `$key` exists (even with a `null` value).

```php
Arr::has(['name' => 'rak'], 'name');     // true
Arr::has(['name' => 'rak'], 'email');    // false
Arr::has([null], 0);                     // true
```

---

## `keys`

```php
Arr::keys(['a' => 1, 'b' => 2, 'c' => 3]);   // ['a', 'b', 'c']
Arr::keys([10, 20, 30]);                     // [0, 1, 2]
```

---

## `values`

Re-indexes as a 0-based list.

```php
Arr::values(['a' => 1, 'b' => 2]);     // [1, 2]
```

---

## `zip`

Combines element-wise. Shorter inputs are padded with `null`.

```php
Arr::zip([1, 2, 3], ['a', 'b', 'c']);    // [[1, 'a'], [2, 'b'], [3, 'c']]
Arr::zip([1, 2, 3], ['a', 'b']);         // [[1, 'a'], [2, 'b'], [3, null]]
```

---

## `range`

Integer range, inclusive at both ends.

```php
Arr::range(1, 5);         // [1, 2, 3, 4, 5]
Arr::range(0, 10, 2);     // [0, 2, 4, 6, 8, 10]
Arr::range(5, 1, -1);     // [5, 4, 3, 2, 1]
```
