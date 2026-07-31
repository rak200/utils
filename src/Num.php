<?php

declare(strict_types=1);

namespace Rak200\Utils;

use BcMath\Number;
use Rak200\Utils\Exception\EmptySourceException;
use Rak200\Utils\Exception\MalformedArgumentException;
use RoundingMode;

use function abs;
use function assert;
use function ceil;
use function floor;
use function fmod;
use function implode;
use function intdiv;
use function is_finite;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_numeric;
use function number_format;
use function ord;
use function round;
use function sqrt;
use function var_export;

/**
 * Numeric helpers for parsing, formatting, arithmetic and aggregation.
 *
 * Accepts {@see Number} (PHP 8.4 arbitrary precision) alongside int|float in
 * aggregation and per-element methods; the return widens to Number when any
 * input is one (no silent narrowing to float). Parsing helpers stay scalar;
 * {@see parseNumber()} / {@see parseNumberOrNull()} accept everything
 * {@see is()} reports as numeric for arbitrary-precision work.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Num
{
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
     *
     * @phpstan-assert-if-false !int $value
     */
    public static function isInt(mixed $value): bool
    {
        return is_int($value);
    }

    /**
     * Returns true if $value is a native PHP float.
     *
     * @phpstan-assert-if-true float $value
     *
     * @phpstan-assert-if-false !float $value
     */
    public static function isFloat(mixed $value): bool
    {
        return is_float($value);
    }

    /**
     * Returns true if $value is an int, a float, a numeric string (with no
     * surrounding whitespace), or a {@see Number} instance. Domain predicate
     * for {@see Num}; {@see Type::isNumeric()} is an alias.
     *
     * @phpstan-assert-if-true int|float|numeric-string|Number $value
     */
    public static function is(mixed $value): bool
    {
        return is_int($value)
            || is_float($value)
            || (Str::is($value) && self::isStrictNumericString($value))
            || $value instanceof Number;
    }

    /**
     * Returns true when $value is greater than zero. Accepts int, float, or
     * {@see Number}.
     */
    public static function isPositive(float|int|Number $value): bool
    {
        return $value instanceof Number ? $value > new Number('0') : $value > 0;
    }

    /**
     * Returns true when $value is less than zero. Accepts int, float, or
     * {@see Number}.
     */
    public static function isNegative(float|int|Number $value): bool
    {
        return $value instanceof Number ? $value < new Number('0') : $value < 0;
    }

    /**
     * Returns true if $value is a finite number: an int, a {@see Number}, a
     * finite float, or a numeric string whose float value is finite. `INF`,
     * `-INF`, `NAN`, overflowing numeric strings (e.g. `'1e400'`), and
     * non-numeric values are false.
     */
    public static function isFinite(mixed $value): bool
    {
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
    public static function isNan(mixed $value): bool
    {
        return is_float($value) && is_nan($value);
    }

    /**
     * Returns true when $value is infinite: `INF`, `-INF`, or a numeric string
     * whose float value overflows to infinity (e.g. `'1e400'`). Ints,
     * {@see Number}s, finite floats, and non-numeric values are false.
     * Complements {@see isFinite()}.
     */
    public static function isInfinite(mixed $value): bool
    {
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
     * @throws MalformedArgumentException when $value is not a valid integer in $base, or $base is out of range
     */
    public static function parseInt(string $value, int $base = 10): int
    {
        $parsed = self::parseIntOrNull($value, $base);
        if ($parsed === null) {
            throw new MalformedArgumentException("Cannot parse \"{$value}\" as integer in base {$base}.");
        }

        return $parsed;
    }

    /**
     * Parses $value as an integer in the given $base (2-36); returns null on failure.
     *
     * @throws MalformedArgumentException when $base is outside 2-36
     */
    public static function parseIntOrNull(string $value, int $base = 10): ?int
    {
        if ($base < 2 || $base > 36) {
            throw new MalformedArgumentException('Base must be between 2 and 36.');
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
                // @infection-ignore-all: any negative sentinel is rejected by the $digit < 0 guard below
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
     * @throws MalformedArgumentException when $base is outside 2-36
     */
    public static function toBase(int $value, int $base): string
    {
        if ($base < 2 || $base > 36) {
            throw new MalformedArgumentException('Base must be between 2 and 36.');
        }
        if ($value === 0) {
            return '0';
        }
        $digits = '0123456789abcdefghijklmnopqrstuvwxyz';
        // @infection-ignore-all: $value === 0 already returned above, so < and <= are indistinguishable
        $negative = $value < 0;
        $result = '';
        $n = $value;
        while ($n !== 0) {
            $result = $digits[abs($n % $base)] . $result;
            $n = intdiv($n, $base);
        }

        return $negative ? "-{$result}" : $result;
    }

    /**
     * Parses $value as a float.
     *
     * @throws MalformedArgumentException when $value is not numeric or has surrounding whitespace
     */
    public static function parseFloat(string $value): float
    {
        $parsed = self::parseFloatOrNull($value);
        if ($parsed === null) {
            throw new MalformedArgumentException("Cannot parse \"{$value}\" as float.");
        }

        return $parsed;
    }

    /**
     * Parses $value as a float; returns null when $value is not numeric or has
     * surrounding whitespace.
     */
    public static function parseFloatOrNull(string $value): ?float
    {
        if (!self::isStrictNumericString($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Returns the exact string form of $value — the one that reads back as the
     * same value, unlike the `(string)` cast, which goes through `precision`
     * (14 significant digits) and silently collapses distinct floats: `0.1+0.2`
     * casts to `0.3`, and `1/3` to `0.33333333333333`.
     *
     * The inverse of {@see parseFloat()}: `parseFloat(toStr($f)) === $f` holds
     * for every finite float. Integral floats keep their marker (`1.0`, where
     * the cast gives `1`), and `-0.0` keeps its sign. Ints and {@see Number}s
     * are already exact under a cast and pass through — a Number keeps its
     * trailing zeros (`1.500`).
     *
     * Not to be confused with {@see Filter::toStr()}, the lenient `mixed`
     * coercer for untrusted input; this one preserves precision.
     *
     * @throws MalformedArgumentException when $value is a non-finite float (NAN,
     *                                    INF, -INF): those have no string form
     *                                    {@see parseFloat()} can read back
     */
    public static function toStr(float|int|Number $value): string
    {
        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new MalformedArgumentException(
                    'Cannot represent ' . var_export($value, true) . ' as an exact string.',
                );
            }

            return var_export($value, true);
        }

        return (string) $value;
    }

    /**
     * Parses $value as an arbitrary-precision {@see Number}. Accepts exactly
     * the values {@see is()} reports as numeric: a Number (returned as-is), an
     * int, a finite float (expanded to its exact decimal form, so a value whose
     * string form is scientific notation converts cleanly), or a strict numeric
     * string in decimal or scientific notation (e.g. `1.5e3`, `2e-10`;
     * scientific input is expanded, so no precision is lost).
     *
     * @throws MalformedArgumentException when $value cannot be represented as a Number —
     *                                    non-numeric, surrounding whitespace, a
     *                                    non-finite float (NAN, INF), or an
     *                                    exponent so large the decimal form is impractical
     */
    public static function parseNumber(float|int|Number|string $value): Number
    {
        $parsed = self::parseNumberOrNull($value);
        if ($parsed === null) {
            $display = is_float($value) && !is_finite($value)
                ? var_export($value, true)
                : /* @infection-ignore-all: every non-float parse failure is already a string, so the cast is an identity */ (string) $value;

            throw new MalformedArgumentException("Cannot parse \"{$display}\" as number.");
        }

        return $parsed;
    }

    /**
     * Parses $value as an arbitrary-precision {@see Number}; returns null when
     * $value is a non-finite float (NAN, INF), not a strict numeric string
     * (surrounding whitespace is rejected, matching {@see is()}), or cannot be
     * represented. See {@see parseNumber()}.
     */
    public static function parseNumberOrNull(float|int|Number|string $value): ?Number
    {
        if ($value instanceof Number) {
            return $value;
        }
        if (is_int($value)) {
            return new Number($value);
        }
        if (is_float($value)) {
            if (!is_finite($value)) {
                return null;
            }
            $value = (string) $value;
        }
        if (!self::isStrictNumericString($value)) {
            return null;
        }
        $decimal = self::expandScientific($value);
        if (/* @infection-ignore-all: expandScientific never returns a non-numeric string, so the is_numeric half is defensive */ $decimal === null || !is_numeric($decimal)) {
            return null;
        }

        return new Number($decimal);
    }

    /**
     * Constrains $value to the closed interval [$min, $max].
     *
     * @throws MalformedArgumentException when $min > $max
     */
    public static function clamp(
        float|int|Number $value,
        float|int|Number $min,
        float|int|Number $max,
    ): float|int|Number {
        if ($min > $max) {
            throw new MalformedArgumentException('Min cannot be greater than max.');
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
        float|int|Number $value,
        float|int|Number $min,
        float|int|Number $max,
    ): bool {
        return $value >= $min && $value <= $max;
    }

    /**
     * Linearly interpolates between $a and $b by $t, returning `a + (b - a) * t`.
     * $t in [0, 1] interpolates; outside that range it extrapolates. Widens to
     * {@see Number} when any operand is one.
     */
    public static function lerp(
        float|int|Number $a,
        float|int|Number $b,
        float|int|Number $t,
    ): float|int|Number {
        return self::add($a, self::mul(self::sub($b, $a), $t));
    }

    /**
     * Re-maps $value from the input range [$inMin, $inMax] to the output range
     * [$outMin, $outMax], linearly. Does not clamp — a $value outside the input
     * range maps outside the output range (compose with {@see clamp()} to bound).
     * Widens to {@see Number} when any operand is one.
     *
     * @throws MalformedArgumentException when $inMin equals $inMax (the input range is empty)
     */
    public static function remap(
        float|int|Number $value,
        float|int|Number $inMin,
        float|int|Number $inMax,
        float|int|Number $outMin,
        float|int|Number $outMax,
    ): float|int|Number {
        if ($inMin == $inMax) {
            throw new MalformedArgumentException('Input range cannot be empty (inMin equals inMax).');
        }

        return self::add(
            $outMin,
            self::div(self::mul(self::sub($value, $inMin), self::sub($outMax, $outMin)), self::sub($inMax, $inMin)),
        );
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
        float|int|Number $value,
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
     * An all-int input is typed `int`, so the result can be returned from an
     * int-typed method. **One caveat, stated because the annotation cannot:** an
     * int sum that overflows `PHP_INT_MAX` promotes to float at runtime, exactly
     * as `array_sum()` does — and, as with `array_sum()`, the declared type says
     * `int` regardless. Feed {@see Number} values instead when a sum can plausibly
     * reach that magnitude. {@see min()} / {@see max()} carry no such caveat: they
     * return one of the elements rather than computing.
     *
     * @param iterable<float|int|Number> $values
     *
     * @return ($values is iterable<int> ? int : float|int|Number)
     */
    public static function sum(iterable $values): float|int|Number
    {
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
     * An all-int input is typed `int`, with the same overflow caveat as
     * {@see sum()} — and reached far sooner here, since a product grows
     * multiplicatively.
     *
     * @param iterable<float|int|Number> $values
     *
     * @return ($values is iterable<int> ? int : float|int|Number)
     */
    public static function product(iterable $values): float|int|Number
    {
        $product = 1;
        foreach ($values as $value) {
            $product = self::mul($product, $value);
        }

        return $product;
    }

    /**
     * Returns the arithmetic mean of $values. Widens to {@see Number} when any
     * element is one.
     *
     * @param iterable<float|int|Number> $values
     *
     * @throws EmptySourceException when $values is empty
     */
    public static function avg(iterable $values): float|Number
    {
        $sum = 0;
        $count = 0;
        foreach ($values as $value) {
            $sum = self::add($sum, $value);
            ++$count;
        }
        if ($count === 0) {
            throw new EmptySourceException('Cannot compute average of empty input.');
        }
        if ($sum instanceof Number) {
            // @infection-ignore-all: falling through would divide Number by int via operator overloading — identical result
            return $sum / new Number((string) $count);
        }

        return $sum / $count;
    }

    /**
     * Returns the smallest of $values. An all-int input is typed `int`, exactly
     * — the result is one of the elements, so no arithmetic can widen it.
     *
     * @param iterable<float|int|Number> $values
     *
     * @return ($values is iterable<int> ? int : float|int|Number)
     *
     * @throws EmptySourceException when $values is empty
     */
    public static function min(iterable $values): float|int|Number
    {
        $min = null;
        foreach ($values as $value) {
            if ($min === null || $value < $min) {
                $min = $value;
            }
        }
        if ($min === null) {
            throw new EmptySourceException('Cannot compute min of empty input.');
        }

        return $min;
    }

    /**
     * Returns the largest of $values. An all-int input is typed `int`, exactly —
     * the result is one of the elements, so no arithmetic can widen it.
     *
     * @param iterable<float|int|Number> $values
     *
     * @return ($values is iterable<int> ? int : float|int|Number)
     *
     * @throws EmptySourceException when $values is empty
     */
    public static function max(iterable $values): float|int|Number
    {
        $max = null;
        foreach ($values as $value) {
            if ($max === null || $value > $max) {
                $max = $value;
            }
        }
        if ($max === null) {
            throw new EmptySourceException('Cannot compute max of empty input.');
        }

        return $max;
    }

    /**
     * Returns the absolute value of $value.
     */
    public static function abs(float|int|Number $value): float|int|Number
    {
        if ($value instanceof Number) {
            return $value < new Number('0') ? $value * -1 : $value;
        }

        return abs($value);
    }

    /**
     * Returns -1, 0, or 1 according to the sign of $value.
     */
    public static function sign(float|int|Number $value): int
    {
        // @infection-ignore-all: <=> between Number and int|float agrees with the scalar comparison, so both branches are interchangeable
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
    public static function pow(float|int|Number $base, float|int|Number $exp): float|int|Number
    {
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
     * @throws MalformedArgumentException when $value is negative
     */
    public static function sqrt(float|int|Number $value): float|Number
    {
        if ($value instanceof Number) {
            if ($value < new Number('0')) {
                throw new MalformedArgumentException('Cannot take square root of a negative number.');
            }

            return $value->sqrt();
        }
        if ($value < 0) {
            throw new MalformedArgumentException('Cannot take square root of a negative number.');
        }

        return sqrt((float) $value);
    }

    /**
     * Rounds $value down to $precision decimal places.
     */
    public static function floor(float|int|Number $value, int $precision = 0): float|Number
    {
        if ($value instanceof Number) {
            return self::numberFloorCeil($value, $precision, false);
        }
        if ($precision === 0) {
            // @infection-ignore-all: falling through with factor 10 ** 0 = 1 is an identity — same result
            return floor((float) $value);
        }
        $factor = 10 ** $precision;

        return floor($value * $factor) / $factor;
    }

    /**
     * Rounds $value up to $precision decimal places.
     */
    public static function ceil(float|int|Number $value, int $precision = 0): float|Number
    {
        if ($value instanceof Number) {
            return self::numberFloorCeil($value, $precision, true);
        }
        if ($precision === 0) {
            // @infection-ignore-all: falling through with factor 10 ** 0 = 1 is an identity — same result
            return ceil((float) $value);
        }
        $factor = 10 ** $precision;

        return ceil($value * $factor) / $factor;
    }

    /**
     * Returns the truncated modulo of $a divided by $b (sign of the result
     * follows the dividend, matching PHP's `%`).
     *
     * @throws MalformedArgumentException when $b is zero
     */
    public static function mod(float|int|Number $a, float|int|Number $b): float|int|Number
    {
        if ($b instanceof Number) {
            if ($b == new Number('0')) {
                throw new MalformedArgumentException('Cannot mod by zero.');
            }
        } elseif ($b == 0) {
            throw new MalformedArgumentException('Cannot mod by zero.');
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
     * @throws MalformedArgumentException when $b is zero
     */
    public static function intDiv(int $a, int $b): int
    {
        if ($b === 0) {
            throw new MalformedArgumentException('Cannot divide by zero.');
        }

        return intdiv($a, $b);
    }

    /**
     * Adds two values, widening to {@see Number} when either operand is one.
     * Centralises the union arithmetic the type system cannot express in a single
     * expression — the same widening {@see sub()} / {@see mul()} / {@see div()} apply.
     */
    public static function add(float|int|Number $a, float|int|Number $b): float|int|Number
    {
        if ($a instanceof Number || $b instanceof Number) {
            $aN = $a instanceof Number ? $a : new Number((string) $a);
            $bN = $b instanceof Number ? $b : new Number((string) $b);

            return $aN + $bN;
        }

        return $a + $b;
    }

    /**
     * Subtracts $b from $a, widening to {@see Number} when either operand is one.
     */
    public static function sub(float|int|Number $a, float|int|Number $b): float|int|Number
    {
        if ($a instanceof Number || $b instanceof Number) {
            $aN = $a instanceof Number ? $a : new Number((string) $a);
            $bN = $b instanceof Number ? $b : new Number((string) $b);

            return $aN - $bN;
        }

        return $a - $b;
    }

    /**
     * Multiplies two values, widening to {@see Number} when either operand is one.
     */
    public static function mul(float|int|Number $a, float|int|Number $b): float|int|Number
    {
        if ($a instanceof Number || $b instanceof Number) {
            $aN = $a instanceof Number ? $a : new Number((string) $a);
            $bN = $b instanceof Number ? $b : new Number((string) $b);

            return $aN * $bN;
        }

        return $a * $b;
    }

    /**
     * Divides $a by $b, following PHP's `/`: an int when both operands are ints
     * and evenly divisible, a float otherwise, and a {@see Number} when either
     * operand is one.
     *
     * @throws MalformedArgumentException when $b is zero
     */
    public static function div(float|int|Number $a, float|int|Number $b): float|int|Number
    {
        if ($b instanceof Number) {
            if ($b == new Number('0')) {
                throw new MalformedArgumentException('Cannot divide by zero.');
            }
        } elseif ($b == 0) {
            throw new MalformedArgumentException('Cannot divide by zero.');
        }
        if ($a instanceof Number || $b instanceof Number) {
            $aN = $a instanceof Number ? $a : new Number((string) $a);
            $bN = $b instanceof Number ? $b : new Number((string) $b);

            return $aN / $bN;
        }

        return $a / $b;
    }

    /**
     * Returns true if $value is a numeric string with no surrounding
     * whitespace. PHP's {@see is_numeric()} accepts leading/trailing whitespace
     * since 8.0; this rejects it so the numeric contract stays strict, matching
     * {@see parseInt()} and {@see parseNumber()}.
     */
    private static function isStrictNumericString(string $value): bool
    {
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
    private static function expandScientific(string $value): ?string
    {
        $ePos = Str::indexOf($value, 'e', ignoreCase: true);
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
            // @infection-ignore-all: a strict-numeric mantissa holds at most one dot, so any limit >= 2 splits identically
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
        if (/* @infection-ignore-all: at 0 the else branch yields the '.'-prefixed form — same Number value */ $pointPos <= 0) {
            $result = '0.' . Str::repeat('0', -$pointPos) . $digits;
        } elseif (/* @infection-ignore-all: at len the '.'-suffixed form — same Number value */ $pointPos >= Str::len($digits)) {
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
    private static function pow10(int $exp): Number
    {
        $s = '1' . Str::repeat('0', $exp);
        assert(is_numeric($s));

        return new Number($s);
    }

    /**
     * Shared {@see Number} floor/ceil implementation: shifts the value by
     * 10^precision (negative precision shifts in the other direction), rounds
     * the shifted integer-scale value, then unshifts.
     */
    private static function numberFloorCeil(Number $value, int $precision, bool $ceil): Number
    {
        if ($precision === 0) {
            // @infection-ignore-all: falling through with factor pow10(0) = 1 is an identity — same result
            return $ceil ? $value->ceil() : $value->floor();
        }
        // @infection-ignore-all: $precision === 0 already returned above, so < and <= are indistinguishable
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
            // @infection-ignore-all: a rounded Number string holds at most one dot, so any limit >= 2 splits identically
            [$intPart, $decPart] = Str::split($str, '.', 2);
        } else {
            $intPart = $str;
            $decPart = '';
        }
        if (/* @infection-ignore-all: at 0 padEnd to length 0 also yields '', same as the else branch */ $decimals > 0) {
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
