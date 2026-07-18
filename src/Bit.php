<?php

declare(strict_types=1);

namespace Rak200\Utils;

use InvalidArgumentException;

use function bindec;
use function decbin;

/**
 * Bit-manipulation helpers operating on the native PHP int (platform-sized,
 * 32 or 64 bits depending on PHP_INT_SIZE).
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Bit
{
    private const int BITS = PHP_INT_SIZE * 8;

    private function __construct() {}

    /**
     * Returns $value with the bit at index $bit set to 1.
     *
     * @throws InvalidArgumentException when $bit is out of range [0, PHP_INT_SIZE*8 - 1]
     */
    public static function set(int $value, int $bit): int
    {
        self::checkBitIndex($bit);

        return $value | (1 << $bit);
    }

    /**
     * Returns $value with the bit at index $bit cleared to 0.
     *
     * @throws InvalidArgumentException when $bit is out of range
     */
    public static function unset(int $value, int $bit): int
    {
        self::checkBitIndex($bit);

        return $value & ~(1 << $bit);
    }

    /**
     * Returns $value with the bit at index $bit flipped.
     *
     * @throws InvalidArgumentException when $bit is out of range
     */
    public static function toggle(int $value, int $bit): int
    {
        self::checkBitIndex($bit);

        return $value ^ (1 << $bit);
    }

    /**
     * Returns true when the bit at index $bit is set.
     *
     * @throws InvalidArgumentException when $bit is out of range
     */
    public static function has(int $value, int $bit): bool
    {
        self::checkBitIndex($bit);

        return ($value & (1 << $bit)) !== 0;
    }

    /**
     * Returns the population count (number of bits set to 1) of $value.
     */
    public static function count(int $value): int
    {
        $count = 0;
        for ($i = 0; $i < self::BITS; ++$i) {
            if ((($value >> $i) & 1) === 1) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Returns the number of leading zero bits in $value. Returns PHP_INT_SIZE*8
     * when $value is 0.
     */
    public static function leadingZeros(int $value): int
    {
        if ($value === 0) {
            // @infection-ignore-all: falling through reaches the defensive post-loop return of the same value
            return self::BITS;
        }
        for ($i = self::BITS - 1; $i >= 0; --$i) {
            if ((($value >> $i) & 1) === 1) {
                return self::BITS - 1 - $i;
            }
        }

        return self::BITS;
    }

    /**
     * Returns the number of trailing zero bits in $value. Returns PHP_INT_SIZE*8
     * when $value is 0.
     */
    public static function trailingZeros(int $value): int
    {
        if ($value === 0) {
            // @infection-ignore-all: falling through reaches the defensive post-loop return of the same value
            return self::BITS;
        }
        for ($i = 0; /* @infection-ignore-all: a nonzero value always finds a set bit before i reaches BITS */ $i < self::BITS; ++$i) {
            if ((($value >> $i) & 1) === 1) {
                return $i;
            }
        }

        return self::BITS;
    }

    /**
     * Returns the base-2 string representation of $value. Negative values use the
     * platform two's-complement form (PHP_INT_SIZE*8 bits). When $width is given,
     * the result is left-padded with '0' to at least $width characters.
     */
    public static function toStr(int $value, int $width = /* @infection-ignore-all: -1 also skips padding, and 1 never pads (decbin is never empty) */ 0): string
    {
        $bits = decbin($value);

        return /* @infection-ignore-all: padding to width 0 is a no-op, so > and >= agree */ $width > 0 ? Str::padStart($bits, $width, '0') : $bits;
    }

    /**
     * Rotates the bits of $value left by $by positions (a circular shift over the
     * full {@see PHP_INT_SIZE}*8-bit width — bits shifted off the top re-enter at
     * the bottom). $by is taken modulo the bit width, so any integer is accepted.
     */
    public static function rotateLeft(int $value, int $by): int
    {
        $by = ($by % self::BITS + self::BITS) % self::BITS;
        // @infection-ignore-all: at 0 the general path is (value << 0) | uShr(value, BITS), and uShr by BITS masks to 0 — identity
        if ($by === 0) {
            return $value;
        }

        return ($value << $by) | self::uShr($value, self::BITS - $by);
    }

    /**
     * Rotates the bits of $value right by $by positions (a circular shift over the
     * full {@see PHP_INT_SIZE}*8-bit width — bits shifted off the bottom re-enter
     * at the top). $by is taken modulo the bit width, so any integer is accepted.
     */
    public static function rotateRight(int $value, int $by): int
    {
        $by = ($by % self::BITS + self::BITS) % self::BITS;
        // @infection-ignore-all: at 0 the general path is uShr(value, 0) | (value << BITS), and both halves reduce to the value itself and 0 — identity
        if ($by === 0) {
            return $value;
        }

        return self::uShr($value, $by) | ($value << (self::BITS - $by));
    }

    /**
     * Returns the integer value of the base-2 string $bits.
     *
     * @throws InvalidArgumentException when $bits is empty, contains characters other than
     *                                  '0'/'1', or denotes a value larger than PHP_INT_MAX
     */
    public static function fromStr(string $bits): int
    {
        if ($bits === '' || Str::span($bits, '01') !== Str::len($bits)) {
            throw new InvalidArgumentException('Invalid binary string.');
        }
        $value = bindec($bits);
        if (!Num::isInt($value)) {
            throw new InvalidArgumentException('Binary string exceeds the integer range.');
        }

        return $value;
    }

    /**
     * Throws when $bit is outside the valid range [0, PHP_INT_SIZE*8 - 1].
     */
    private static function checkBitIndex(int $bit): void
    {
        if ($bit < 0 || $bit >= self::BITS) {
            throw new InvalidArgumentException('Bit index must be between 0 and ' . (self::BITS - 1) . '.');
        }
    }

    /**
     * Logical (unsigned, zero-filling) right shift by $shift positions, undoing
     * the sign-extension of PHP's arithmetic `>>`. Used by the rotate helpers;
     * $shift is expected in the range [1, BITS - 1].
     */
    private static function uShr(int $value, int $shift): int
    {
        return ($value >> $shift) & ~(-1 << (self::BITS - $shift));
    }
}
