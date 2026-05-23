<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

/**
 * Array helpers.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Arr {
    private function __construct() {}

    /**
     * Returns true if the array has no elements.
     */
    public static function isEmpty(array $array): bool {
        return $array === [];
    }

    /**
     * Returns true if the array has at least one element.
     */
    public static function isNotEmpty(array $array): bool {
        return $array !== [];
    }

    /**
     * Returns the first element of the array.
     *
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
     */
    public static function lastOrNull(array $array): mixed {
        if ($array === []) {
            return null;
        }
        return $array[array_key_last($array)];
    }

    /**
     * Returns the first element matching $predicate (called with value and key).
     *
     * @param callable(mixed, int|string): bool $predicate
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
     * @param callable(mixed, int|string): bool $predicate
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
     * @param callable(mixed, int|string): bool $predicate
     */
    public static function filter(array $array, callable $predicate): array {
        return array_filter($array, $predicate, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Returns a new array with $callback applied to each element. Keys are
     * preserved; $callback receives value and key.
     *
     * @param callable(mixed, int|string): mixed $callback
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
     * @param callable(mixed, mixed): mixed $callback Receives the accumulator and the current value.
     */
    public static function reduce(array $array, callable $callback, mixed $initial = null): mixed {
        return array_reduce($array, $callback, $initial);
    }

    /**
     * Flattens nested arrays down to $depth levels. Use PHP_INT_MAX (default)
     * to flatten completely. Keys are not preserved.
     *
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
     * @param callable(mixed, int|string): (int|string) $classifier
     * @return array<int|string, list<mixed>>
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
     * @param callable(mixed, int|string): bool $predicate
     * @return array{0: list<mixed>, 1: list<mixed>}
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
     * @throws RuntimeException When $size is less than 1.
     * @return list<list<mixed>>
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
     * @return list<mixed>
     */
    public static function unique(array $array): array {
        return array_values(array_unique($array, SORT_REGULAR));
    }

    /**
     * Returns true if $key exists in the array (including null values).
     */
    public static function has(array $array, int|string $key): bool {
        return array_key_exists($key, $array);
    }

    /**
     * Returns the keys of the array, in their original order.
     *
     * @return list<int|string>
     */
    public static function keys(array $array): array {
        return array_keys($array);
    }

    /**
     * Returns the values of the array re-indexed as a 0-based list.
     *
     * @return list<mixed>
     */
    public static function values(array $array): array {
        return array_values($array);
    }

    /**
     * Combines the input arrays element-wise. The result length matches the
     * longest input; shorter inputs are padded with null.
     *
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
