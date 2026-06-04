<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

use function microtime;
use function random_bytes;
use function random_int;

/**
 * Cryptographically-secure random helpers, plus UUID, ULID, and NanoID generators.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Rand
{
    /** Decimal digits 0-9. */
    public const string NUM = '0123456789';

    /** Lowercase hexadecimal digits 0-9 a-f. */
    public const string HEX = '0123456789abcdef';

    /** ASCII letters a-z A-Z. */
    public const string ALPHA = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** ASCII letters and decimal digits. */
    public const string ALNUM = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const string CROCKFORD_BASE32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const string NANOID_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';

    private function __construct() {}

    /**
     * Returns a cryptographically-secure integer in the closed range [$min, $max].
     *
     * @throws RuntimeException when $min > $max
     */
    public static function int(int $min, int $max): int
    {
        if ($min > $max) {
            throw new RuntimeException('Min cannot be greater than max.');
        }

        return random_int($min, $max);
    }

    /**
     * Returns a cryptographically-seeded float in the closed range [$min, $max].
     *
     * @throws RuntimeException when $min > $max
     */
    public static function float(float $min, float $max): float
    {
        if ($min > $max) {
            throw new RuntimeException('Min cannot be greater than max.');
        }
        $ratio = random_int(0, PHP_INT_MAX) / PHP_INT_MAX;

        return $min + ($max - $min) * $ratio;
    }

    /**
     * Returns $length cryptographically-secure random bytes (raw binary).
     *
     * @throws RuntimeException when $length < 1
     */
    public static function bytes(int $length): string
    {
        if ($length < 1) {
            throw new RuntimeException('Length must be at least 1.');
        }

        return random_bytes($length);
    }

    /**
     * Returns a random string of $length characters drawn from $alphabet.
     *
     * @throws RuntimeException when $length < 1 or $alphabet is empty
     */
    public static function string(int $length, string $alphabet = self::ALNUM): string
    {
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
     *
     * @throws RuntimeException when $pattern or $alphabet is empty
     */
    public static function masked(string $pattern, string $alphabet = self::ALNUM): string
    {
        if ($pattern === '') {
            throw new RuntimeException('Pattern cannot be empty.');
        }
        if ($alphabet === '') {
            throw new RuntimeException('Alphabet cannot be empty.');
        }
        $alphabetLen = Str::byteLen($alphabet);
        $result = '';
        foreach (Str::split($pattern) as $char) {
            $result .= $char === '#'
                ? $alphabet[random_int(0, $alphabetLen - 1)]
                : $char;
        }

        return $result;
    }

    /**
     * Generates a random UUID version 4 (RFC 4122) in canonical 8-4-4-4-12 hex form.
     */
    public static function uuidV4(): string
    {
        $bytes = Str::toBytes(random_bytes(16));
        $bytes[6] = ($bytes[6] & 0x0F) | 0x40;
        $bytes[8] = ($bytes[8] & 0x3F) | 0x80;

        return self::formatUuid($bytes);
    }

    /**
     * Generates a UUID version 7 (time-ordered, RFC 9562) using the current
     * millisecond timestamp as a prefix.
     */
    public static function uuidV7(): string
    {
        $bytes = [...self::timestampBytes(), ...Str::toBytes(random_bytes(10))];
        $bytes[6] = ($bytes[6] & 0x0F) | 0x70;
        $bytes[8] = ($bytes[8] & 0x3F) | 0x80;

        return self::formatUuid($bytes);
    }

    /**
     * Generates a ULID: 26 Crockford Base32 characters, time-ordered by
     * millisecond prefix.
     */
    public static function ulid(): string
    {
        $bytes = [...self::timestampBytes(), ...Str::toBytes(random_bytes(10))];

        return self::encodeCrockfordBase32($bytes);
    }

    /**
     * Returns a cryptographically-secure random boolean.
     */
    public static function bool(): bool
    {
        return random_int(0, 1) === 1;
    }

    /**
     * Returns a cryptographically-secure random element of $items.
     *
     * @template T
     *
     * @param array<array-key, T> $items
     *
     * @return T
     *
     * @throws RuntimeException when $items is empty
     */
    public static function choice(array $items): mixed
    {
        if ($items === []) {
            throw new RuntimeException('Cannot pick from an empty array.');
        }
        $keys = Arr::keys($items);

        return $items[$keys[random_int(0, Arr::count($keys) - 1)]];
    }

    /**
     * Returns the values of $items in a uniformly-random order (Fisher-Yates
     * with {@see random_int} as the source of entropy). Keys are not preserved.
     *
     * @template T
     *
     * @param array<array-key, T> $items
     *
     * @return list<T>
     */
    public static function shuffle(array $items): array
    {
        $values = Arr::values($items);
        for ($i = Arr::count($values) - 1; $i > 0; --$i) {
            $j = random_int(0, $i);
            if ($i !== $j) {
                [$values[$i], $values[$j]] = [$values[$j], $values[$i]];
            }
        }

        return Arr::values($values);
    }

    /**
     * Generates a NanoID using the standard 64-character URL-safe alphabet.
     *
     * @throws RuntimeException when $length < 1
     */
    public static function nanoid(int $length = 21): string
    {
        if ($length < 1) {
            throw new RuntimeException('Length must be at least 1.');
        }

        return self::pickFromAlphabet($length, self::NANOID_ALPHABET);
    }

    /**
     * Builds a string of $length characters by drawing each character uniformly
     * at random from $alphabet with {@see random_int}.
     */
    private static function pickFromAlphabet(int $length, string $alphabet): string
    {
        $alphabetLen = Str::byteLen($alphabet);
        $result = '';
        for ($i = 0; $i < $length; ++$i) {
            $result .= $alphabet[random_int(0, $alphabetLen - 1)];
        }

        return $result;
    }

    /**
     * Returns the current Unix-millisecond timestamp as 6 big-endian byte
     * values — the time prefix shared by {@see uuidV7()} and {@see ulid()}.
     *
     * @return list<int>
     */
    private static function timestampBytes(): array
    {
        $ms = (int) (microtime(true) * 1000);

        return [
            ($ms >> 40) & 0xFF,
            ($ms >> 32) & 0xFF,
            ($ms >> 24) & 0xFF,
            ($ms >> 16) & 0xFF,
            ($ms >> 8) & 0xFF,
            $ms & 0xFF,
        ];
    }

    /**
     * Renders 16 byte values as the canonical 8-4-4-4-12 hex UUID string.
     *
     * @param int[] $bytes
     */
    private static function formatUuid(array $bytes): string
    {
        $hex = Hex::fromBytes($bytes);
        $a = Str::sub($hex, 0, 8);
        $b = Str::sub($hex, 8, 4);
        $c = Str::sub($hex, 12, 4);
        $d = Str::sub($hex, 16, 4);
        $e = Str::sub($hex, 20, 12);

        return "{$a}-{$b}-{$c}-{$d}-{$e}";
    }

    /**
     * Encodes 16 byte values (128 bits) into a 26-character Crockford Base32
     * string by left-padding with 2 zero bits to reach 130 bits = 26 × 5-bit groups.
     *
     * @param list<int> $bytes
     */
    private static function encodeCrockfordBase32(array $bytes): string
    {
        $bits = '';
        foreach ($bytes as $byte) {
            $bits .= Bit::toStr($byte, 8);
        }
        $bits = '00'.$bits;
        $result = '';
        for ($i = 0; $i < 130; $i += 5) {
            $result .= self::CROCKFORD_BASE32[Bit::fromStr(Str::sub($bits, $i, 5))];
        }

        return $result;
    }
}
