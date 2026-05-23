<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

final class Bit {
    private const int BITS = PHP_INT_SIZE * 8;

    private function __construct() {}

    public static function set(int $value, int $bit): int {
        self::checkBitIndex($bit);
        return $value | (1 << $bit);
    }

    public static function unset(int $value, int $bit): int {
        self::checkBitIndex($bit);
        return $value & ~(1 << $bit);
    }

    public static function toggle(int $value, int $bit): int {
        self::checkBitIndex($bit);
        return $value ^ (1 << $bit);
    }

    public static function has(int $value, int $bit): bool {
        self::checkBitIndex($bit);
        return ($value & (1 << $bit)) !== 0;
    }

    public static function count(int $value): int {
        $count = 0;
        for ($i = 0; $i < self::BITS; $i++) {
            if ((($value >> $i) & 1) === 1) {
                $count++;
            }
        }
        return $count;
    }

    public static function leadingZeros(int $value): int {
        if ($value === 0) {
            return self::BITS;
        }
        for ($i = self::BITS - 1; $i >= 0; $i--) {
            if ((($value >> $i) & 1) === 1) {
                return self::BITS - 1 - $i;
            }
        }
        return self::BITS;
    }

    public static function trailingZeros(int $value): int {
        if ($value === 0) {
            return self::BITS;
        }
        for ($i = 0; $i < self::BITS; $i++) {
            if ((($value >> $i) & 1) === 1) {
                return $i;
            }
        }
        return self::BITS;
    }

    private static function checkBitIndex(int $bit): void {
        if ($bit < 0 || $bit >= self::BITS) {
            throw new RuntimeException(sprintf('Bit index must be between 0 and %d.', self::BITS - 1));
        }
    }
}
