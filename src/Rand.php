<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

final class Rand {
    public const string NUM = '0123456789';
    public const string HEX = '0123456789abcdef';
    public const string ALPHA = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const string ALNUM = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const string CROCKFORD_BASE32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const string NANOID_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';

    private function __construct() {}

    public static function int(int $min, int $max): int {
        if ($min > $max) {
            throw new RuntimeException('Min cannot be greater than max.');
        }
        return random_int($min, $max);
    }

    public static function float(float $min, float $max): float {
        if ($min > $max) {
            throw new RuntimeException('Min cannot be greater than max.');
        }
        $ratio = random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
        return $min + ($max - $min) * $ratio;
    }

    public static function bytes(int $length): string {
        if ($length < 1) {
            throw new RuntimeException('Length must be at least 1.');
        }
        return random_bytes($length);
    }

    public static function string(int $length, string $alphabet = self::ALNUM): string {
        if ($length < 1) {
            throw new RuntimeException('Length must be at least 1.');
        }
        if ($alphabet === '') {
            throw new RuntimeException('Alphabet cannot be empty.');
        }
        return self::pickFromAlphabet($length, $alphabet);
    }

    /**
     * Generates a string by replacing every '#' in $pattern with a random
     * character from $alphabet. All other characters are emitted literally.
     */
    public static function masked(string $pattern, string $alphabet = self::ALNUM): string {
        if ($pattern === '') {
            throw new RuntimeException('Pattern cannot be empty.');
        }
        if ($alphabet === '') {
            throw new RuntimeException('Alphabet cannot be empty.');
        }
        $alphabetLen = strlen($alphabet);
        $result = '';
        foreach (str_split($pattern) as $char) {
            $result .= $char === '#'
                ? $alphabet[random_int(0, $alphabetLen - 1)]
                : $char;
        }
        return $result;
    }

    public static function uuid(): string {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return self::formatUuid($bytes);
    }

    public static function uuidV7(): string {
        $timestampMs = (int) (microtime(true) * 1000);
        $bytes = chr(($timestampMs >> 40) & 0xff)
            . chr(($timestampMs >> 32) & 0xff)
            . chr(($timestampMs >> 24) & 0xff)
            . chr(($timestampMs >> 16) & 0xff)
            . chr(($timestampMs >> 8) & 0xff)
            . chr($timestampMs & 0xff)
            . random_bytes(10);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return self::formatUuid($bytes);
    }

    public static function ulid(): string {
        $timestampMs = (int) (microtime(true) * 1000);
        $bytes = chr(($timestampMs >> 40) & 0xff)
            . chr(($timestampMs >> 32) & 0xff)
            . chr(($timestampMs >> 24) & 0xff)
            . chr(($timestampMs >> 16) & 0xff)
            . chr(($timestampMs >> 8) & 0xff)
            . chr($timestampMs & 0xff)
            . random_bytes(10);
        return self::encodeCrockfordBase32($bytes);
    }

    public static function nanoid(int $length = 21): string {
        if ($length < 1) {
            throw new RuntimeException('Length must be at least 1.');
        }
        return self::pickFromAlphabet($length, self::NANOID_ALPHABET);
    }

    private static function pickFromAlphabet(int $length, string $alphabet): string {
        $alphabetLen = strlen($alphabet);
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[random_int(0, $alphabetLen - 1)];
        }
        return $result;
    }

    private static function formatUuid(string $bytes): string {
        $hex = bin2hex($bytes);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /**
     * Encodes 16 bytes (128 bits) into a 26-character Crockford Base32 string
     * by left-padding with 2 zero bits to reach 130 bits = 26 × 5-bit groups.
     */
    private static function encodeCrockfordBase32(string $bytes): string {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $bits = '00' . $bits;
        $result = '';
        for ($i = 0; $i < 130; $i += 5) {
            $result .= self::CROCKFORD_BASE32[bindec(substr($bits, $i, 5))];
        }
        return $result;
    }
}
