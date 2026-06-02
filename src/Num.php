<?php

declare(strict_types=1);

namespace Rak200\Utils;

use BcMath\Number;
use RoundingMode;
use RuntimeException;
use function abs, assert, ceil, floor, fmod, implode, intdiv, is_finite, is_infinite, is_int,
    is_float, is_nan, is_numeric, number_format, ord, round, sqrt;

/**
 * Numeric helpers for parsing, formatting, arithmetic and aggregation.
 *
 * Accepts {@see Number} (PHP 8.4 arbitrary precision) alongside int|float in
 * aggregation and per-element methods; the return widens to Number when any
 * input is one (no silent narrowing to float). Parsing helpers stay scalar;
 * use {@see parseNumber()} / {@see parseNumberOrNull()} for arbitrary-precision
 * strings.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Num {
    /**
     * Upper bound on the digit count of an expanded scientific-notation number,
     * guarding {@see expandScientific()} against pathological exponents in
     * untrusted input (e.g. `1e999999999`).
     */
    private const int MAX_NUMBER_DIGITS = 65536;

    private function __construct() {}

    /**
     * Returns true if $value is a native PHP int.
     *
     * @phpstan-assert-if-true int $value
     * @phpstan-assert-if-false !int $value
     */
    public static function isInt(mixed $value): bool {
        return is_int($value);
    }

    /**
     * @deprecated since 1.2.0, use {@see self::isInt()} instead. Will be removed in 2.0.0.
     */
    public static function isInteger(mixed $value): bool {
        return self::isInt($value);
    }

    /**
     * Returns true if $value is a native PHP float.
     *
     * @phpstan-assert-if-true float $value
     * @phpstan-assert-if-false !float $value
     */
    public static function isFloat(mixed $value): bool {
        return is_float($value);
    }

    /**
     * Returns true if $value is an int, a float, a numeric string (with no
     * surrounding whitespace), or a {@see Number} instance. Domain predicate
     * for {@see Num}; {@see Type::isNumeric()} is an alias.
     *
     * @phpstan-assert-if-true int|float|numeric-string|Number $value
     */
    public static function is(mixed $value): bool {
        return is_int($value)
            || is_float($value)
            || (Str::is($value) && self::isStrictNumericString($value))
            || $value instanceof Number;
    }

    /**
     * @deprecated since 1.8.0, use {@see self::is()} instead. Will be removed in 2.0.0.
     */
    public static function isNumeric(mixed $value): bool {
        return self::is($value);
    }

    /**
     * Returns true when $value is greater than zero. Accepts int, float, or
     * {@see Number}.
     */
    public static function isPositive(int|float|Number $value): bool {
        return $value instanceof Number ? $value > new Number('0') : $value > 0;
    }

    /**
     * Returns true when $value is less than zero. Accepts int, float, or
     * {@see Number}.
     */
    public static function isNegative(int|float|Number $value): bool {
        return $value instanceof Number ? $value < new Number('0') : $value < 0;
    }

    /**
     * Returns true if $value is an int strictly greater than zero. Floats
     * and numeric strings are rejected.
     *
     * @deprecated since 1.14.0, use {@see self::isPositive()} (int/float/Number)
     *             or `Num::isInt($v) && $v > 0`. Will be removed in 2.0.0.
     */
    public static function isPositiveInt(mixed $value): bool {
        return is_int($value) && $value > 0;
    }

    /**
     * Returns true if $value is an int strictly less than zero. Floats and
     * numeric strings are rejected.
     *
     * @deprecated since 1.14.0, use {@see self::isNegative()} (int/float/Number)
     *             or `Num::isInt($v) && $v < 0`. Will be removed in 2.0.0.
     */
    public static function isNegativeInt(mixed $value): bool {
        return is_int($value) && $value < 0;
    }

    /**
     * Returns true if $value is an int greater than or equal to zero.
     * Floats and numeric strings are rejected.
     *
     * @deprecated since 1.14.0, use `Num::isInt($v) && $v >= 0`. Note `!isNegative()`
     *             is *not* equivalent (it is true for non-ints). Will be removed in 2.0.0.
     */
    public static function isNonNegativeInt(mixed $value): bool {
        return is_int($value) && $value >= 0;
    }

    /**
     * Returns true if $value is a finite number: an int, a {@see Number}, a
     * finite float, or a numeric string whose float value is finite. `INF`,
     * `-INF`, `NAN`, overflowing numeric strings (e.g. `'1e400'`), and
     * non-numeric values are false.
     */
    public static function isFinite(mixed $value): bool {
        if (!self::is($value)) {
            return false;
        }
        if (is_float($value)) {
            return is_finite($value);
        }
        if (Str::is($value)) {
            return is_finite((float) $value);
        }
        return true;
    }

    /**
     * Returns true when $value is `NAN` — a float not-a-number. Ints,
     * {@see Number}s, numeric strings (which can never denote NaN), and
     * non-numeric values are all false. Complements {@see isFinite()}.
     */
    public static function isNan(mixed $value): bool {
        return is_float($value) && is_nan($value);
    }

    /**
     * Returns true when $value is infinite: `INF`, `-INF`, or a numeric string
     * whose float value overflows to infinity (e.g. `'1e400'`). Ints,
     * {@see Number}s, finite floats, and non-numeric values are false.
     * Complements {@see isFinite()}.
     */
    public static function isInfinite(mixed $value): bool {
        if (is_float($value)) {
            return is_infinite($value);
        }
        if (Str::is($value) && self::isStrictNumericString($value)) {
            return is_infinite((float) $value);
        }
        return false;
    }

    /**
     * Parses $value as an integer in the given $base (2-36).
     *
     * @throws RuntimeException When $value is not a valid integer in $base, or $base is out of range.
     */
    public static function parseInt(string $value, int $base = 10): int {
        $parsed = self::parseIntOrNull($value, $base);
        if ($parsed === null) {
            throw new RuntimeException("Cannot parse \"$value\" as integer in base $base.");
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
        if ($value === '') {
            return null;
        }

        $sign = 1;
        if ($value[0] === '-') {
            $sign = -1;
            $value = Str::sub($value, 1);
        } elseif ($value[0] === '+') {
            $value = Str::sub($value, 1);
        }
        if ($value === '') {
            return null;
        }

        $result = 0;
        foreach (Str::split(Str::lower($value), '') as $char) {
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
     * Returns the base-$base (2-36) string representation of the integer $value,
     * using digits `0-9a-z` (lowercase). Negative values are prefixed with `-`.
     * Inverse of {@see parseInt()}: `parseInt(toBase($n, $b), $b) === $n`.
     *
     * @throws RuntimeException When $base is outside 2-36.
     */
    public static function toBase(int $value, int $base): string {
        if ($base < 2 || $base > 36) {
            throw new RuntimeException('Base must be between 2 and 36.');
        }
        if ($value === 0) {
            return '0';
        }
        $digits = '0123456789abcdefghijklmnopqrstuvwxyz';
        $negative = $value < 0;
        $result = '';
        $n = $value;
        while ($n !== 0) {
            $result = $digits[abs($n % $base)] . $result;
            $n = intdiv($n, $base);
        }
        return $negative ? "-$result" : $result;
    }

    /**
     * Parses $value as a float.
     *
     * @throws RuntimeException When $value is not numeric or has surrounding whitespace.
     */
    public static function parseFloat(string $value): float {
        $parsed = self::parseFloatOrNull($value);
        if ($parsed === null) {
            throw new RuntimeException("Cannot parse \"$value\" as float.");
        }
        return $parsed;
    }

    /**
     * Parses $value as a float; returns null when $value is not numeric or has
     * surrounding whitespace.
     */
    public static function parseFloatOrNull(string $value): ?float {
        if (!self::isStrictNumericString($value)) {
            return null;
        }
        return (float) $value;
    }

    /**
     * Parses $value as an arbitrary-precision {@see Number}. Accepts decimal and
     * scientific notation (e.g. `1.5e3`, `2e-10`); scientific input is expanded
     * to its exact decimal form, so no precision is lost. Accepts exactly the
     * strings {@see is()} reports as numeric.
     *
     * @throws RuntimeException When $value cannot be represented as a Number —
     *                          non-numeric, surrounding whitespace, or an
     *                          exponent so large the decimal form is impractical.
     */
    public static function parseNumber(string $value): Number {
        $parsed = self::parseNumberOrNull($value);
        if ($parsed === null) {
            throw new RuntimeException("Cannot parse \"$value\" as number.");
        }
        return $parsed;
    }

    /**
     * Parses $value as an arbitrary-precision {@see Number}; returns null when
     * $value is not a strict numeric string (surrounding whitespace is rejected,
     * matching {@see is()}) or cannot be represented. Scientific notation is
     * accepted and expanded to its exact decimal form. See {@see parseNumber()}.
     */
    public static function parseNumberOrNull(string $value): ?Number {
        if (!self::isStrictNumericString($value)) {
            return null;
        }
        $decimal = self::expandScientific($value);
        if ($decimal === null || !is_numeric($decimal)) {
            return null;
        }
        return new Number($decimal);
    }

    /**
     * Constrains $value to the closed interval [$min, $max].
     *
     * @throws RuntimeException When $min > $max.
     */
    public static function clamp(
        int|float|Number $value,
        int|float|Number $min,
        int|float|Number $max,
    ): int|float|Number {
        if ($min > $max) {
            throw new RuntimeException('Min cannot be greater than max.');
        }
        if ($value < $min) {
            return $min;
        }
        if ($value > $max) {
            return $max;
        }
        return $value;
    }

    /**
     * Returns true if $value lies within the closed interval [$min, $max].
     */
    public static function inRange(
        int|float|Number $value,
        int|float|Number $min,
        int|float|Number $max,
    ): bool {
        return $value >= $min && $value <= $max;
    }

    /**
     * Rounds $value to $precision decimal places. Returns a {@see Number} when
     * $value is one (preserves arbitrary precision); a float otherwise.
     */
    public static function round(
        float|Number $value,
        int $precision = 0,
        RoundingMode $mode = RoundingMode::HalfAwayFromZero,
    ): float|Number {
        if ($value instanceof Number) {
            return $value->round($precision, $mode);
        }
        return round($value, $precision, $mode);
    }

    /**
     * Formats $value with the given decimal and thousands separators.
     *
     * For {@see Number} input, the value is rounded to $decimals using
     * {@see RoundingMode::HalfAwayFromZero} and formatted from its canonical
     * decimal string (no precision loss).
     */
    public static function format(
        int|float|Number $value,
        int $decimals = 2,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ',',
    ): string {
        if ($value instanceof Number) {
            return self::formatNumber($value, $decimals, $decimalSeparator, $thousandsSeparator);
        }
        return number_format((float) $value, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Returns the sum of $values. Widens to {@see Number} when any element is
     * one. Returns 0 (int) for an empty input.
     *
     * @param iterable<int|float|Number> $values
     */
    public static function sum(iterable $values): int|float|Number {
        $sum = 0;
        foreach ($values as $value) {
            $sum = self::add($sum, $value);
        }
        return $sum;
    }

    /**
     * Returns the product of $values. Widens to {@see Number} when any element is
     * one. Returns 1 (int) for an empty input.
     *
     * @param iterable<int|float|Number> $values
     */
    public static function product(iterable $values): int|float|Number {
        $product = 1;
        foreach ($values as $value) {
            $product = self::multiply($product, $value);
        }
        return $product;
    }

    /**
     * Returns the arithmetic mean of $values. Widens to {@see Number} when any
     * element is one.
     *
     * @param iterable<int|float|Number> $values
     * @throws RuntimeException When $values is empty.
     */
    public static function avg(iterable $values): float|Number {
        $sum = 0;
        $count = 0;
        foreach ($values as $value) {
            $sum = self::add($sum, $value);
            $count++;
        }
        if ($count === 0) {
            throw new RuntimeException('Cannot compute average of empty input.');
        }
        if ($sum instanceof Number) {
            return $sum / new Number((string) $count);
        }
        return $sum / $count;
    }

    /**
     * Returns the smallest of $values.
     *
     * @param iterable<int|float|Number> $values
     * @throws RuntimeException When $values is empty.
     */
    public static function min(iterable $values): int|float|Number {
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
     * @param iterable<int|float|Number> $values
     * @throws RuntimeException When $values is empty.
     */
    public static function max(iterable $values): int|float|Number {
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
    public static function abs(int|float|Number $value): int|float|Number {
        if ($value instanceof Number) {
            return $value < new Number('0') ? $value * -1 : $value;
        }
        return abs($value);
    }

    /**
     * Returns -1, 0, or 1 according to the sign of $value.
     */
    public static function sign(int|float|Number $value): int {
        if ($value instanceof Number) {
            $zero = new Number('0');
            return $value <=> $zero;
        }
        return $value <=> 0;
    }

    /**
     * Returns $base raised to $exp. Widens to {@see Number} when either operand
     * is one.
     */
    public static function pow(int|float|Number $base, int|float|Number $exp): int|float|Number {
        if ($base instanceof Number || $exp instanceof Number) {
            $baseN = $base instanceof Number ? $base : new Number((string) $base);
            $expN = $exp instanceof Number ? $exp : new Number((string) $exp);
            return $baseN ** $expN;
        }
        return $base ** $exp;
    }

    /**
     * Returns the square root of $value. Returns a {@see Number} when $value is
     * one (preserves arbitrary precision); a float otherwise.
     *
     * @throws RuntimeException When $value is negative.
     */
    public static function sqrt(int|float|Number $value): float|Number {
        if ($value instanceof Number) {
            if ($value < new Number('0')) {
                throw new RuntimeException('Cannot take square root of a negative number.');
            }
            return $value->sqrt();
        }
        if ($value < 0) {
            throw new RuntimeException('Cannot take square root of a negative number.');
        }
        return sqrt((float) $value);
    }

    /**
     * Rounds $value down to $precision decimal places.
     */
    public static function floor(int|float|Number $value, int $precision = 0): float|Number {
        if ($value instanceof Number) {
            return self::numberFloorCeil($value, $precision, false);
        }
        if ($precision === 0) {
            return floor((float) $value);
        }
        $factor = 10 ** $precision;
        return floor((float) $value * $factor) / $factor;
    }

    /**
     * Rounds $value up to $precision decimal places.
     */
    public static function ceil(int|float|Number $value, int $precision = 0): float|Number {
        if ($value instanceof Number) {
            return self::numberFloorCeil($value, $precision, true);
        }
        if ($precision === 0) {
            return ceil((float) $value);
        }
        $factor = 10 ** $precision;
        return ceil((float) $value * $factor) / $factor;
    }

    /**
     * Returns the truncated modulo of $a divided by $b (sign of the result
     * follows the dividend, matching PHP's `%`).
     *
     * @throws RuntimeException When $b is zero.
     */
    public static function mod(int|float|Number $a, int|float|Number $b): int|float|Number {
        if ($b instanceof Number) {
            if ($b == new Number('0')) {
                throw new RuntimeException('Cannot mod by zero.');
            }
        } elseif ($b == 0) {
            throw new RuntimeException('Cannot mod by zero.');
        }
        if ($a instanceof Number || $b instanceof Number) {
            $aN = $a instanceof Number ? $a : new Number((string) $a);
            $bN = $b instanceof Number ? $b : new Number((string) $b);
            return $aN % $bN;
        }
        if (is_int($a) && is_int($b)) {
            return $a % $b;
        }
        return fmod((float) $a, (float) $b);
    }

    /**
     * Returns the integer quotient of $a divided by $b, truncated toward zero
     * (matching PHP's {@see intdiv()}).
     *
     * @throws RuntimeException When $b is zero.
     */
    public static function intDiv(int $a, int $b): int {
        if ($b === 0) {
            throw new RuntimeException('Cannot divide by zero.');
        }
        return intdiv($a, $b);
    }

    /**
     * Adds two values, widening to {@see Number} when either operand is one.
     * Centralises the union-arithmetic branching that the type system cannot
     * express in a single expression.
     */
    private static function add(int|float|Number $a, int|float|Number $b): int|float|Number {
        if ($a instanceof Number || $b instanceof Number) {
            $aN = $a instanceof Number ? $a : new Number((string) $a);
            $bN = $b instanceof Number ? $b : new Number((string) $b);
            return $aN + $bN;
        }
        return $a + $b;
    }

    /**
     * Multiplies two values, widening to {@see Number} when either operand is
     * one. Mirrors {@see add()} for the union-arithmetic branching that the type
     * system cannot express in a single expression.
     */
    private static function multiply(int|float|Number $a, int|float|Number $b): int|float|Number {
        if ($a instanceof Number || $b instanceof Number) {
            $aN = $a instanceof Number ? $a : new Number((string) $a);
            $bN = $b instanceof Number ? $b : new Number((string) $b);
            return $aN * $bN;
        }
        return $a * $b;
    }

    /**
     * Returns true if $value is a numeric string with no surrounding
     * whitespace. PHP's {@see is_numeric()} accepts leading/trailing whitespace
     * since 8.0; this rejects it so the numeric contract stays strict, matching
     * {@see parseInt()} and {@see parseNumber()}.
     */
    private static function isStrictNumericString(string $value): bool {
        return is_numeric($value)
            && !Str::isWhitespace($value[0])
            && !Str::isWhitespace($value[-1]);
    }

    /**
     * Expands a strict-numeric string into a plain decimal string with no
     * exponent — `1.5e3` → `1500`, `1.5e-3` → `0.0015` — keeping every digit so
     * {@see Number} arbitrary precision is preserved. Strings without an
     * exponent are returned unchanged. Returns null when the expanded form would
     * exceed {@see MAX_NUMBER_DIGITS} digits.
     */
    private static function expandScientific(string $value): ?string {
        $ePos = Str::indexOf($value, 'e', 0, true);
        if ($ePos === -1) {
            return $value;
        }

        $mantissa = Str::sub($value, 0, $ePos);
        $exp = (int) Str::sub($value, $ePos + 1);

        $sign = '';
        if ($mantissa !== '' && ($mantissa[0] === '+' || $mantissa[0] === '-')) {
            if ($mantissa[0] === '-') {
                $sign = '-';
            }
            $mantissa = Str::sub($mantissa, 1);
        }

        if (Str::contains($mantissa, '.')) {
            [$intPart, $fracPart] = Str::split($mantissa, '.', 2);
        } else {
            $intPart = $mantissa;
            $fracPart = '';
        }

        $digits = $intPart . $fracPart;
        if (Str::len($digits) + abs($exp) > self::MAX_NUMBER_DIGITS) {
            return null;
        }

        $pointPos = Str::len($intPart) + $exp;
        if ($pointPos <= 0) {
            $result = '0.' . Str::repeat('0', -$pointPos) . $digits;
        } elseif ($pointPos >= Str::len($digits)) {
            $result = $digits . Str::repeat('0', $pointPos - Str::len($digits));
        } else {
            $result = Str::sub($digits, 0, $pointPos) . '.' . Str::sub($digits, $pointPos);
        }

        return $sign . $result;
    }

    /**
     * Returns 10^$exp as a {@see Number}, built by digit concatenation to
     * preserve precision for large exponents.
     */
    private static function pow10(int $exp): Number {
        $s = '1' . Str::repeat('0', $exp);
        assert(is_numeric($s));
        return new Number($s);
    }

    /**
     * Shared {@see Number} floor/ceil implementation: shifts the value by
     * 10^precision (negative precision shifts in the other direction), rounds
     * the shifted integer-scale value, then unshifts.
     */
    private static function numberFloorCeil(Number $value, int $precision, bool $ceil): Number {
        if ($precision === 0) {
            return $ceil ? $value->ceil() : $value->floor();
        }
        if ($precision < 0) {
            $factor = self::pow10(abs($precision));
            /** @var Number $shifted */
            $shifted = $value / $factor;
            /** @var Number $rounded */
            $rounded = $ceil ? $shifted->ceil() : $shifted->floor();
            return $rounded * $factor;
        }
        $factor = self::pow10($precision);
        /** @var Number $shifted */
        $shifted = $value * $factor;
        /** @var Number $rounded */
        $rounded = $ceil ? $shifted->ceil() : $shifted->floor();
        /** @var Number $result */
        $result = $rounded / $factor;
        return $result;
    }

    /**
     * Formats a {@see Number} from its canonical decimal string — manual
     * thousands-grouping via reverse-chunk-reverse — so arbitrary precision
     * is preserved instead of being narrowed through {@see number_format}.
     */
    private static function formatNumber(
        Number $value,
        int $decimals,
        string $decimalSeparator,
        string $thousandsSeparator,
    ): string {
        $str = (string) $value->round($decimals, RoundingMode::HalfAwayFromZero);
        $negative = Str::startsWith($str, '-');
        if ($negative) {
            $str = Str::sub($str, 1);
        }
        if (Str::contains($str, '.')) {
            [$intPart, $decPart] = Str::split($str, '.', 2);
        } else {
            $intPart = $str;
            $decPart = '';
        }
        if ($decimals > 0) {
            $decPart = Str::padEnd(Str::sub($decPart, 0, $decimals), $decimals, '0');
        } else {
            $decPart = '';
        }
        $intGrouped = $thousandsSeparator === ''
            ? $intPart
            : Str::reverse(implode($thousandsSeparator, Str::split(Str::reverse($intPart), limit: 3)));
        $result = ($negative ? '-' : '') . $intGrouped;
        if ($decimals > 0) {
            $result .= $decimalSeparator . $decPart;
        }
        return $result;
    }
}
