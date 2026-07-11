<?php

declare(strict_types=1);

namespace Rak200\Utils;

use Generator;
use RuntimeException;

/**
 * Lazy iterable helpers — the streaming counterpart of {@see Arr}.
 *
 * Every transform returns a `Generator` and pulls from its source one element
 * at a time, so transforms compose without materialising intermediate arrays
 * and sources may be infinite ({@see range()}, {@see repeat()}, {@see cycle()},
 * {@see iterate()}). Terminals ({@see toArray()}, {@see first()}, {@see reduce()},
 * {@see count()}, …) consume the source to produce a concrete value; those that
 * read it to the end ({@see toArray()}, {@see last()}, {@see reduce()},
 * {@see count()}, {@see every()} on an all-true source, …) never return on an
 * infinite source — bound it with {@see take()} first.
 *
 * **Single-pass — the one place the immutable/stateless contract bends.** A
 * `Generator` can be iterated only once. Passing the *same* generator to two
 * terminals fails: the first drains it, and PHP then throws "Cannot traverse an
 * already closed generator" on the second — re-derive it from its source
 * instead. Most lazy transforms preserve keys; {@see flatMap()}, {@see flatten()},
 * {@see values()}, {@see zip()} and {@see chunk()} re-index. Because preserved
 * keys can be non-sequential or collide, {@see toArray()} re-indexes by default
 * (pass `true` to keep keys).
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Iter
{
    private function __construct() {}

    /**
     * Yields integers from $start, advancing by $step. With $end null the
     * sequence is infinite; otherwise it stops once it passes $end (a negative
     * $step walks backwards), like {@see Arr::range()}.
     *
     * @return Generator<int, int>
     *
     * @throws RuntimeException when $step is zero
     */
    public static function range(int $start, ?int $end = null, int $step = 1): Generator
    {
        if ($step === 0) {
            throw new RuntimeException('Step cannot be zero.');
        }

        return self::doRange($start, $end, $step);
    }

    /**
     * Yields $value $times times, or forever when $times is null.
     *
     * @template T
     *
     * @param T $value
     *
     * @return Generator<int, T>
     *
     * @throws RuntimeException when $times is negative
     */
    public static function repeat(mixed $value, ?int $times = null): Generator
    {
        if ($times !== null && $times < 0) {
            throw new RuntimeException('Times must be non-negative.');
        }

        return self::doRepeat($value, $times);
    }

    /**
     * Yields the values of $iterable over and over, forever. The values are
     * buffered on the first pass, so a single-pass source (a fresh, not-yet-iterated
     * `Generator`) can be cycled; an empty source yields nothing rather than looping
     * forever.
     *
     * @template T
     *
     * @param iterable<T> $iterable
     *
     * @return Generator<int, T>
     */
    public static function cycle(iterable $iterable): Generator
    {
        $buffer = [];
        foreach ($iterable as $value) {
            $buffer[] = $value;

            yield $value;
        }
        if ($buffer === []) {
            return;
        }
        while (true) {
            foreach ($buffer as $value) {
                yield $value;
            }
        }
    }

    /**
     * Yields $seed, then $fn($seed), then $fn($fn($seed)), … — an infinite
     * sequence built by repeatedly applying $fn to the previous value.
     *
     * @template T
     *
     * @param T              $seed
     * @param callable(T): T $fn
     *
     * @return Generator<int, T>
     *
     * @suppress PHP0420
     */
    public static function iterate(mixed $seed, callable $fn): Generator
    {
        $value = $seed;
        while (true) {
            yield $value;
            $value = $fn($value);
        }
    }

    /**
     * Yields $fn(0), $fn(1), …, $fn($count - 1).
     *
     * @template T
     *
     * @param callable(int): T $fn
     *
     * @return Generator<int, T>
     *
     * @throws RuntimeException when $count is negative
     */
    public static function times(int $count, callable $fn): Generator
    {
        if ($count < 0) {
            throw new RuntimeException('Count must be non-negative.');
        }

        return self::doTimes($count, $fn);
    }

    /**
     * Lazily applies $callback to each element (called with value and key),
     * preserving keys.
     *
     * @template TKey
     * @template TValue
     * @template TResult
     *
     * @param iterable<TKey, TValue>          $iterable
     * @param callable(TValue, TKey): TResult $callback
     *
     * @return Generator<TKey, TResult>
     */
    public static function map(iterable $iterable, callable $callback): Generator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $callback($value, $key);
        }
    }

    /**
     * Lazily yields the elements for which $predicate (called with value and key)
     * returns true, preserving keys.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): bool $predicate
     *
     * @return Generator<TKey, TValue>
     */
    public static function filter(iterable $iterable, callable $predicate): Generator
    {
        foreach ($iterable as $key => $value) {
            if ($predicate($value, $key)) {
                yield $key => $value;
            }
        }
    }

    /**
     * Maps each element to an iterable via $callback (called with value and key)
     * and yields the results flattened one level, re-indexed.
     *
     * @template TKey
     * @template TValue
     * @template TResult
     *
     * @param iterable<TKey, TValue>                    $iterable
     * @param callable(TValue, TKey): iterable<TResult> $callback
     *
     * @return Generator<int, TResult>
     */
    public static function flatMap(iterable $iterable, callable $callback): Generator
    {
        foreach ($iterable as $key => $value) {
            foreach ($callback($value, $key) as $sub) {
                yield $sub;
            }
        }
    }

    /**
     * Lazily yields the first $count elements (preserving keys), then stops —
     * the standard way to bound an infinite source.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return Generator<TKey, TValue>
     *
     * @throws RuntimeException when $count is negative
     */
    public static function take(iterable $iterable, int $count): Generator
    {
        if ($count < 0) {
            throw new RuntimeException('Count must be non-negative.');
        }

        return self::doTake($iterable, $count);
    }

    /**
     * Lazily yields every element after the first $count (preserving keys).
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return Generator<TKey, TValue>
     *
     * @throws RuntimeException when $count is negative
     */
    public static function drop(iterable $iterable, int $count): Generator
    {
        if ($count < 0) {
            throw new RuntimeException('Count must be non-negative.');
        }

        return self::doDrop($iterable, $count);
    }

    /**
     * Lazily yields elements (preserving keys) while $predicate (called with
     * value and key) holds, stopping at the first element that fails it.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): bool $predicate
     *
     * @return Generator<TKey, TValue>
     */
    public static function takeWhile(iterable $iterable, callable $predicate): Generator
    {
        foreach ($iterable as $key => $value) {
            if (!$predicate($value, $key)) {
                return;
            }

            yield $key => $value;
        }
    }

    /**
     * Lazily skips the leading run of elements for which $predicate (called with
     * value and key) holds, then yields the rest (preserving keys).
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): bool $predicate
     *
     * @return Generator<TKey, TValue>
     */
    public static function dropWhile(iterable $iterable, callable $predicate): Generator
    {
        $dropping = true;
        foreach ($iterable as $key => $value) {
            if ($dropping && $predicate($value, $key)) {
                continue;
            }
            $dropping = false;

            yield $key => $value;
        }
    }

    /**
     * Lazily yields chunks (0-indexed lists) of at most $size elements; the
     * final chunk may be smaller.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return Generator<int, non-empty-list<TValue>>
     *
     * @throws RuntimeException when $size is less than 1
     */
    public static function chunk(iterable $iterable, int $size): Generator
    {
        if ($size < 1) {
            throw new RuntimeException('Chunk size must be at least 1.');
        }

        return self::doChunk($iterable, $size);
    }

    /**
     * Lazily yields the elements of nested iterables flattened down to $depth
     * levels (re-indexed). Unlike {@see Arr::flatten()}, any nested iterable
     * (array or `Traversable`) is descended into, not only arrays.
     *
     * @param iterable<mixed> $iterable
     *
     * @return Generator<int, mixed>
     *
     * @throws RuntimeException when $depth is negative
     */
    public static function flatten(iterable $iterable, int $depth = PHP_INT_MAX): Generator
    {
        if ($depth < 0) {
            throw new RuntimeException('Depth must be non-negative.');
        }

        return self::doFlatten($iterable, $depth);
    }

    /**
     * Lazily yields tuples (0-indexed lists) pairing the elements of $iterables
     * position by position. Unlike {@see Arr::zip()} (which pads to the longest
     * input), the lazy zip stops at the shortest — the only well-defined choice
     * when an input may be infinite.
     *
     * @param iterable<mixed> ...$iterables
     *
     * @return Generator<int, list<mixed>>
     */
    public static function zip(iterable ...$iterables): Generator
    {
        if ($iterables === []) {
            return;
        }
        $iterators = [];
        foreach ($iterables as $iterable) {
            $iterators[] = self::toIterator($iterable);
        }
        $first = true;
        while (true) {
            $tuple = [];
            foreach ($iterators as $iterator) {
                if ($first) {
                    $iterator->rewind();
                } else {
                    $iterator->next();
                }
                if (!$iterator->valid()) {
                    return;
                }
                $tuple[] = $iterator->current();
            }
            $first = false;

            yield $tuple;
        }
    }

    /**
     * Lazily yields the elements of each iterable in turn, preserving keys (so
     * {@see toArray()} re-indexes to avoid collisions).
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> ...$iterables
     *
     * @return Generator<TKey, TValue>
     */
    public static function concat(iterable ...$iterables): Generator
    {
        foreach ($iterables as $iterable) {
            yield from $iterable;
        }
    }

    /**
     * Lazily yields the keys of $iterable as values.
     *
     * @template TKey
     *
     * @param iterable<TKey, mixed> $iterable
     *
     * @return Generator<int, TKey>
     */
    public static function keys(iterable $iterable): Generator
    {
        foreach ($iterable as $key => $value) {
            yield $key;
        }
    }

    /**
     * Lazily yields the values of $iterable, re-indexed.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return Generator<int, TValue>
     */
    public static function values(iterable $iterable): Generator
    {
        foreach ($iterable as $value) {
            yield $value;
        }
    }

    /**
     * Lazily yields the first occurrence of each distinct value (strict
     * comparison), preserving keys. Buffers the values seen so far (O(n) memory).
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return Generator<TKey, TValue>
     */
    public static function unique(iterable $iterable): Generator
    {
        $seen = [];
        foreach ($iterable as $key => $value) {
            if (Arr::contains($seen, $value, true)) {
                continue;
            }
            $seen[] = $value;

            yield $key => $value;
        }
    }

    /**
     * Lazily yields $length elements starting at $offset (preserving keys); a
     * null $length runs to the end. Both bounds must be non-negative — counting
     * from the end is impossible on a lazy or infinite source. With a null
     * $length, slicing an infinite source never ends — cap it with {@see take()}.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return Generator<TKey, TValue>
     *
     * @throws RuntimeException when $offset or $length is negative
     */
    public static function slice(iterable $iterable, int $offset, ?int $length = null): Generator
    {
        if ($offset < 0) {
            throw new RuntimeException('Offset must be non-negative.');
        }
        if ($length !== null && $length < 0) {
            throw new RuntimeException('Length must be non-negative.');
        }

        return self::doSlice($iterable, $offset, $length);
    }

    /**
     * Lazily yields each element unchanged (preserving keys) after invoking
     * $callback on it (with value and key) for its side effect — a peek into a
     * lazy pipeline, the immutable stand-in for an impure `each`.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): void $callback
     *
     * @return Generator<TKey, TValue>
     */
    public static function tap(iterable $iterable, callable $callback): Generator
    {
        foreach ($iterable as $key => $value) {
            $callback($value, $key);

            yield $key => $value;
        }
    }

    /**
     * Returns the first element, consuming the source until one is found.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return TValue
     *
     * @throws RuntimeException when the source is empty
     */
    public static function first(iterable $iterable): mixed
    {
        foreach ($iterable as $value) {
            return $value;
        }

        throw new RuntimeException('Cannot get first element of an empty iterable.');
    }

    /**
     * Returns the first element, or null when the source is empty.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return null|TValue
     */
    public static function firstOrNull(iterable $iterable): mixed
    {
        foreach ($iterable as $value) {
            return $value;
        }

        return null;
    }

    /**
     * Returns the last element, consuming the entire source.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return TValue
     *
     * @throws RuntimeException when the source is empty
     */
    public static function last(iterable $iterable): mixed
    {
        $found = false;
        $last = null;
        foreach ($iterable as $value) {
            $last = $value;
            $found = true;
        }
        if (!$found) {
            throw new RuntimeException('Cannot get last element of an empty iterable.');
        }

        return $last;
    }

    /**
     * Returns the last element, or null when the source is empty.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return null|TValue
     */
    public static function lastOrNull(iterable $iterable): mixed
    {
        $last = null;
        foreach ($iterable as $value) {
            $last = $value;
        }

        return $last;
    }

    /**
     * Returns the first element matching $predicate (called with value and key),
     * consuming the source until one matches.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): bool $predicate
     *
     * @return TValue
     *
     * @throws RuntimeException when no element matches
     */
    public static function find(iterable $iterable, callable $predicate): mixed
    {
        foreach ($iterable as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }

        throw new RuntimeException('No element matches the predicate.');
    }

    /**
     * Returns the first element matching $predicate, or null when none match.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): bool $predicate
     *
     * @return null|TValue
     */
    public static function findOrNull(iterable $iterable, callable $predicate): mixed
    {
        foreach ($iterable as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns the element at 0-based position $n, consuming the source up to it.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return TValue
     *
     * @throws RuntimeException when $n is negative or beyond the last element
     */
    public static function nth(iterable $iterable, int $n): mixed
    {
        if ($n < 0) {
            throw new RuntimeException('Index must be non-negative.');
        }
        $index = 0;
        foreach ($iterable as $value) {
            if ($index === $n) {
                return $value;
            }
            ++$index;
        }

        throw new RuntimeException("No element at index {$n}.");
    }

    /**
     * Returns the element at 0-based position $n, or null when it is beyond the
     * last element.
     *
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return null|TValue
     *
     * @throws RuntimeException when $n is negative
     */
    public static function nthOrNull(iterable $iterable, int $n): mixed
    {
        if ($n < 0) {
            throw new RuntimeException('Index must be non-negative.');
        }
        $index = 0;
        foreach ($iterable as $value) {
            if ($index === $n) {
                return $value;
            }
            ++$index;
        }

        return null;
    }

    /**
     * Reduces the source to a single value by repeatedly applying $callback,
     * consuming the source.
     *
     * @template TKey
     * @template TValue
     * @template TCarry
     *
     * @param iterable<TKey, TValue>                 $iterable
     * @param callable(TCarry, TValue, TKey): TCarry $callback receives the accumulator, current value, and key
     * @param TCarry                                 $initial  starting accumulator value
     *
     * @return TCarry
     */
    public static function reduce(iterable $iterable, callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($iterable as $key => $value) {
            $carry = $callback($carry, $value, $key);
        }

        return $carry;
    }

    /**
     * Returns true when the source yields no elements. Probes at most one
     * element — an infinite source is fine — but probing **advances** the
     * source: a Generator has its first element consumed.
     *
     * @param iterable<mixed> $iterable
     */
    public static function isEmpty(iterable $iterable): bool
    {
        foreach ($iterable as $value) {
            return false;
        }

        return true;
    }

    /**
     * Returns true when the source yields at least one element. Negation of
     * {@see isEmpty()}, with the same single-element probing caveat.
     *
     * @param iterable<mixed> $iterable
     */
    public static function isNotEmpty(iterable $iterable): bool
    {
        return !self::isEmpty($iterable);
    }

    /**
     * Returns the number of elements, consuming the source.
     *
     * @param iterable<mixed> $iterable
     */
    public static function count(iterable $iterable): int
    {
        $count = 0;
        foreach ($iterable as $value) {
            ++$count;
        }

        return $count;
    }

    /**
     * Returns true when $value occurs in the source (strict comparison by
     * default), short-circuiting at the first match.
     *
     * @param iterable<mixed> $iterable
     */
    public static function contains(iterable $iterable, mixed $value, bool $strict = true): bool
    {
        foreach ($iterable as $item) {
            if ($strict ? $item === $value : $item == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true when $predicate (called with value and key) holds for at
     * least one element, short-circuiting at the first match.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): bool $predicate
     */
    public static function any(iterable $iterable, callable $predicate): bool
    {
        foreach ($iterable as $key => $value) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true when $predicate (called with value and key) holds for every
     * element (vacuously true for an empty source), short-circuiting at the
     * first failure.
     *
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue>       $iterable
     * @param callable(TValue, TKey): bool $predicate
     */
    public static function every(iterable $iterable, callable $predicate): bool
    {
        foreach ($iterable as $key => $value) {
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Materialises the source into an array, consuming it. Re-indexes to a
     * 0-based list by default; pass $preserveKeys = true to keep keys (later
     * duplicates then overwrite earlier ones).
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return ($preserveKeys is true ? array<TKey, TValue> : list<TValue>)
     */
    public static function toArray(iterable $iterable, bool $preserveKeys = false): array
    {
        $result = [];
        if ($preserveKeys) {
            foreach ($iterable as $key => $value) {
                $result[$key] = $value;
            }
        } else {
            foreach ($iterable as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * @return Generator<int, int>
     */
    private static function doRange(int $start, ?int $end, int $step): Generator
    {
        if ($end === null) {
            yield from self::infiniteRange($start, $step);

            return;
        }
        $value = $start;
        while ($step > 0 ? $value <= $end : $value >= $end) {
            yield $value;
            // Stop before $value += $step would pass $end or overflow the int domain.
            if ($step > 0 ? $end - $value < $step : $value - $end < -$step) {
                return;
            }
            $value += $step;
        }
    }

    /**
     * @return Generator<int, int>
     *
     * @suppress PHP0420
     */
    private static function infiniteRange(int $start, int $step): Generator
    {
        $value = $start;
        while (true) {
            yield $value;
            $value += $step;
        }
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return Generator<int, T>
     */
    private static function doRepeat(mixed $value, ?int $times): Generator
    {
        if ($times === null) {
            yield from self::infiniteRepeat($value);

            return;
        }
        for ($i = 0; $i < $times; ++$i) {
            yield $value;
        }
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return Generator<int, T>
     *
     * @suppress PHP0420
     */
    private static function infiniteRepeat(mixed $value): Generator
    {
        while (true) {
            yield $value;
        }
    }

    /**
     * @template T
     *
     * @param callable(int): T $fn
     *
     * @return Generator<int, T>
     */
    private static function doTimes(int $count, callable $fn): Generator
    {
        for ($i = 0; $i < $count; ++$i) {
            yield $fn($i);
        }
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return Generator<TKey, TValue>
     */
    private static function doTake(iterable $iterable, int $count): Generator
    {
        if ($count === 0) {
            return;
        }
        $taken = 0;
        foreach ($iterable as $key => $value) {
            yield $key => $value;
            if (++$taken === $count) {
                return;
            }
        }
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return Generator<TKey, TValue>
     */
    private static function doDrop(iterable $iterable, int $count): Generator
    {
        $dropped = 0;
        foreach ($iterable as $key => $value) {
            if ($dropped < $count) {
                ++$dropped;

                continue;
            }

            yield $key => $value;
        }
    }

    /**
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return Generator<int, non-empty-list<TValue>>
     */
    private static function doChunk(iterable $iterable, int $size): Generator
    {
        $buffer = [];
        $n = 0;
        foreach ($iterable as $value) {
            $buffer[] = $value;
            if (++$n === $size) {
                yield $buffer;
                $buffer = [];
                $n = 0;
            }
        }
        if ($buffer !== []) {
            yield $buffer;
        }
    }

    /**
     * @param iterable<mixed> $iterable
     *
     * @return Generator<int, mixed>
     */
    private static function doFlatten(iterable $iterable, int $depth): Generator
    {
        foreach ($iterable as $item) {
            if ($depth > 0 && Type::isIterable($item)) {
                foreach (self::doFlatten($item, $depth - 1) as $sub) {
                    yield $sub;
                }
            } else {
                yield $item;
            }
        }
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param iterable<TKey, TValue> $iterable
     *
     * @return Generator<TKey, TValue>
     */
    private static function doSlice(iterable $iterable, int $offset, ?int $length): Generator
    {
        if ($length === 0) {
            return;
        }
        $index = 0;
        $taken = 0;
        foreach ($iterable as $key => $value) {
            if ($index++ < $offset) {
                continue;
            }

            yield $key => $value;
            if ($length !== null && ++$taken === $length) {
                return;
            }
        }
    }

    /**
     * Wraps any iterable in a fresh single-pass generator so {@see zip()} can
     * drive several sources in lockstep via the `Iterator` protocol (a
     * `Generator` is an `Iterator`), regardless of the concrete source type.
     *
     * @param iterable<mixed> $iterable
     *
     * @return Generator<mixed, mixed>
     */
    private static function toIterator(iterable $iterable): Generator
    {
        yield from $iterable;
    }
}
