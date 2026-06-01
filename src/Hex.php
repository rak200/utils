<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;
use function bin2hex, ctype_xdigit, dechex, hex2bin, hexdec, str_split;

/**
 * Hexadecimal encoding of binary strings — the byte-string ↔ hex-string
 * counterpart to {@see Base64}. Operates on raw bytes, not characters.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Hex {
    private function __construct() {}

    /**
     * Returns true when $value is decodable by {@see decode()}: an even number
     * of hexadecimal digits (`0-9`, `a-f`, `A-F`). The empty string is a valid
     * encoding (decodes to ``).
     */
    public static function is(string $value): bool {
        if ($value === '') {
            return true;
        }
        return Str::length($value) % 2 === 0 && ctype_xdigit($value);
    }

    /**
     * Encodes a binary string as lowercase hexadecimal (two digits per byte).
     */
    public static function encode(string $value): string {
        return bin2hex($value);
    }

    /**
     * Decodes a hexadecimal string back to its binary form. Accepts upper- and
     * lowercase digits.
     *
     * @throws RuntimeException When $value is not valid hex (odd length or a
     *                          non-hex character).
     */
    public static function decode(string $value): string {
        if (!self::is($value)) {
            throw new RuntimeException('Invalid hex input.');
        }
        $result = hex2bin($value);
        if ($result === false) {
            throw new RuntimeException('Invalid hex input.');
        }
        return $result;
    }

    /**
     * Decodes a hexadecimal string to a list of its byte values (0–255).
     * Accepts upper- and lowercase digits; the empty string yields `[]`.
     *
     * @return list<int>
     * @throws RuntimeException When $value is not valid hex (odd length or a
     *                          non-hex character).
     */
    public static function toBytes(string $value): array {
        if (!self::is($value)) {
            throw new RuntimeException('Invalid hex input.');
        }
        $bytes = [];
        foreach (str_split($value, 2) as $pair) {
            $bytes[] = (int) hexdec($pair);
        }
        return $bytes;
    }

    /**
     * Encodes a list of byte values (0–255) as lowercase hexadecimal (two
     * digits per byte). The empty array yields ``.
     *
     * @param array<int> $bytes
     * @throws RuntimeException When a value is outside the 0–255 byte range.
     */
    public static function fromBytes(array $bytes): string {
        $hex = '';
        foreach ($bytes as $byte) {
            if ($byte < 0 || $byte > 255) {
                throw new RuntimeException("Byte value out of range (0-255): $byte.");
            }
            $hex .= Str::padStart(dechex($byte), 2, '0');
        }
        return $hex;
    }
}
