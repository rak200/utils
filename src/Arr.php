<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

use function array_chunk;
use function array_combine;
use function array_count_values;
use function array_diff;
use function array_diff_key;
use function array_fill;
use function array_fill_keys;
use function array_filter;
use function array_flip;
use function array_intersect;
use function array_intersect_key;
use function array_is_list;
use function array_key_exists;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_search;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function krsort;
use function ksort;
use function max;
use function usort;

/**
 * Array helpers.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Arr
{
    private function __construct() {}

    /**
     * Returns true if $value is an array. Domain predicate for {@see Arr};
     * {@see Type::isArray()} is an alias.
     *
     * @phpstan-assert-if-true array<mixed> $value
     *
     * @phpstan-assert-if-false !array<mixed> $value
     */
    public static function is(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Returns true if the array has no elements.
     *
     * @param array<array-key, mixed> $array
     */
    public static function isEmpty(array $array): bool
    {
        return $array === [];
    }

    /**
     * Returns true if the array has at least one element.
     *
     * @param array<array-key, mixed> $array
     */
    public static function isNotEmpty(array $array): bool
    {
        return $array !== [];
    }

    /**
     * Returns true if $value is an array with sequential integer keys
     * starting at 0. An empty array qualifies. Accepts `mixed` so it can be
     * used as a guard on values whose type is not yet known.
     */
    public static function isList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }

    /**
     * Returns true if $value is a non-empty array whose keys are not the
     * sequential `0..n-1` integers of a list. Accepts `mixed`.
     */
    public static function isAssoc(mixed $value): bool
    {
        return is_array($value) && $value !== [] && !array_is_list($value);
    }

    /**
     * Returns the first element of the array.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return T
     *
     * @throws RuntimeException when the array is empty
     */
    public static function first(array $array): mixed
    {
        if ($array === []) {
            throw new RuntimeException('Cannot get first element of an empty array.');
        }

        return $array[array_key_first($array)];
    }

    /**
     * Returns the first element of the array, or null if it is empty.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return null|T
     */
    public static function firstOrNull(array $array): mixed
    {
        if ($array === []) {
            return null;
        }

        return $array[array_key_first($array)];
    }

    /**
     * Returns the last element of the array.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return T
     *
     * @throws RuntimeException when the array is empty
     */
    public static function last(array $array): mixed
    {
        if ($array === []) {
            throw new RuntimeException('Cannot get last element of an empty array.');
        }

        return $array[array_key_last($array)];
    }

    /**
     * Returns the last element of the array, or null if it is empty.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return null|T
     */
    public static function lastOrNull(array $array): mixed
    {
        if ($array === []) {
            return null;
        }

        return $array[array_key_last($array)];
    }

    /**
     * Returns the first key of the array.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return K
     *
     * @throws RuntimeException when the array is empty
     */
    public static function firstKey(array $array): int|string
    {
        if ($array === []) {
            throw new RuntimeException('Cannot get first key of an empty array.');
        }

        return array_key_first($array);
    }

    /**
     * Returns the first key of the array, or null if it is empty.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return null|K
     */
    public static function firstKeyOrNull(array $array): int|string|null
    {
        return array_key_first($array);
    }

    /**
     * Returns the last key of the array.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return K
     *
     * @throws RuntimeException when the array is empty
     */
    public static function lastKey(array $array): int|string
    {
        if ($array === []) {
            throw new RuntimeException('Cannot get last key of an empty array.');
        }

        return array_key_last($array);
    }

    /**
     * Returns the last key of the array, or null if it is empty.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return null|K
     */
    public static function lastKeyOrNull(array $array): int|string|null
    {
        return array_key_last($array);
    }

    /**
     * Returns the first element matching $predicate (called with value and key).
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>          $array
     * @param callable(T, K): bool $predicate
     *
     * @return T
     *
     * @throws RuntimeException when no element matches
     */
    public static function find(array $array, callable $predicate): mixed
    {
        foreach ($array as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }

        throw new RuntimeException('No element matches the predicate.');
    }

    /**
     * Returns the first element matching $predicate, or null if none match.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>          $array
     * @param callable(T, K): bool $predicate
     *
     * @return null|T
     */
    public static function findOrNull(array $array, callable $predicate): mixed
    {
        foreach ($array as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns the elements (with original keys preserved) for which $predicate
     * returns true. $predicate is invoked with both value and key.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>          $array
     * @param callable(T, K): bool $predicate
     *
     * @return array<K, T>
     */
    public static function filter(array $array, callable $predicate): array
    {
        return array_filter($array, $predicate, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Returns a new array with $callback applied to each element. Keys are
     * preserved; $callback receives value and key.
     *
     * @template K of array-key
     * @template T
     * @template TResult
     *
     * @param array<K, T>             $array
     * @param callable(T, K): TResult $callback
     *
     * @return array<K, TResult>
     */
    public static function map(array $array, callable $callback): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return $result;
    }

    /**
     * Reduces the array to a single value by repeatedly applying $callback.
     *
     * @template K of array-key
     * @template T
     * @template TCarry
     *
     * @param array<K, T>                    $array
     * @param callable(TCarry, T, K): TCarry $callback receives the accumulator, current value, and key
     * @param TCarry                         $initial  starting accumulator value, passed to the first $callback call
     *
     * @return TCarry
     */
    public static function reduce(array $array, callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($array as $key => $value) {
            $carry = $callback($carry, $value, $key);
        }

        return $carry;
    }

    /**
     * Flattens nested arrays down to $depth levels. Use PHP_INT_MAX (default)
     * to flatten completely. Keys are not preserved.
     *
     * @param array<array-key, mixed> $array
     *
     * @return list<mixed>
     *
     * @throws RuntimeException when $depth is negative
     */
    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        if ($depth < 0) {
            throw new RuntimeException('Depth must be non-negative.');
        }
        $result = [];
        foreach ($array as $item) {
            if (is_array($item) && $depth > 0) {
                foreach (self::flatten($item, $depth - 1) as $sub) {
                    $result[] = $sub;
                }
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Groups elements by the value returned by $classifier (called with value
     * and key). Each group is a 0-indexed list.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>               $array
     * @param callable(T, K): array-key $classifier
     *
     * @return array<array-key, non-empty-list<T>>
     */
    public static function groupBy(array $array, callable $classifier): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $group = $classifier($value, $key);
            $result[$group][] = $value;
        }

        return $result;
    }

    /**
     * Splits the array into [matching, non-matching] based on $predicate.
     * Both halves are 0-indexed lists.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>          $array
     * @param callable(T, K): bool $predicate
     *
     * @return array{0: list<T>, 1: list<T>}
     */
    public static function partition(array $array, callable $predicate): array
    {
        $truthy = [];
        $falsy = [];
        foreach ($array as $key => $value) {
            if ($predicate($value, $key)) {
                $truthy[] = $value;
            } else {
                $falsy[] = $value;
            }
        }

        return [$truthy, $falsy];
    }

    /**
     * Splits the array into chunks of at most $size elements. The final chunk
     * may be smaller.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return list<non-empty-list<T>>
     *
     * @throws RuntimeException when $size is less than 1
     */
    public static function chunk(array $array, int $size): array
    {
        if ($size < 1) {
            throw new RuntimeException('Chunk size must be at least 1.');
        }

        return array_chunk($array, $size);
    }

    /**
     * Returns the unique values of the array as a 0-indexed list. Uses loose
     * comparison (SORT_REGULAR).
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return list<T>
     */
    public static function unique(array $array): array
    {
        return array_values(array_unique($array, SORT_REGULAR));
    }

    /**
     * Returns true if $path resolves in the array — either as a literal key
     * (checked first, including null values) or, when $path is a string holding
     * dots, as a nested path `'a.b.c'` traversed level by level.
     *
     * The literal-first fallback is transitional: in 3.0.0 {@see has()} becomes
     * a pure dot-path lookup. Use {@see hasKey()} for a literal-key check that
     * stays stable across that change.
     *
     * @param array<array-key, mixed> $array
     */
    public static function has(array $array, int|string $path): bool
    {
        return self::resolvePath($array, $path)[0];
    }

    /**
     * Returns true if $key exists in the array as a literal key (including null
     * values), without dot-path interpretation — the literal-key counterpart to
     * the dot-aware {@see has()}.
     *
     * @param array<array-key, mixed> $array
     */
    public static function hasKey(array $array, int|string $key): bool
    {
        return array_key_exists($key, $array);
    }

    /**
     * Returns the value at $path — a literal key (checked first) or, when $path
     * is a string holding dots, the nested path `'a.b.c'`. The literal-first
     * fallback is transitional (pure dot-path in 3.0.0); see {@see has()}.
     *
     * @param array<array-key, mixed> $array
     *
     * @throws RuntimeException when $path does not resolve
     */
    public static function get(array $array, int|string $path): mixed
    {
        [$found, $value] = self::resolvePath($array, $path);
        if (!$found) {
            throw new RuntimeException("Path \"{$path}\" not found in array.");
        }

        return $value;
    }

    /**
     * Returns the value at $path (literal key first, then dot-path), or null when
     * it does not resolve. See {@see get()}.
     *
     * @param array<array-key, mixed> $array
     */
    public static function getOrNull(array $array, int|string $path): mixed
    {
        return self::resolvePath($array, $path)[1];
    }

    /**
     * Returns a new array with $value set at the nested dot-path $path, creating
     * intermediate arrays as needed; a non-array value met along the path is
     * overwritten. $array itself is left unchanged.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function set(array $array, string $path, mixed $value): array
    {
        return self::setSegments($array, Str::split($path, '.'), 0, $value);
    }

    /**
     * Returns a new array with the nested dot-path $path removed. $array itself is
     * left unchanged; a path that does not resolve yields an unchanged copy.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function forget(array $array, string $path): array
    {
        return self::forgetSegments($array, Str::split($path, '.'), 0);
    }

    /**
     * Flattens a nested array into a single level keyed by the dot-path to each
     * leaf: `['a' => ['b' => 1]]` becomes `['a.b' => 1]`. Empty arrays are kept as
     * leaves. The inverse of {@see undot()}.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function dot(array $array, string $prefix = ''): array
    {
        $result = [];
        self::dotInto($array, $prefix, $result);

        return $result;
    }

    /**
     * Expands a single-level array of dot-paths back into nested arrays — the
     * inverse of {@see dot()}: `['a.b' => 1]` becomes `['a' => ['b' => 1]]`.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function undot(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result = self::set($result, (string) $key, $value);
        }

        return $result;
    }

    /**
     * Returns the keys of the array, in their original order.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return list<K>
     */
    public static function keys(array $array): array
    {
        return array_keys($array);
    }

    /**
     * Returns the values of the array re-indexed as a 0-based list.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return list<T>
     */
    public static function values(array $array): array
    {
        return array_values($array);
    }

    /**
     * Combines the input arrays element-wise. The result length matches the
     * longest input; shorter inputs are padded with null.
     *
     * @param array<array-key, mixed> ...$arrays
     *
     * @return list<list<mixed>>
     */
    public static function zip(array ...$arrays): array
    {
        if ($arrays === []) {
            return [];
        }
        $count = max(array_map(count(...), $arrays));
        $values = array_map(array_values(...), $arrays);
        $result = [];
        for ($i = 0; $i < $count; ++$i) {
            $tuple = [];
            foreach ($values as $list) {
                $tuple[] = $list[$i] ?? null;
            }
            $result[] = $tuple;
        }

        return $result;
    }

    /**
     * Returns true when $value is present in $array. Strict comparison by default.
     *
     * @param array<array-key, mixed> $array
     */
    public static function contains(array $array, mixed $value, bool $strict = true): bool
    {
        return in_array($value, $array, $strict);
    }

    /**
     * Returns the key of the first element equal to $value (strict comparison by
     * default). The key-returning counterpart of {@see find()}, which returns the
     * value.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array-key
     *
     * @throws RuntimeException when $value is not present
     */
    public static function search(array $array, mixed $value, bool $strict = true): int|string
    {
        $key = self::searchOrNull($array, $value, $strict);
        if ($key === null) {
            throw new RuntimeException('Value not found in array.');
        }

        return $key;
    }

    /**
     * Returns the key of the first element equal to $value (strict comparison by
     * default), or null when $value is not present.
     *
     * @param array<array-key, mixed> $array
     *
     * @return null|array-key
     */
    public static function searchOrNull(array $array, mixed $value, bool $strict = true): int|string|null
    {
        $key = array_search($value, $array, $strict);

        return $key === false ? null : $key;
    }

    /**
     * Extracts the values at $key from each sub-array of $array. Items without
     * the key contribute null. With no $indexKey the result is a 0-indexed list;
     * with $indexKey each value is keyed by that column of the same item,
     * following {@see keyBy()} (the item must be an array holding $indexKey;
     * later collisions overwrite) — the three-argument {@see array_column()}.
     *
     * @param array<array-key, mixed> $array
     *
     * @return ($indexKey is null ? list<mixed> : array<int|string, mixed>)
     *
     * @throws RuntimeException when $indexKey is given and an item lacks it or
     *                          the resolved key is not an int or string
     */
    public static function pluck(array $array, int|string $key, int|string|null $indexKey = null): array
    {
        $extract = static fn (mixed $item): mixed => is_array($item) && array_key_exists($key, $item) ? $item[$key] : null;
        if ($indexKey !== null) {
            return self::map(self::keyBy($array, $indexKey), $extract);
        }
        $result = [];
        foreach ($array as $item) {
            $result[] = $extract($item);
        }

        return $result;
    }

    /**
     * Re-indexes $array by the value at $key on each item (or by the result of
     * $key when called with the item). Later collisions overwrite earlier ones.
     *
     * @template T
     *
     * @param array<array-key, T>                  $array
     * @param callable(T): (int|string)|int|string $key
     *
     * @return array<int|string, T>
     *
     * @throws RuntimeException when $key is a column name and an item is not
     *                          an array or lacks that column, or when the
     *                          resolved key is not an int or string
     */
    public static function keyBy(array $array, callable|int|string $key): array
    {
        $result = [];
        $isCallable = !Num::isInt($key) && !Str::is($key);
        foreach ($array as $item) {
            if ($isCallable) {
                /** @var callable(T): (int|string) $key */
                $k = $key($item);
            } else {
                if (!is_array($item) || !array_key_exists($key, $item)) {
                    throw new RuntimeException("Item missing key \"{$key}\"");
                }
                $k = $item[$key];
            }
            if (!Num::isInt($k) && !Str::is($k)) {
                throw new RuntimeException('Resolved key must be an int or string.');
            }
            $result[$k] = $item;
        }

        return $result;
    }

    /**
     * Returns $array sorted with the natural `<=>` comparator (or $comparator
     * when given). Values are re-indexed as a 0-based list.
     *
     * @template T
     *
     * @param array<array-key, T>      $array
     * @param null|callable(T, T): int $comparator
     *
     * @return list<T>
     */
    public static function sort(array $array, ?callable $comparator = null): array
    {
        $values = array_values($array);
        if ($comparator === null) {
            usort($values, static fn (mixed $a, mixed $b): int => $a <=> $b);
        } else {
            usort($values, $comparator);
        }

        return $values;
    }

    /**
     * Returns $array sorted by the value produced by $keyExtractor for each
     * element (called with value and key). Values are re-indexed as a 0-based
     * list.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>           $array
     * @param callable(T, K): mixed $keyExtractor
     *
     * @return list<T>
     */
    public static function sortBy(array $array, callable $keyExtractor): array
    {
        $annotated = [];
        foreach ($array as $key => $value) {
            $annotated[] = [$keyExtractor($value, $key), $value];
        }
        usort($annotated, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return array_map(static fn (array $pair): mixed => $pair[1], $annotated);
    }

    /**
     * Merges $arrays left-to-right. String keys are overwritten by later arrays;
     * integer keys are renumbered (matches PHP `array_merge`).
     *
     * @param array<array-key, mixed> ...$arrays
     *
     * @return array<array-key, mixed>
     */
    public static function merge(array ...$arrays): array
    {
        return $arrays === [] ? [] : array_merge(...$arrays);
    }

    /**
     * Returns $array restricted to the given $keys, preserving original order.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T> $array
     * @param list<K>     $keys
     *
     * @return array<K, T>
     */
    public static function pick(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Returns $array with the given $keys removed, preserving original order.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T> $array
     * @param list<K>     $keys
     *
     * @return array<K, T>
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Returns the number of elements in the array.
     *
     * @param array<array-key, mixed> $array
     */
    public static function count(array $array): int
    {
        return count($array);
    }

    /**
     * Returns the array with its elements in reverse order. Integer keys are
     * renumbered from 0 (string keys are always kept) unless $preserveKeys is true.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return array<array-key, T>
     */
    public static function reverse(array $array, bool $preserveKeys = false): array
    {
        return array_reverse($array, $preserveKeys);
    }

    /**
     * Returns a slice of $length elements from the array starting at $offset
     * (negative $offset counts from the end; null $length runs to the end,
     * negative $length stops that many elements from the end). Integer keys are
     * renumbered (string keys kept) unless $preserveKeys is true.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return array<array-key, T>
     */
    public static function slice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array
    {
        return array_slice($array, $offset, $length, $preserveKeys);
    }

    /**
     * Returns the array with keys and values swapped. Values must be int or
     * string; on duplicate values the last key wins.
     *
     * @param array<array-key, int|string> $array
     *
     * @return array<int|string, array-key>
     */
    public static function flip(array $array): array
    {
        return array_flip($array);
    }

    /**
     * Pairs each key in $keys with the value at the same position in $values.
     *
     * @template T
     *
     * @param list<array-key> $keys
     * @param list<T>         $values
     *
     * @return array<array-key, T>
     *
     * @throws RuntimeException when $keys and $values differ in length
     */
    public static function combine(array $keys, array $values): array
    {
        if (count($keys) !== count($values)) {
            throw new RuntimeException('Keys and values must have the same number of elements.');
        }

        return array_combine($keys, $values);
    }

    /**
     * Returns the values of $array not present in any of $others (loose
     * string comparison, like {@see array_diff()}). Keys are preserved.
     *
     * @template T of int|string
     *
     * @param array<array-key, T>     $array
     * @param array<array-key, mixed> ...$others
     *
     * @return array<array-key, T>
     */
    public static function diff(array $array, array ...$others): array
    {
        return $others === [] ? $array : array_diff($array, ...$others);
    }

    /**
     * Returns the values of $array present in every one of $others (loose
     * string comparison, like {@see array_intersect()}). Keys are preserved.
     *
     * @template T of int|string
     *
     * @param array<array-key, T>     $array
     * @param array<array-key, mixed> ...$others
     *
     * @return array<array-key, T>
     */
    public static function intersect(array $array, array ...$others): array
    {
        return $others === [] ? $array : array_intersect($array, ...$others);
    }

    /**
     * Returns a map from each distinct value in $array to its occurrence count.
     * Values must be int or string (matching {@see array_count_values()}).
     *
     * @param array<array-key, int|string> $array
     *
     * @return array<int|string, int>
     */
    public static function countValues(array $array): array
    {
        return array_count_values($array);
    }

    /**
     * Returns a new array with $values appended after the array's elements,
     * each under the next integer key (existing keys are preserved). $array
     * itself is left unchanged.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     * @param T                   ...$values
     *
     * @return array<array-key, T>
     */
    public static function append(array $array, mixed ...$values): array
    {
        foreach ($values as $value) {
            $array[] = $value;
        }

        return $array;
    }

    /**
     * Returns a new array with $values inserted before the array's elements.
     * Integer keys are renumbered (string keys kept), matching
     * {@see array_unshift()}. $array itself is left unchanged.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     * @param T                   ...$values
     *
     * @return array<array-key, T>
     */
    public static function prepend(array $array, mixed ...$values): array
    {
        return $values === [] ? $array : array_merge($values, $array);
    }

    /**
     * Immutable {@see array_shift()}: returns a `[firstElement, rest]` pair
     * without mutating $array. The remainder follows {@see slice()} semantics
     * (integer keys renumbered, string keys kept).
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return array{0: T, 1: array<array-key, T>}
     *
     * @throws RuntimeException when the array is empty
     */
    public static function shift(array $array): array
    {
        if ($array === []) {
            throw new RuntimeException('Cannot shift from an empty array.');
        }

        return [self::first($array), self::slice($array, 1)];
    }

    /**
     * Returns the `[firstElement, rest]` pair of {@see shift()}, or null when
     * the array is empty.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return null|array{0: T, 1: array<array-key, T>}
     */
    public static function shiftOrNull(array $array): ?array
    {
        return $array === [] ? null : [self::first($array), self::slice($array, 1)];
    }

    /**
     * Immutable {@see array_pop()}: returns a `[lastElement, rest]` pair without
     * mutating $array. The remainder follows {@see slice()} semantics (integer
     * keys renumbered, string keys kept).
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return array{0: T, 1: array<array-key, T>}
     *
     * @throws RuntimeException when the array is empty
     */
    public static function pop(array $array): array
    {
        if ($array === []) {
            throw new RuntimeException('Cannot pop from an empty array.');
        }

        return [self::last($array), self::slice($array, 0, -1)];
    }

    /**
     * Returns the `[lastElement, rest]` pair of {@see pop()}, or null when the
     * array is empty.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return null|array{0: T, 1: array<array-key, T>}
     */
    public static function popOrNull(array $array): ?array
    {
        return $array === [] ? null : [self::last($array), self::slice($array, 0, -1)];
    }

    /**
     * Returns $array sorted by key with the natural `<=>` comparator, preserving
     * the key=>value association. Pass $desc = true for descending order.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return array<array-key, T>
     */
    public static function sortKeys(array $array, bool $desc = false): array
    {
        if ($desc) {
            krsort($array);
        } else {
            ksort($array);
        }

        return $array;
    }

    /**
     * Returns a 0-indexed list of $count copies of $value.
     *
     * @template T
     *
     * @param T $value
     *
     * @return list<T>
     *
     * @throws RuntimeException when $count is negative
     */
    public static function fill(int $count, mixed $value): array
    {
        if ($count < 0) {
            throw new RuntimeException('Count must be non-negative.');
        }

        return array_fill(0, $count, $value);
    }

    /**
     * Returns an array mapping each key in $keys to $value.
     *
     * @template T
     *
     * @param list<array-key> $keys
     * @param T               $value
     *
     * @return array<array-key, T>
     */
    public static function fillKeys(array $keys, mixed $value): array
    {
        return array_fill_keys($keys, $value);
    }

    /**
     * Returns a list of integers from $start to $end (both inclusive), advancing
     * by $step (negative steps walk backwards). Returns [] when the direction
     * cannot reach $end.
     *
     * @return list<int>
     *
     * @throws RuntimeException when $step is zero
     */
    public static function range(int $start, int $end, int $step = 1): array
    {
        if ($step === 0) {
            throw new RuntimeException('Step cannot be zero.');
        }
        if (($step > 0 && $start > $end) || ($step < 0 && $start < $end)) {
            return [];
        }
        $result = [];
        if ($step > 0) {
            for ($i = $start; $i <= $end; $i += $step) {
                $result[] = $i;
            }
        } else {
            for ($i = $start; $i >= $end; $i += $step) {
                $result[] = $i;
            }
        }

        return $result;
    }

    /**
     * Resolves $path against $array — the literal key first, then, for a string
     * with dots, a level-by-level dot-traversal — returning a `[found, value]`
     * pair so callers can tell a missing path from a present null.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array{0: bool, 1: mixed}
     */
    private static function resolvePath(array $array, int|string $path): array
    {
        if (array_key_exists($path, $array)) {
            return [true, $array[$path]];
        }
        if (Num::isInt($path) || !Str::contains($path, '.')) {
            return [false, null];
        }
        $current = $array;
        foreach (Str::split($path, '.') as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return [false, null];
            }
            $current = $current[$segment];
        }

        return [true, $current];
    }

    /**
     * Recursively sets $value at $segments[$index..] within $array, creating
     * intermediate arrays. Backing implementation of {@see set()}.
     *
     * @param array<array-key, mixed> $array
     * @param list<string>            $segments
     *
     * @return array<array-key, mixed>
     */
    private static function setSegments(array $array, array $segments, int $index, mixed $value): array
    {
        $key = $segments[$index];
        if ($index === count($segments) - 1) {
            $array[$key] = $value;

            return $array;
        }
        $child = isset($array[$key]) && is_array($array[$key]) ? $array[$key] : [];
        $array[$key] = self::setSegments($child, $segments, $index + 1, $value);

        return $array;
    }

    /**
     * Recursively removes $segments[$index..] from $array. Backing implementation
     * of {@see forget()}.
     *
     * @param array<array-key, mixed> $array
     * @param list<string>            $segments
     *
     * @return array<array-key, mixed>
     */
    private static function forgetSegments(array $array, array $segments, int $index): array
    {
        $key = $segments[$index];
        if ($index === count($segments) - 1) {
            unset($array[$key]);

            return $array;
        }
        if (isset($array[$key]) && is_array($array[$key])) {
            $array[$key] = self::forgetSegments($array[$key], $segments, $index + 1);
        }

        return $array;
    }

    /**
     * Writes the dot-path leaves of $array into $result in a single pass (no
     * intermediate arrays). Backing implementation of {@see dot()}.
     *
     * @param array<array-key, mixed> $array
     * @param array<array-key, mixed> $result
     */
    private static function dotInto(array $array, string $prefix, array &$result): void
    {
        foreach ($array as $key => $value) {
            $compound = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value) && $value !== []) {
                self::dotInto($value, $compound, $result);
            } else {
                $result[$compound] = $value;
            }
        }
    }
}
