# Arr

[← Reference](README.md)

Array helpers.

```php
use Rak200\Utils\Arr;
```

## Contents

- [`is`](#is)
- [`isEmpty` / `isNotEmpty`](#isempty--isnotempty)
- [`isList` / `isAssoc`](#islist--isassoc)
- [`count`](#count)
- [`first` / `firstOrNull`](#first--firstornull)
- [`last` / `lastOrNull`](#last--lastornull)
- [`firstKey` / `firstKeyOrNull` / `lastKey` / `lastKeyOrNull`](#firstkey--firstkeyornull--lastkey--lastkeyornull)
- [`find` / `findOrNull`](#find--findornull)
- [`search` / `searchOrNull`](#search--searchornull)
- [`filter`](#filter)
- [`map`](#map)
- [`reduce`](#reduce)
- [`flatten`](#flatten)
- [`groupBy`](#groupby)
- [`partition`](#partition)
- [`chunk`](#chunk)
- [`unique`](#unique)
- [`has` / `hasKey`](#has--haskey)
- [`get` / `getOrNull` / `getKey` / `getKeyOrNull`](#get--getornull--getkey--getkeyornull)
- [`set` / `forget`](#set--forget)
- [`dot` / `undot`](#dot--undot)
- [`contains`](#contains)
- [`keys`](#keys)
- [`values`](#values)
- [`pluck`](#pluck)
- [`keyBy`](#keyby)
- [`sort`](#sort)
- [`sortBy`](#sortby)
- [`sortKeys`](#sortkeys)
- [`reverse`](#reverse)
- [`slice`](#slice)
- [`flip`](#flip)
- [`combine`](#combine)
- [`diff` / `intersect`](#diff--intersect)
- [`countValues`](#countvalues)
- [`append` / `prepend`](#append--prepend)
- [`shift` / `shiftOrNull`](#shift--shiftornull)
- [`pop` / `popOrNull`](#pop--popornull)
- [Dropping the first / last element (`array_shift` / `array_pop`)](#dropping-the-first--last-element-array_shift--array_pop)
- [`fill` / `fillKeys`](#fill--fillkeys)
- [`merge`](#merge)
- [`pick` / `except`](#pick--except)
- [`zip`](#zip)
- [`range`](#range)

---

## `is`

Domain predicate — true when `$value` is an array (list or associative). Accepts `mixed` so it can be used as a guard. [`Type::isArray`](type.md#basic-type-checks) is an alias.

```php
Arr::is([]);             // true
Arr::is([1, 2, 3]);      // true
Arr::is(['a' => 1]);     // true
Arr::is('abc');          // false
Arr::is(null);           // false
```

[↑ Back to top](#arr)

---

## `isEmpty` / `isNotEmpty`

Typed `array` input. For a `mixed`-typed value, guard with `Arr::is($v) && $v !== []`.

```php
Arr::isEmpty([]);            // true
Arr::isEmpty([1, 2]);        // false
Arr::isNotEmpty([1, 2]);     // true
```

[↑ Back to top](#arr)

---

## `isList` / `isAssoc`

`mixed`-typed guards. `isList` mirrors `array_is_list` (sequential `0..n-1` int keys; empty array qualifies). `isAssoc` is the complement on non-empty arrays.

```php
Arr::isList([1, 2, 3]);              // true
Arr::isList([]);                     // true
Arr::isList(['a' => 1]);             // false
Arr::isList('abc');                  // false

Arr::isAssoc(['a' => 1]);            // true
Arr::isAssoc([1 => 'a', 0 => 'b']);  // true   (not 0-indexed)
Arr::isAssoc([1, 2, 3]);             // false
Arr::isAssoc([]);                    // false  (ambiguous — choose isList for empty)
```

[↑ Back to top](#arr)

---

## `count`

Number of elements in the array.

```php
Arr::count([]);                   // 0
Arr::count([1, 2, 3]);            // 3
Arr::count(['a' => 1, 'b' => 2]); // 2
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

## `firstKey` / `firstKeyOrNull` / `lastKey` / `lastKeyOrNull`

The key-returning counterparts of [`first`](#first--firstornull) / [`last`](#last--lastornull). Bare throws on an empty array; `*OrNull` returns `null`.

```php
Arr::firstKey(['x' => 1, 'y' => 2]);   // 'x'
Arr::lastKey(['x' => 1, 'y' => 2]);    // 'y'
Arr::firstKey([10, 20]);               // 0
Arr::lastKey([10, 20]);                // 1
Arr::firstKeyOrNull([]);               // null
Arr::lastKeyOrNull([]);                // null
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

Callback receives value and key. Keys are preserved — and a `list` input is typed as returning a `list` (conditional return type), so list-ness survives the call under PHPStan.

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

Strict comparison, re-indexed as a list — values of different types never collapse.

```php
Arr::unique([1, 2, 2, 3, 3, 3]);     // [1, 2, 3]
Arr::unique(['a', 'b', 'a', 'c']);   // ['a', 'b', 'c']
Arr::unique([1, '1', 1]);            // [1, '1']  (strict: int 1 and string '1' differ)
```

[↑ Back to top](#arr)

---

## `has` / `hasKey`

`has` resolves a **dot-path** `'a.b.c'`, traversing level by level (an int or dotless string is a single-segment lookup). `hasKey` is the literal-key check that never splits on dots. Both treat a `null` value as present.

```php
Arr::has(['user' => ['name' => 'rak']], 'user.name');  // true   (nested)
Arr::has(['user' => ['name' => 'rak']], 'user.age');   // false
Arr::has(['name' => 'rak'], 'name');                   // true
Arr::has(['a.b' => 1], 'a.b');                         // false  (traversed a→b; use hasKey)
Arr::has([null], 0);                                   // true

Arr::hasKey(['a' => ['b' => 1]], 'a.b');               // false  (no literal 'a.b' key)
Arr::hasKey(['a.b' => 1], 'a.b');                      // true
```

[↑ Back to top](#arr)

---

## `get` / `getOrNull` / `getKey` / `getKeyOrNull`

`get` / `getOrNull` read a value by dot-path (like [`has`](#has--haskey)); the bare form throws when the path does not resolve, `getOrNull` returns `null`. `getKey` / `getKeyOrNull` are the literal-key counterparts — they never split on dots (a key that contains a dot is read as-is), throwing / returning `null` respectively when the key is absent. All treat a present `null` value as found.

```php
$data = ['user' => ['name' => 'rak', 'roles' => ['admin']]];
Arr::get($data, 'user.name');            // 'rak'
Arr::get($data, 'user.roles.0');         // 'admin'
Arr::getOrNull($data, 'user.email');     // null
Arr::get($data, 'user.email');           // throws OutOfBoundsException

Arr::getKey(['a.b' => 1], 'a.b');               // 1     (literal key, no traversal)
Arr::getKeyOrNull(['a.b' => 1], 'a.b');         // 1
Arr::getKeyOrNull(['a' => ['b' => 1]], 'a.b');  // null  (no traversal)
Arr::getKey(['a' => ['b' => 1]], 'a.b');        // throws OutOfBoundsException
```

[↑ Back to top](#arr)

---

## `set` / `forget`

Immutable nested writes — both return a **new** array, leaving the input untouched. `set` creates intermediate arrays as needed (overwriting a non-array value met along the path); `forget` removes a nested key (an unresolved path yields an unchanged copy).

```php
Arr::set([], 'a.b.c', 1);                        // ['a' => ['b' => ['c' => 1]]]
Arr::set(['a' => ['x' => 1]], 'a.y', 2);         // ['a' => ['x' => 1, 'y' => 2]]
Arr::forget(['a' => ['b' => 1, 'c' => 2]], 'a.b'); // ['a' => ['c' => 2]]
Arr::forget(['a' => 1], 'x.y');                  // ['a' => 1]   (unchanged copy)
```

[↑ Back to top](#arr)

---

## `dot` / `undot`

`dot` flattens a nested array into a single level keyed by the dot-path to each leaf; `undot` is the inverse. Empty arrays are kept as leaves by `dot`.

```php
Arr::dot(['a' => ['b' => ['c' => 1]], 'x' => 2]);   // ['a.b.c' => 1, 'x' => 2]
Arr::undot(['a.b' => 1, 'a.c' => 2]);               // ['a' => ['b' => 1, 'c' => 2]]
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

## `search` / `searchOrNull`

Returns the *key* of the first element equal to `$value` (strict by default) — the key-returning counterpart of [`find`](#find--findornull). Bare throws when the value is absent; `*OrNull` returns `null`.

```php
Arr::search(['a', 'b', 'c'], 'b');         // 1
Arr::search(['x' => 1, 'y' => 2], 2);      // 'y'
Arr::searchOrNull([1, 2, 3], 9);           // null
Arr::searchOrNull([0, 1], '0');            // null  (strict)
Arr::searchOrNull([0, 1], '0', strict: false); // 0
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

Extracts the value at `$key` from each sub-array. Items without the key contribute `null`. Pass `$indexKey` to key each value by that column of the same item — the three-argument `array_column`; it follows [`keyBy`](#keyby) (the item must hold `$indexKey`, later collisions overwrite, otherwise it throws).

```php
$rows = [
    ['id' => 1, 'name' => 'a'],
    ['id' => 2, 'name' => 'b'],
    ['id' => 3],
];
Arr::pluck($rows, 'id');             // [1, 2, 3]
Arr::pluck($rows, 'name');           // ['a', 'b', null]
Arr::pluck($rows, 'name', 'id');     // [1 => 'a', 2 => 'b', 3 => null]
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

## `sortKeys`

Sorts by key with the natural comparator, preserving the key=>value association. Pass `desc: true` for descending order. Immutable. Ascending, a `list` input is typed as returning a `list` (its keys are already sorted, so this is a no-op).

```php
Arr::sortKeys(['b' => 1, 'a' => 2, 'c' => 3]);        // ['a' => 2, 'b' => 1, 'c' => 3]
Arr::sortKeys(['b' => 1, 'a' => 2], desc: true);      // ['b' => 1, 'a' => 2]
```

[↑ Back to top](#arr)

---

## `reverse`

Reverses element order. Integer keys are renumbered from 0 (string keys are always kept) unless `preserveKeys: true`. With the default `preserveKeys`, a `list` input is typed as returning a `list`.

```php
Arr::reverse([1, 2, 3]);                  // [3, 2, 1]
Arr::reverse(['a' => 1, 'b' => 2]);       // ['b' => 2, 'a' => 1]
Arr::reverse([1, 2, 3], preserveKeys: true); // [2 => 3, 1 => 2, 0 => 1]
```

[↑ Back to top](#arr)

---

## `slice`

A slice of `$length` elements from `$offset` (negative `$offset` counts from the end; null `$length` runs to the end, negative `$length` stops that many from the end). Integer keys are renumbered (string keys kept) unless `preserveKeys: true`. With the default `preserveKeys`, a `list` input is typed as returning a `list`.

```php
Arr::slice([1, 2, 3, 4, 5], 1, 2);            // [2, 3]
Arr::slice([1, 2, 3, 4, 5], -2);              // [4, 5]
Arr::slice([1, 2, 3, 4, 5], 1, -1);           // [2, 3, 4]
Arr::slice([1, 2, 3], 1, 2, preserveKeys: true); // [1 => 2, 2 => 3]
```

[↑ Back to top](#arr)

---

## `flip`

Swaps keys and values (values must be int or string). On duplicate values the last key wins.

```php
Arr::flip(['a' => 1, 'b' => 2]);   // [1 => 'a', 2 => 'b']
Arr::flip(['x', 'y']);             // ['x' => 0, 'y' => 1]
```

[↑ Back to top](#arr)

---

## `combine`

Pairs each key in `$keys` with the value at the same position in `$values`. Throws when the two differ in length.

```php
Arr::combine(['a', 'b'], [1, 2]);   // ['a' => 1, 'b' => 2]
Arr::combine([], []);               // []
```

[↑ Back to top](#arr)

---

## `diff` / `intersect`

Compare *by value* (loose string comparison, like `array_diff` / `array_intersect`), preserving the keys of the first array. `diff` keeps values not in any of the others; `intersect` keeps values present in every other. These differ from the key-based [`pick` / `except`](#pick--except). With no other arrays, the input is returned unchanged.

```php
Arr::diff([1, 2, 3, 4], [2, 4]);          // [0 => 1, 2 => 3]
Arr::diff([1, 2, 3], [1], [3]);           // [1 => 2]
Arr::intersect([1, 2, 3], [2, 3, 4]);     // [1 => 2, 2 => 3]
Arr::intersect([1, 2, 3], [2, 3], [2]);   // [1 => 2]
```

[↑ Back to top](#arr)

---

## `countValues`

Maps each distinct value to its occurrence count (values must be int or string).

```php
Arr::countValues(['a', 'b', 'a']);   // ['a' => 2, 'b' => 1]
Arr::countValues([1, 1, 2]);         // [1 => 2, 2 => 1]
```

[↑ Back to top](#arr)

---

## `append` / `prepend`

Immutable add at either end (the input array is left unchanged). `append` keeps existing keys and adds the new values under the next integer key; `prepend` inserts before, renumbering integer keys (string keys kept), matching `array_unshift`. A `list` input is typed as returning a `list`.

```php
Arr::append([1, 2], 3, 4);          // [1, 2, 3, 4]
Arr::append(['a' => 1], 2);         // ['a' => 1, 0 => 2]
Arr::prepend([1, 2], 0);            // [0, 1, 2]
Arr::prepend(['a' => 1], 3);        // [3, 'a' => 1]
```

[↑ Back to top](#arr)

---

## `shift` / `shiftOrNull`

Immutable `array_shift`: returns the `[firstElement, rest]` pair without mutating the input. The remainder follows [`slice`](#slice) semantics (integer keys renumbered, string keys kept; typed as a `list` when the input is one). Bare throws on an empty array; `*OrNull` returns `null`.

```php
[$first, $rest] = Arr::shift([10, 20, 30]);          // [10, [20, 30]]
[$first, $rest] = Arr::shift(['a' => 1, 'b' => 2]);  // [1, ['b' => 2]]
Arr::shiftOrNull([]);                                 // null
Arr::shift([]);                                       // UnderflowException
```

[↑ Back to top](#arr)

---

## `pop` / `popOrNull`

Immutable `array_pop`: returns the `[lastElement, rest]` pair without mutating the input. The remainder follows [`slice`](#slice) semantics (typed as a `list` when the input is one). Bare throws on an empty array; `*OrNull` returns `null`.

```php
[$last, $rest] = Arr::pop([10, 20, 30]);            // [30, [10, 20]]
[$last, $rest] = Arr::pop(['a' => 1, 'b' => 2]);    // [2, ['a' => 1]]
Arr::popOrNull([]);                                  // null
Arr::pop([]);                                        // UnderflowException
```

[↑ Back to top](#arr)

---

## Dropping the first / last element (`array_shift` / `array_pop`)

PHP's `array_shift` / `array_pop` are excluded by design — they mutate the array in place, breaking the immutable contract (each *returns* an element **and** *mutates* the array). The immutable substitutes are [`shift`](#shift--shiftornull) / [`pop`](#pop--popornull), which return both halves as a `[element, rest]` pair without touching the input. Pick the helper by how much of the result you actually need:

| What you want | Use |
|---|---|
| element **and** rest (full `array_shift` / `array_pop`) | `[$x, $rest] = Arr::shift($a)` / `Arr::pop($a)` |
| the element only | `Arr::first($a)` / `Arr::last($a)` (or `*OrNull`) |
| the rest only | `Arr::slice($a, 1)` (drop first) / `Arr::slice($a, 0, -1)` (drop last) |

[↑ Back to top](#arr)

---

## `fill` / `fillKeys`

`fill` builds a 0-indexed list of `$count` copies of a value; `fillKeys` maps every key in `$keys` to a value.

```php
Arr::fill(3, 'x');                  // ['x', 'x', 'x']
Arr::fill(0, 'x');                  // []
Arr::fillKeys(['a', 'b'], 0);       // ['a' => 0, 'b' => 0]
```

[↑ Back to top](#arr)

---

## `merge`

Left-to-right merge. String keys are overwritten by later arrays; integer keys are renumbered (matches `array_merge`). Merging only lists therefore yields a list at runtime, but the return type cannot say so — PHPStan does not evaluate conditional return types over a variadic parameter; pipe the result through [`values`](#values) when the narrowed type matters.

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
