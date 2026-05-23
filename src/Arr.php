<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

final class Arr {
    private function __construct() {}

    public static function isEmpty(array $array): bool {
        return $array === [];
    }

    public static function isNotEmpty(array $array): bool {
        return $array !== [];
    }

    public static function first(array $array): mixed {
        if ($array === []) {
            throw new RuntimeException('Cannot get first element of an empty array.');
        }
        return $array[array_key_first($array)];
    }

    public static function firstOrNull(array $array): mixed {
        if ($array === []) {
            return null;
        }
        return $array[array_key_first($array)];
    }

    public static function last(array $array): mixed {
        if ($array === []) {
            throw new RuntimeException('Cannot get last element of an empty array.');
        }
        return $array[array_key_last($array)];
    }

    public static function lastOrNull(array $array): mixed {
        if ($array === []) {
            return null;
        }
        return $array[array_key_last($array)];
    }

    public static function find(array $array, callable $predicate): mixed {
        foreach ($array as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }
        throw new RuntimeException('No element matches the predicate.');
    }

    public static function findOrNull(array $array, callable $predicate): mixed {
        foreach ($array as $key => $value) {
            if ($predicate($value, $key)) {
                return $value;
            }
        }
        return null;
    }

    public static function filter(array $array, callable $predicate): array {
        return array_filter($array, $predicate, ARRAY_FILTER_USE_BOTH);
    }

    public static function map(array $array, callable $callback): array {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = $callback($value, $key);
        }
        return $result;
    }

    public static function reduce(array $array, callable $callback, mixed $initial = null): mixed {
        return array_reduce($array, $callback, $initial);
    }

    /**
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
     * @return list<list<mixed>>
     */
    public static function chunk(array $array, int $size): array {
        if ($size < 1) {
            throw new RuntimeException('Chunk size must be at least 1.');
        }
        return array_chunk($array, $size);
    }

    /**
     * @return list<mixed>
     */
    public static function unique(array $array): array {
        return array_values(array_unique($array, SORT_REGULAR));
    }

    public static function has(array $array, int|string $key): bool {
        return array_key_exists($key, $array);
    }

    /**
     * @return list<int|string>
     */
    public static function keys(array $array): array {
        return array_keys($array);
    }

    /**
     * @return list<mixed>
     */
    public static function values(array $array): array {
        return array_values($array);
    }

    /**
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
