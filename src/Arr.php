<?php

declare(strict_types=1);

namespace Rak200\Utils;

use Rak200\Utils\Exception\BadCallbackException;
use Rak200\Utils\Exception\EmptySourceException;
use Rak200\Utils\Exception\LookupException;
use Rak200\Utils\Exception\MalformedArgumentException;

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
use function array_splice;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function krsort;
use function ksort;
use function max;
use function uasort;
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
     *
     * @phpstan-assert-if-true list<mixed> $value
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
     * @throws EmptySourceException when the array is empty
     */
    public static function first(array $array): mixed
    {
        if ($array === []) {
            throw new EmptySourceException('Cannot get first element of an empty array.');
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
     * @throws EmptySourceException when the array is empty
     */
    public static function last(array $array): mixed
    {
        if ($array === []) {
            throw new EmptySourceException('Cannot get last element of an empty array.');
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
     * @throws EmptySourceException when the array is empty
     */
    public static function firstKey(array $array): int|string
    {
        if ($array === []) {
            throw new EmptySourceException('Cannot get first key of an empty array.');
        }

        return array_key_first($array);
    }

    /**
     * Returns the first key of the array, or null if it is empty.
     *
     * Carries the array's own key type, and drops the `null` arm outright when
     * the array is statically non-empty. The conditional is true here — a
     * non-empty array always has a first key — unlike on {@see searchOrNull()},
     * where the same shape would claim a non-empty array always contains the
     * value, which is why that one stays wide.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return ($array is non-empty-array ? K : null)
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
     * @throws EmptySourceException when the array is empty
     */
    public static function lastKey(array $array): int|string
    {
        if ($array === []) {
            throw new EmptySourceException('Cannot get last key of an empty array.');
        }

        return array_key_last($array);
    }

    /**
     * Returns the last key of the array, or null if it is empty.
     *
     * Carries the array's own key type, and drops the `null` arm outright when
     * the array is statically non-empty — see {@see firstKeyOrNull()} for why
     * the conditional is legitimate here and not on {@see searchOrNull()}.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return ($array is non-empty-array ? K : null)
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
     * @throws LookupException when no element matches
     */
    public static function find(array $array, callable $predicate): mixed
    {
        foreach ($array as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }

        throw new LookupException('No element matches the predicate.');
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
     * @return ($array is list<T> ? list<TResult> : array<K, TResult>)
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
     * Maps each element to an iterable via $callback (called with value and key)
     * and returns the results flattened one level, re-indexed as a 0-based list.
     * The eager twin of {@see Iter::flatMap()}; one level only, so a callback
     * returning nested iterables leaves the inner ones untouched.
     *
     * @template K of array-key
     * @template T
     * @template R
     *
     * @param array<K, T>                 $array
     * @param callable(T, K): iterable<R> $callback
     *
     * @return list<R>
     *
     * @throws BadCallbackException when $callback returns a non-iterable
     */
    public static function flatMap(array $array, callable $callback): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $mapped = $callback($value, $key);
            // The guard is unreachable for a caller that honours the declared
            // `iterable<R>`, which is exactly why PHPStan reports it — but the
            // declaration is PHPDoc on a public API, not something PHP enforces.
            // Without the guard an untyped caller loses the element in silence:
            // `foreach` over a non-iterable only warns and skips.
            // @phpstan-ignore staticMethod.alreadyNarrowedType
            if (!Type::isIterable($mapped)) {
                throw new BadCallbackException('Callback must return an iterable. Got: ' . Type::of($mapped));
            }
            foreach ($mapped as $sub) {
                $result[] = $sub;
            }
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
     * @throws MalformedArgumentException when $depth is negative
     */
    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        if ($depth < 0) {
            throw new MalformedArgumentException('Depth must be non-negative.');
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
     * @throws MalformedArgumentException when $size is less than 1
     */
    public static function chunk(array $array, int $size): array
    {
        if ($size < 1) {
            throw new MalformedArgumentException('Chunk size must be at least 1.');
        }

        return array_chunk($array, $size);
    }

    /**
     * Returns the unique values of the array as a 0-indexed list, keeping the
     * first occurrence of each value under strict comparison — so values of
     * different types never collapse (`1` and `'1'` both survive).
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return list<T>
     */
    public static function unique(array $array): array
    {
        $result = [];
        foreach ($array as $value) {
            if (!self::contains($result, $value, true)) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns true if $path resolves in the array as a dot-path `'a.b.c'`
     * traversed level by level (an int or dotless string is a single-segment
     * lookup). A present null value counts as resolved. For a literal-key check
     * that never splits on dots, use {@see hasKey()}.
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
     * Returns the value at the dot-path $path `'a.b.c'`, traversed level by level
     * (an int or dotless string is a single-segment lookup). For a literal-key
     * read that never splits on dots, use {@see getKey()}.
     *
     * @param array<array-key, mixed> $array
     *
     * @throws LookupException when $path does not resolve
     */
    public static function get(array $array, int|string $path): mixed
    {
        [$found, $value] = self::resolvePath($array, $path);
        if (!$found) {
            throw new LookupException("Path \"{$path}\" not found in array.");
        }

        return $value;
    }

    /**
     * Returns the value at the dot-path $path, or null when it does not resolve.
     * See {@see get()}; for a literal-key read use {@see getKeyOrNull()}.
     *
     * @param array<array-key, mixed> $array
     */
    public static function getOrNull(array $array, int|string $path): mixed
    {
        return self::resolvePath($array, $path)[1];
    }

    /**
     * Returns the value at the literal key $key, without dot-path interpretation
     * (including null values) — the literal-key counterpart to the dot-aware
     * {@see get()}.
     *
     * @param array<array-key, mixed> $array
     *
     * @throws LookupException when $key is not present
     */
    public static function getKey(array $array, int|string $key): mixed
    {
        if (!array_key_exists($key, $array)) {
            throw new LookupException("Key \"{$key}\" not found in array.");
        }

        return $array[$key];
    }

    /**
     * Returns the value at the literal key $key (including null values), or null
     * when it is not present — the literal-key counterpart to {@see getOrNull()}.
     *
     * @param array<array-key, mixed> $array
     */
    public static function getKeyOrNull(array $array, int|string $key): mixed
    {
        return array_key_exists($key, $array) ? $array[$key] : null;
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
     * Carries the array's own key type: over a `list` the result is typed
     * `int<0, max>`, where {@see searchOrNull()} can only widen to
     * `int|string|null` — see the note there.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return K
     *
     * @throws LookupException when $value is not present
     */
    public static function search(array $array, mixed $value, bool $strict = true): int|string
    {
        // array_keys() with a search value, not array_search(): over
        // `array<K, mixed>` the latter's stub yields `int|string|false` — it does
        // not propagate the key template — so its result cannot prove the K this
        // method promises, and no annotation fixes that from the outside.
        // array_keys() returns `list<K>` and does. The comparison is `==` / `===`
        // exactly as the $strict flag selects in either native, so the first
        // match is the same key. The cost is one full scan with no early exit,
        // plus a list of every match where only the first is used.
        $keys = array_keys($array, $value, $strict);
        if ($keys === []) {
            throw new LookupException('Value not found in array.');
        }

        return $keys[0];
    }

    /**
     * Returns the key of the first element equal to $value (strict comparison by
     * default), or null when $value is not present.
     *
     * Unlike {@see search()}, this cannot carry the array's key type. The result
     * is intrinsically `K|null` — the value may simply be absent — and PHPStan
     * does not resolve a template inside a union with null, neither as a plain
     * return nor inside a conditional branch. The one shape that does resolve,
     * `($array is non-empty-array ? K : null)`, would be false here: it claims a
     * non-empty array always finds the value, which would make every caller's
     * `=== null` check look like dead code. The wider type is the honest one, so
     * the `null|K` below degrades to `int|string|null` at every call site — it is
     * spelled with the template to state the intent, and to pick the precision up
     * for free should PHPStan ever resolve it. Use {@see search()} when you need
     * the precise key and can treat a miss as exceptional.
     *
     * @template K of array-key
     *
     * @param array<K, mixed> $array
     *
     * @return null|K
     */
    public static function searchOrNull(array $array, mixed $value, bool $strict = true): int|string|null
    {
        $key = array_search($value, $array, $strict);

        return $key === false ? null : $key;
    }

    /**
     * Returns the 0-based position of $key in the array's iteration order — the
     * positional counterpart of {@see search()}, which answers value => key.
     * Key matching follows {@see hasKey()}: PHP normalises a numeric-string key
     * to an int on write, so `'1'` finds the key `1`.
     *
     * @param array<array-key, mixed> $array
     *
     * @return int<0, max>
     *
     * @throws LookupException when $key is not present
     */
    public static function keyPosition(array $array, int|string $key): int
    {
        $position = self::keyPositionOrNull($array, $key);
        if ($position === null) {
            throw new LookupException("Key \"{$key}\" not found in array.");
        }

        return $position;
    }

    /**
     * Returns the 0-based position of $key in the array's iteration order, or
     * null when $key is not present.
     *
     * @param array<array-key, mixed> $array
     *
     * @return null|int<0, max>
     */
    public static function keyPositionOrNull(array $array, int|string $key): ?int
    {
        // PHP normalises a numeric-string key to int on write, so normalise the
        // needle the same way — otherwise '1' would miss the key 1 that
        // hasKey()/array_key_exists() report as present.
        $normalized = array_key_first([$key => null]);

        $position = 0;
        foreach ($array as $existing => $ignored) {
            if ($existing === $normalized) {
                return $position;
            }
            ++$position;
        }

        return null;
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
     * @throws LookupException      when $indexKey is given and an item lacks it
     * @throws BadCallbackException when the resolved key is not an int or string
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
     * @throws LookupException      when $key is a column name and an item is
     *                              not an array or lacks that column
     * @throws BadCallbackException when the resolved key is not an int or string
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
                    throw new LookupException("Item missing key \"{$key}\"");
                }
                $k = $item[$key];
            }
            if (!Num::isInt($k) && !Str::is($k)) {
                throw new BadCallbackException('Resolved key must be an int or string.');
            }
            $result[$k] = $item;
        }

        return $result;
    }

    /**
     * Returns $array sorted with the natural `<=>` comparator (or $comparator
     * when given). Values are re-indexed as a 0-based list unless $preserveKeys
     * is true, which keeps each value under its own key ({@see uasort()}
     * semantics). Note that a `list` sorted with $preserveKeys does *not* come
     * back a list — its keys survive, in their new order.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>              $array
     * @param null|callable(T, T): int $comparator
     *
     * @return ($preserveKeys is true ? array<K, T> : list<T>)
     */
    public static function sort(array $array, ?callable $comparator = null, bool $preserveKeys = false): array
    {
        $comparator ??= static fn (mixed $a, mixed $b): int => $a <=> $b;

        if ($preserveKeys) {
            uasort($array, $comparator);

            return $array;
        }

        // @infection-ignore-all: usort reindexes its subject anyway; the explicit array_values only pins the list type
        $values = array_values($array);
        usort($values, $comparator);

        return $values;
    }

    /**
     * Returns $array sorted by the value produced by $keyExtractor for each
     * element (called with value and key). Values are re-indexed as a 0-based
     * list unless $preserveKeys is true, which keeps each value under its own
     * key — the same axis {@see sort()} offers, sorting by a derived key
     * instead of by the value itself.
     *
     * @template K of array-key
     * @template T
     *
     * @param array<K, T>           $array
     * @param callable(T, K): mixed $keyExtractor
     *
     * @return ($preserveKeys is true ? array<K, T> : list<T>)
     */
    public static function sortBy(array $array, callable $keyExtractor, bool $preserveKeys = false): array
    {
        $annotated = [];
        foreach ($array as $key => $value) {
            $annotated[$key] = [$keyExtractor($value, $key), $value];
        }

        $comparator = static fn (array $a, array $b): int => $a[0] <=> $b[0];
        if ($preserveKeys) {
            uasort($annotated, $comparator);

            return array_map(static fn (array $pair): mixed => $pair[1], $annotated);
        }
        usort($annotated, $comparator);

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
     * Returns the number of elements in the array. Typed `int<0, max>`, so the
     * result satisfies a {@see Countable::count()} implementation directly.
     *
     * @param array<array-key, mixed> $array
     *
     * @return int<0, max>
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
     * @return ($array is list<T> ? ($preserveKeys is true ? array<int, T> : list<T>) : array<array-key, T>)
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
     * @return ($array is list<T> ? ($preserveKeys is true ? array<int, T> : list<T>) : array<array-key, T>)
     */
    public static function slice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array
    {
        return array_slice($array, $offset, $length, $preserveKeys);
    }

    /**
     * Returns $array with $length elements removed starting at $index, closing
     * the gap — the pure form of {@see array_splice()}, which mutates its
     * subject. The input is left untouched.
     *
     * Index and length follow {@see slice()}: a negative $index counts from the
     * end (clamped to the start once it reaches past it), and a negative $length
     * stops that many elements before the end. An $index past the end, or a
     * $length that resolves to nothing, removes nothing. Integer keys are
     * renumbered, string keys kept.
     *
     * To drop the first or last element and keep it, see {@see shift()} /
     * {@see pop()}.
     *
     * @template T
     *
     * @param array<array-key, T> $array
     *
     * @return ($array is list<T> ? list<T> : array<array-key, T>)
     */
    public static function removeAt(array $array, int $index, int $length = 1): array
    {
        // array_splice mutates, but $array is by value, so the caller's array is
        // untouched — the same way sortKeys() uses ksort(). Delegating instead
        // of composing slice() + merge() keeps every edge case (negative-index
        // clamping, negative length, out-of-range) identical to the native by
        // construction rather than by re-derivation.
        array_splice($array, $index, $length);

        return $array;
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
     * @throws MalformedArgumentException when $keys and $values differ in length
     */
    public static function combine(array $keys, array $values): array
    {
        if (count($keys) !== count($values)) {
            throw new MalformedArgumentException('Keys and values must have the same number of elements.');
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
     * @return ($array is list<T> ? list<T> : array<array-key, T>)
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
     * @return ($array is list<T> ? list<T> : array<array-key, T>)
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
     * @return ($array is list<T> ? array{0: T, 1: list<T>} : array{0: T, 1: array<array-key, T>})
     *
     * @throws EmptySourceException when the array is empty
     */
    public static function shift(array $array): array
    {
        if ($array === []) {
            throw new EmptySourceException('Cannot shift from an empty array.');
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
     * @return ($array is list<T> ? null|array{0: T, 1: list<T>} : null|array{0: T, 1: array<array-key, T>})
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
     * @return ($array is list<T> ? array{0: T, 1: list<T>} : array{0: T, 1: array<array-key, T>})
     *
     * @throws EmptySourceException when the array is empty
     */
    public static function pop(array $array): array
    {
        if ($array === []) {
            throw new EmptySourceException('Cannot pop from an empty array.');
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
     * @return ($array is list<T> ? null|array{0: T, 1: list<T>} : null|array{0: T, 1: array<array-key, T>})
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
     * @return ($array is list<T> ? ($desc is true ? array<int, T> : list<T>) : array<array-key, T>)
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
     * @throws MalformedArgumentException when $count is negative
     */
    public static function fill(int $count, mixed $value): array
    {
        if ($count < 0) {
            throw new MalformedArgumentException('Count must be non-negative.');
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
     * @throws MalformedArgumentException when $step is zero
     */
    public static function range(int $start, int $end, int $step = 1): array
    {
        if ($step === 0) {
            throw new MalformedArgumentException('Step cannot be zero.');
        }
        // @infection-ignore-all: readability guard — for these inputs both loop conditions below fail on entry, so
        // falling through (or mismatching the guard) still yields []
        if (($step > 0 && $start > $end) || ($step < 0 && $start < $end)) {
            return [];
        }
        $result = [];
        if (/* @infection-ignore-all: step 0 already threw, so > and >= agree */ $step > 0) {
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
     * Resolves $path against $array by dot-traversal — a string splits on `.`
     * into segments, an int is a single numeric key — returning a `[found, value]`
     * pair so callers can tell a missing path from a present null.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array{0: bool, 1: mixed}
     */
    private static function resolvePath(array $array, int|string $path): array
    {
        $segments = Num::isInt($path) ? [$path] : Str::split((string) $path, '.');
        $current = $array;
        foreach ($segments as $segment) {
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

            // @infection-ignore-all: falling through skips the recursion (the key was just unset) and returns the same array
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
            $compound = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value) && $value !== []) {
                self::dotInto($value, $compound, $result);
            } else {
                $result[$compound] = $value;
            }
        }
    }
}
