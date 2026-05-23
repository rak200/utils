<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RoundingMode;
use RuntimeException;

/**
 * Numeric helpers for parsing, formatting, and aggregation.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Num {
    private function __construct() {}

    /**
     * Returns true if $value is a native PHP int.
     */
    public static function isInteger(mixed $value): bool {
        return is_int($value);
    }

    /**
     * Returns true if $value is a native PHP float.
     */
    public static function isFloat(mixed $value): bool {
        return is_float($value);
    }

    /**
     * Returns true if $value is an int, a float, or a numeric string.
     */
    public static function isNumeric(mixed $value): bool {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
    }

    /**
     * Parses $value as an integer in the given $base (2-36).
     *
     * @throws RuntimeException When $value is not a valid integer in $base, or $base is out of range.
     */
    public static function parseInt(string $value, int $base = 10): int {
        $parsed = self::parseIntOrNull($value, $base);
        if ($parsed === null) {
            throw new RuntimeException(sprintf('Cannot parse "%s" as integer in base %d.', $value, $base));
        }
        return $parsed;
    }

    /**
     * Parses $value as an integer in the given $base (2-36); returns null on failure.
     *
     * @throws RuntimeException When $base is outside 2-36.
     */
    public static function parseIntOrNull(string $value, int $base = 10): ?int {
        if ($base < 2 || $base > 36) {
            throw new RuntimeException('Base must be between 2 and 36.');
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $sign = 1;
        if ($value[0] === '-') {
            $sign = -1;
            $value = substr($value, 1);
        } elseif ($value[0] === '+') {
            $value = substr($value, 1);
        }
        if ($value === '') {
            return null;
        }

        $result = 0;
        foreach (str_split(strtolower($value)) as $char) {
            $digit = match (true) {
                $char >= '0' && $char <= '9' => ord($char) - ord('0'),
                $char >= 'a' && $char <= 'z' => 10 + ord($char) - ord('a'),
                default => -1,
            };
            if ($digit < 0 || $digit >= $base) {
                return null;
            }
            $result = $result * $base + $digit;
        }
        return $sign * $result;
    }

    /**
     * Parses $value as a float.
     *
     * @throws RuntimeException When $value is not numeric.
     */
    public static function parseFloat(string $value): float {
        $parsed = self::parseFloatOrNull($value);
        if ($parsed === null) {
            throw new RuntimeException(sprintf('Cannot parse "%s" as float.', $value));
        }
        return $parsed;
    }

    /**
     * Parses $value as a float; returns null when $value is not numeric.
     */
    public static function parseFloatOrNull(string $value): ?float {
        $value = trim($value);
        if (!is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }

    /**
     * Constrains $value to the closed interval [$min, $max].
     *
     * @throws RuntimeException When $min > $max.
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float {
        if ($min > $max) {
            throw new RuntimeException('Min cannot be greater than max.');
        }
        return max($min, min($max, $value));
    }

    /**
     * Returns true if $value lies within the closed interval [$min, $max].
     */
    public static function inRange(int|float $value, int|float $min, int|float $max): bool {
        return $value >= $min && $value <= $max;
    }

    /**
     * Rounds $value to $precision decimal places using the given rounding mode.
     */
    public static function round(float $value, int $precision = 0, RoundingMode $mode = RoundingMode::HalfAwayFromZero): float {
        return round($value, $precision, $mode);
    }

    /**
     * Formats $value with the given decimal and thousands separators.
     */
    public static function format(int|float $value, int $decimals = 2, string $decimalSeparator = '.', string $thousandsSeparator = ','): string {
        return number_format((float) $value, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Returns the sum of $values. Returns 0 (int) for an empty input.
     *
     * @param iterable<int|float> $values
     */
    public static function sum(iterable $values): int|float {
        $sum = 0;
        foreach ($values as $value) {
            $sum += $value;
        }
        return $sum;
    }

    /**
     * Returns the arithmetic mean of $values.
     *
     * @param iterable<int|float> $values
     * @throws RuntimeException When $values is empty.
     */
    public static function avg(iterable $values): float {
        $sum = 0;
        $count = 0;
        foreach ($values as $value) {
            $sum += $value;
            $count++;
        }
        if ($count === 0) {
            throw new RuntimeException('Cannot compute average of empty input.');
        }
        return $sum / $count;
    }

    /**
     * Returns the smallest of $values.
     *
     * @param iterable<int|float> $values
     * @throws RuntimeException When $values is empty.
     */
    public static function min(iterable $values): int|float {
        $min = null;
        foreach ($values as $value) {
            if ($min === null || $value < $min) {
                $min = $value;
            }
        }
        if ($min === null) {
            throw new RuntimeException('Cannot compute min of empty input.');
        }
        return $min;
    }

    /**
     * Returns the largest of $values.
     *
     * @param iterable<int|float> $values
     * @throws RuntimeException When $values is empty.
     */
    public static function max(iterable $values): int|float {
        $max = null;
        foreach ($values as $value) {
            if ($max === null || $value > $max) {
                $max = $value;
            }
        }
        if ($max === null) {
            throw new RuntimeException('Cannot compute max of empty input.');
        }
        return $max;
    }

    /**
     * Returns the absolute value of $value.
     */
    public static function abs(int|float $value): int|float {
        return abs($value);
    }

    /**
     * Returns -1, 0, or 1 according to the sign of $value.
     */
    public static function sign(int|float $value): int {
        return $value <=> 0;
    }
}
