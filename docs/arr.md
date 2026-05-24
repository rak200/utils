# Arr

[← Reference](README.md)

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
- [`contains`](#contains)
- [`keys`](#keys)
- [`values`](#values)
- [`pluck`](#pluck)
- [`keyBy`](#keyby)
- [`sort`](#sort)
- [`sortBy`](#sortby)
- [`merge`](#merge)
- [`pick` / `except`](#pick--except)
- [`zip`](#zip)
- [`range`](#range)

---

## `isEmpty` / `isNotEmpty`

```php
Arr::isEmpty([]);            // true
Arr::isEmpty([1, 2]);        // false
Arr::isNotEmpty([1, 2]);     // true
```

[↑ Back to top](#arr)

---

## `first` / `firstOrNull`

Bare throws on an empty array; `*OrNull` returns `null`.

```php
Arr::first([10, 20, 30]);           // 10
Arr::first(['a' => 1, 'b' => 2]);   // 1
Arr::firstOrNull([]);               // null
```

[↑ Back to top](#arr)

---

## `last` / `lastOrNull`

```php
Arr::last([10, 20, 30]);            // 30
Arr::last(['a' => 1, 'b' => 2]);    // 2
Arr::lastOrNull([]);                // null
```

[↑ Back to top](#arr)

---

## `find` / `findOrNull`

First element matching the predicate (which receives value and key).

```php
Arr::find([1, 2, 3, 4], fn(int $n) => $n > 2);         // 3
Arr::findOrNull([1, 2, 3], fn(int $n) => $n > 99);     // null
```

[↑ Back to top](#arr)

---

## `filter`

Predicate receives value and key. Keys are preserved.

```php
Arr::filter([1, 2, 3, 4], fn(int $n) => $n % 2 === 0);
// [1 => 2, 3 => 4]
```

[↑ Back to top](#arr)

---

## `map`

Callback receives value and key. Keys are preserved.

```php
Arr::map([1, 2, 3], fn(int $n) => $n * 10);
// [10, 20, 30]

Arr::map(['a' => 1, 'b' => 2], fn(int $n, string $k) => "$k:$n");
// ['a' => 'a:1', 'b' => 'b:2']
```

[↑ Back to top](#arr)

---

## `reduce`

Callback receives `(carry, value, key)`. The key argument is the third positional parameter — callbacks that ignore it (2-arg) work without changes.

```php
Arr::reduce([1, 2, 3, 4], fn($acc, $n) => $acc + $n, 0);             // 10
Arr::reduce(['a', 'b', 'c'], fn($acc, $s) => $acc . $s, '');         // 'abc'
Arr::reduce(
    ['a' => 1, 'b' => 2],
    fn(array $acc, int $v, string $k) => $acc + [$k => $v * 10],
    [],
);                                                                    // ['a' => 10, 'b' => 20]
```

[↑ Back to top](#arr)

---

## `flatten`

Flattens nested arrays down to `$depth` levels (default: complete flatten).

```php
Arr::flatten([1, [2, [3, [4]]]]);        // [1, 2, 3, 4]
Arr::flatten([1, [2, [3, [4]]]], 1);     // [1, 2, [3, [4]]]
```

[↑ Back to top](#arr)

---

## `groupBy`

Group elements by the value returned by the classifier.

```php
Arr::groupBy([1, 2, 3, 4, 5, 6], fn(int $n) => $n % 2 === 0 ? 'even' : 'odd');
// ['odd' => [1, 3, 5], 'even' => [2, 4, 6]]
```

[↑ Back to top](#arr)

---

## `partition`

Split into `[matching, non-matching]`.

```php
Arr::partition([1, 2, 3, 4], fn(int $n) => $n % 2 === 0);
// [[2, 4], [1, 3]]
```

[↑ Back to top](#arr)

---

## `chunk`

```php
Arr::chunk([1, 2, 3, 4, 5], 2);      // [[1, 2], [3, 4], [5]]
```

[↑ Back to top](#arr)

---

## `unique`

Loose comparison, re-indexed as a list.

```php
Arr::unique([1, 2, 2, 3, 3, 3]);     // [1, 2, 3]
Arr::unique(['a', 'b', 'a', 'c']);   // ['a', 'b', 'c']
```

[↑ Back to top](#arr)

---

## `has`

True when `$key` exists (even with a `null` value).

```php
Arr::has(['name' => 'rak'], 'name');     // true
Arr::has(['name' => 'rak'], 'email');    // false
Arr::has([null], 0);                     // true
```

[↑ Back to top](#arr)

---

## `contains`

True when `$value` is present. Strict comparison by default — pass `strict: false` for loose.

```php
Arr::contains([1, 2, 3], 2);                  // true
Arr::contains([1, 2, 3], '2');                // false
Arr::contains([1, 2, 3], '2', strict: false); // true
```

[↑ Back to top](#arr)

---

## `keys`

```php
Arr::keys(['a' => 1, 'b' => 2, 'c' => 3]);   // ['a', 'b', 'c']
Arr::keys([10, 20, 30]);                     // [0, 1, 2]
```

[↑ Back to top](#arr)

---

## `values`

Re-indexes as a 0-based list.

```php
Arr::values(['a' => 1, 'b' => 2]);     // [1, 2]
```

[↑ Back to top](#arr)

---

## `pluck`

Extracts the value at `$key` from each sub-array. Items without the key contribute `null`.

```php
$rows = [
    ['id' => 1, 'name' => 'a'],
    ['id' => 2, 'name' => 'b'],
    ['id' => 3],
];
Arr::pluck($rows, 'id');       // [1, 2, 3]
Arr::pluck($rows, 'name');     // ['a', 'b', null]
```

[↑ Back to top](#arr)

---

## `keyBy`

Re-indexes by the value at `$key` on each item, or by the value `$key($item)` returns when `$key` is callable. Later collisions overwrite earlier ones.

```php
$rows = [
    ['id' => 'a', 'v' => 1],
    ['id' => 'b', 'v' => 2],
];
Arr::keyBy($rows, 'id');
// ['a' => ['id' => 'a', 'v' => 1], 'b' => ['id' => 'b', 'v' => 2]]

Arr::keyBy([1, 2, 3, 4], fn(int $n) => $n % 2 === 0 ? 'even' : 'odd');
// ['odd' => 3, 'even' => 4]
```

[↑ Back to top](#arr)

---

## `sort`

Returns a sorted, re-indexed list. Default comparator is `<=>`.

```php
Arr::sort([3, 1, 2]);                                    // [1, 2, 3]
Arr::sort([1, 2, 3], fn(int $a, int $b) => $b <=> $a);   // [3, 2, 1]
```

[↑ Back to top](#arr)

---

## `sortBy`

Sorts by a derived key (callback receives value and key). Result is a re-indexed list.

```php
$people = [
    ['name' => 'c', 'age' => 30],
    ['name' => 'a', 'age' => 10],
    ['name' => 'b', 'age' => 20],
];
Arr::sortBy($people, fn(array $p) => $p['age']);
// [['name' => 'a', 'age' => 10], ['name' => 'b', 'age' => 20], ['name' => 'c', 'age' => 30]]
```

[↑ Back to top](#arr)

---

## `merge`

Left-to-right merge. String keys are overwritten by later arrays; integer keys are renumbered (matches `array_merge`).

```php
Arr::merge(['a' => 1, 'b' => 2], ['b' => 3, 'c' => 4]);
// ['a' => 1, 'b' => 3, 'c' => 4]
Arr::merge([1, 2], [3, 4]);                              // [1, 2, 3, 4]
```

[↑ Back to top](#arr)

---

## `pick` / `except`

`pick` keeps only the listed keys; `except` removes them. Original order is preserved.

```php
$a = ['a' => 1, 'b' => 2, 'c' => 3];
Arr::pick($a, ['a', 'c']);     // ['a' => 1, 'c' => 3]
Arr::except($a, ['a', 'c']);   // ['b' => 2]
```

[↑ Back to top](#arr)

---

## `zip`

Combines element-wise. Shorter inputs are padded with `null`.

```php
Arr::zip([1, 2, 3], ['a', 'b', 'c']);    // [[1, 'a'], [2, 'b'], [3, 'c']]
Arr::zip([1, 2, 3], ['a', 'b']);         // [[1, 'a'], [2, 'b'], [3, null]]
```

[↑ Back to top](#arr)

---

## `range`

Integer range, inclusive at both ends.

```php
Arr::range(1, 5);         // [1, 2, 3, 4, 5]
Arr::range(0, 10, 2);     // [0, 2, 4, 6, 8, 10]
Arr::range(5, 1, -1);     // [5, 4, 3, 2, 1]
```

[↑ Back to top](#arr)
