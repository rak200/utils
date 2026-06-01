<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;
use function array_chunk, array_combine, array_count_values, array_diff, array_diff_key,
    array_fill, array_fill_keys, array_filter, array_flip, array_intersect, array_intersect_key,
    array_is_list, array_key_exists, array_key_first, array_key_last, array_keys, array_map,
    array_merge, array_reverse, array_search, array_slice, array_unique, array_values, count,
    in_array, is_array, krsort, ksort, max, usort;

/**
 * Array helpers.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Arr {
    private function __construct() {}

    /**
     * Returns true if $value is an array. Domain predicate for {@see Arr};
     * {@see Type::isArray()} is an alias.
     *
     * @phpstan-assert-if-true array<mixed> $value
     * @phpstan-assert-if-false !array<mixed> $value
     */
    public static function is(mixed $value): bool {
        return is_array($value);
    }

    /**
     * Returns true if the array has no elements.
     *
     * @param array<array-key, mixed> $array
     */
    public static function isEmpty(array $array): bool {
        return $array === [];
    }

    /**
     * Returns true if the array has at least one element.
     *
     * @param array<array-key, mixed> $array
     */
    public static function isNotEmpty(array $array): bool {
        return $array !== [];
    }

    /**
     * Returns true if $value is an array with sequential integer keys
     * starting at 0. An empty array qualifies. Accepts `mixed` so it can be
     * used as a guard on values whose type is not yet known.
     */
    public static function isList(mixed $value): bool {
        return is_array($value) && array_is_list($value);
    }

    /**
     * Returns true if $value is a non-empty array whose keys are not the
     * sequential `0..n-1` integers of a list. Accepts `mixed`.
     */
    public static function isAssoc(mixed $value): bool {
        return is_array($value) && $value !== [] && !array_is_list($value);
    }

    /**
     * Returns true if $value is an array with at least one element. Accepts
     * `mixed` (unlike {@see isNotEmpty()}, which requires an `array`).
     */
    public static function isNonEmptyArray(mixed $value): bool {
        return is_array($value) && $value !== [];
    }

    /**
     * Returns the first element of the array.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return T
     * @throws RuntimeException When the array is empty.
     */
    public static function first(array $array): mixed {
        if ($array === []) {
            throw new RuntimeException('Cannot get first element of an empty array.');
        }
        return $array[array_key_first($array)];
    }

    /**
     * Returns the first element of the array, or null if it is empty.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return T|null
     */
    public static function firstOrNull(array $array): mixed {
        if ($array === []) {
            return null;
        }
        return $array[array_key_first($array)];
    }

    /**
     * Returns the last element of the array.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return T
     * @throws RuntimeException When the array is empty.
     */
    public static function last(array $array): mixed {
        if ($array === []) {
            throw new RuntimeException('Cannot get last element of an empty array.');
        }
        return $array[array_key_last($array)];
    }

    /**
     * Returns the last element of the array, or null if it is empty.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return T|null
     */
    public static function lastOrNull(array $array): mixed {
        if ($array === []) {
            return null;
        }
        return $array[array_key_last($array)];
    }

    /**
     * Returns the first key of the array.
     *
     * @template K of array-key
     * @param array<K, mixed> $array
     * @return K
     * @throws RuntimeException When the array is empty.
     */
    public static function firstKey(array $array): int|string {
        if ($array === []) {
            throw new RuntimeException('Cannot get first key of an empty array.');
        }
        return array_key_first($array);
    }

    /**
     * Returns the first key of the array, or null if it is empty.
     *
     * @template K of array-key
     * @param array<K, mixed> $array
     * @return K|null
     */
    public static function firstKeyOrNull(array $array): int|string|null {
        return array_key_first($array);
    }

    /**
     * Returns the last key of the array.
     *
     * @template K of array-key
     * @param array<K, mixed> $array
     * @return K
     * @throws RuntimeException When the array is empty.
     */
    public static function lastKey(array $array): int|string {
        if ($array === []) {
            throw new RuntimeException('Cannot get last key of an empty array.');
        }
        return array_key_last($array);
    }

    /**
     * Returns the last key of the array, or null if it is empty.
     *
     * @template K of array-key
     * @param array<K, mixed> $array
     * @return K|null
     */
    public static function lastKeyOrNull(array $array): int|string|null {
        return array_key_last($array);
    }

    /**
     * Returns the first element matching $predicate (called with value and key).
     *
     * @template K of array-key
     * @template T
     * @param array<K, T> $array
     * @param callable(T, K): bool $predicate
     * @return T
     * @throws RuntimeException When no element matches.
     */
    public static function find(array $array, callable $predicate): mixed {
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
     * @param array<K, T> $array
     * @param callable(T, K): bool $predicate
     * @return T|null
     */
    public static function findOrNull(array $array, callable $predicate): mixed {
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
     * @param array<K, T> $array
     * @param callable(T, K): bool $predicate
     * @return array<K, T>
     */
    public static function filter(array $array, callable $predicate): array {
        return array_filter($array, $predicate, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Returns a new array with $callback applied to each element. Keys are
     * preserved; $callback receives value and key.
     *
     * @template K of array-key
     * @template T
     * @template TResult
     * @param array<K, T> $array
     * @param callable(T, K): TResult $callback
     * @return array<K, TResult>
     */
    public static function map(array $array, callable $callback): array {
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
     * @param array<K, T> $array
     * @param callable(TCarry, T, K): TCarry $callback Receives the accumulator, current value, and key.
     * @param TCarry $initial
     * @return TCarry
     */
    public static function reduce(array $array, callable $callback, mixed $initial = null): mixed {
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
     * @throws RuntimeException When $depth is negative.
     * @return list<mixed>
     */
    public static function flatten(array $array, int $depth = PHP_INT_MAX): array {
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
     * @param array<K, T> $array
     * @param callable(T, K): array-key $classifier
     * @return array<array-key, non-empty-list<T>>
     */
    public static function groupBy(array $array, callable $classifier): array {
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
     * @param array<K, T> $array
     * @param callable(T, K): bool $predicate
     * @return array{0: list<T>, 1: list<T>}
     */
    public static function partition(array $array, callable $predicate): array {
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
     * @param array<array-key, T> $array
     * @throws RuntimeException When $size is less than 1.
     * @return list<non-empty-list<T>>
     */
    public static function chunk(array $array, int $size): array {
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
     * @param array<array-key, T> $array
     * @return list<T>
     */
    public static function unique(array $array): array {
        return array_values(array_unique($array, SORT_REGULAR));
    }

    /**
     * Returns true if $key exists in the array (including null values).
     *
     * @param array<array-key, mixed> $array
     */
    public static function has(array $array, int|string $key): bool {
        return array_key_exists($key, $array);
    }

    /**
     * Returns the keys of the array, in their original order.
     *
     * @template K of array-key
     * @param array<K, mixed> $array
     * @return list<K>
     */
    public static function keys(array $array): array {
        return array_keys($array);
    }

    /**
     * Returns the values of the array re-indexed as a 0-based list.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return list<T>
     */
    public static function values(array $array): array {
        return array_values($array);
    }

    /**
     * Combines the input arrays element-wise. The result length matches the
     * longest input; shorter inputs are padded with null.
     *
     * @param array<array-key, mixed> ...$arrays
     * @return list<list<mixed>>
     */
    public static function zip(array ...$arrays): array {
        if ($arrays === []) {
            return [];
        }
        $count = max(array_map('count', $arrays));
        $values = array_map('array_values', $arrays);
        $result = [];
        for ($i = 0; $i < $count; $i++) {
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
    public static function contains(array $array, mixed $value, bool $strict = true): bool {
        return in_array($value, $array, $strict);
    }

    /**
     * Returns the key of the first element equal to $value (strict comparison by
     * default). The key-returning counterpart of {@see find()}, which returns the
     * value.
     *
     * @param array<array-key, mixed> $array
     * @return array-key
     * @throws RuntimeException When $value is not present.
     */
    public static function search(array $array, mixed $value, bool $strict = true): int|string {
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
     * @return array-key|null
     */
    public static function searchOrNull(array $array, mixed $value, bool $strict = true): int|string|null {
        $key = array_search($value, $array, $strict);
        return $key === false ? null : $key;
    }

    /**
     * Extracts the values at $key from each sub-array of $array as a 0-indexed
     * list. Items without the key contribute null.
     *
     * @param array<array-key, mixed> $array
     * @return list<mixed>
     */
    public static function pluck(array $array, int|string $key): array {
        $result = [];
        foreach ($array as $item) {
            $result[] = is_array($item) && array_key_exists($key, $item) ? $item[$key] : null;
        }
        return $result;
    }

    /**
     * Re-indexes $array by the value at $key on each item (or by the result of
     * $key when called with the item). Later collisions overwrite earlier ones.
     *
     * @template T
     * @param array<array-key, T> $array
     * @param int|string|callable(T): (int|string) $key
     * @return array<int|string, T>
     * @throws RuntimeException When $key is a column name and an item is not
     *                          an array or lacks that column, or when the
     *                          resolved key is not an int or string.
     */
    public static function keyBy(array $array, int|string|callable $key): array {
        $result = [];
        $isCallable = !Num::isInt($key) && !Str::is($key);
        foreach ($array as $item) {
            if ($isCallable) {
                /** @var callable(T): (int|string) $key */
                $k = $key($item);
            } else {
                if (!is_array($item) || !array_key_exists($key, $item)) {
                    throw new RuntimeException("Item missing key \"$key\"");
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
     * @param array<array-key, T> $array
     * @param null|callable(T, T): int $comparator
     * @return list<T>
     */
    public static function sort(array $array, ?callable $comparator = null): array {
        $values = array_values($array);
        if ($comparator === null) {
            usort($values, static fn(mixed $a, mixed $b): int => $a <=> $b);
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
     * @param array<K, T> $array
     * @param callable(T, K): mixed $keyExtractor
     * @return list<T>
     */
    public static function sortBy(array $array, callable $keyExtractor): array {
        $annotated = [];
        foreach ($array as $key => $value) {
            $annotated[] = [$keyExtractor($value, $key), $value];
        }
        usort($annotated, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        return array_map(static fn(array $pair): mixed => $pair[1], $annotated);
    }

    /**
     * Merges $arrays left-to-right. String keys are overwritten by later arrays;
     * integer keys are renumbered (matches PHP `array_merge`).
     *
     * @param array<array-key, mixed> ...$arrays
     * @return array<array-key, mixed>
     */
    public static function merge(array ...$arrays): array {
        return $arrays === [] ? [] : array_merge(...$arrays);
    }

    /**
     * Returns $array restricted to the given $keys, preserving original order.
     *
     * @template K of array-key
     * @template T
     * @param array<K, T> $array
     * @param list<K> $keys
     * @return array<K, T>
     */
    public static function pick(array $array, array $keys): array {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Returns $array with the given $keys removed, preserving original order.
     *
     * @template K of array-key
     * @template T
     * @param array<K, T> $array
     * @param list<K> $keys
     * @return array<K, T>
     */
    public static function except(array $array, array $keys): array {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Returns the number of elements in the array.
     *
     * @param array<array-key, mixed> $array
     */
    public static function count(array $array): int {
        return count($array);
    }

    /**
     * Returns the array with its elements in reverse order. Integer keys are
     * renumbered from 0 (string keys are always kept) unless $preserveKeys is true.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return array<array-key, T>
     */
    public static function reverse(array $array, bool $preserveKeys = false): array {
        return array_reverse($array, $preserveKeys);
    }

    /**
     * Returns a slice of $length elements from the array starting at $offset
     * (negative $offset counts from the end; null $length runs to the end,
     * negative $length stops that many elements from the end). Integer keys are
     * renumbered (string keys kept) unless $preserveKeys is true.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return array<array-key, T>
     */
    public static function slice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array {
        return array_slice($array, $offset, $length, $preserveKeys);
    }

    /**
     * Returns the array with keys and values swapped. Values must be int or
     * string; on duplicate values the last key wins.
     *
     * @param array<array-key, int|string> $array
     * @return array<int|string, array-key>
     */
    public static function flip(array $array): array {
        return array_flip($array);
    }

    /**
     * Pairs each key in $keys with the value at the same position in $values.
     *
     * @template T
     * @param list<array-key> $keys
     * @param list<T> $values
     * @return array<array-key, T>
     * @throws RuntimeException When $keys and $values differ in length.
     */
    public static function combine(array $keys, array $values): array {
        if (count($keys) !== count($values)) {
            throw new RuntimeException('Keys and values must have the same number of elements.');
        }
        return array_combine($keys, $values);
    }

    /**
     * Returns the values of $array not present in any of $others (loose
     * string comparison, like {@see array_diff()}). Keys are preserved.
     *
     * @template T
     * @param array<array-key, T> $array
     * @param array<array-key, mixed> ...$others
     * @return array<array-key, T>
     */
    public static function diff(array $array, array ...$others): array {
        return $others === [] ? $array : array_diff($array, ...$others);
    }

    /**
     * Returns the values of $array present in every one of $others (loose
     * string comparison, like {@see array_intersect()}). Keys are preserved.
     *
     * @template T
     * @param array<array-key, T> $array
     * @param array<array-key, mixed> ...$others
     * @return array<array-key, T>
     */
    public static function intersect(array $array, array ...$others): array {
        return $others === [] ? $array : array_intersect($array, ...$others);
    }

    /**
     * Returns a map from each distinct value in $array to its occurrence count.
     * Values must be int or string (matching {@see array_count_values()}).
     *
     * @param array<array-key, int|string> $array
     * @return array<int|string, int>
     */
    public static function countValues(array $array): array {
        return array_count_values($array);
    }

    /**
     * Returns a new array with $values appended after the array's elements,
     * each under the next integer key (existing keys are preserved). $array
     * itself is left unchanged.
     *
     * @template T
     * @param array<array-key, T> $array
     * @param T ...$values
     * @return array<array-key, T>
     */
    public static function append(array $array, mixed ...$values): array {
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
     * @param array<array-key, T> $array
     * @param T ...$values
     * @return array<array-key, T>
     */
    public static function prepend(array $array, mixed ...$values): array {
        return $values === [] ? $array : array_merge($values, $array);
    }

    /**
     * Returns $array sorted by key with the natural `<=>` comparator, preserving
     * the key=>value association. Pass $desc = true for descending order.
     *
     * @template T
     * @param array<array-key, T> $array
     * @return array<array-key, T>
     */
    public static function sortKeys(array $array, bool $desc = false): array {
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
     * @param T $value
     * @return list<T>
     * @throws RuntimeException When $count is negative.
     */
    public static function fill(int $count, mixed $value): array {
        if ($count < 0) {
            throw new RuntimeException('Count must be non-negative.');
        }
        return array_fill(0, $count, $value);
    }

    /**
     * Returns an array mapping each key in $keys to $value.
     *
     * @template T
     * @param list<array-key> $keys
     * @param T $value
     * @return array<array-key, T>
     */
    public static function fillKeys(array $keys, mixed $value): array {
        return array_fill_keys($keys, $value);
    }

    /**
     * Returns a list of integers from $start to $end (both inclusive), advancing
     * by $step (negative steps walk backwards). Returns [] when the direction
     * cannot reach $end.
     *
     * @throws RuntimeException When $step is zero.
     * @return list<int>
     */
    public static function range(int $start, int $end, int $step = 1): array {
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
}
