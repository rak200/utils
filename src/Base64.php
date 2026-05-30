<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;
use function base64_decode, base64_encode, rtrim, str_repeat, strlen, strtr;

/**
 * Base64 helpers, including the URL-safe variant (RFC 4648 §5) without padding.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Base64 {
    private function __construct() {}

    /**
     * Returns true when $value is decodable by either {@see decode()} (standard
     * alphabet, strict) or {@see decodeUrl()} (URL-safe alphabet, missing
     * padding restored). The empty string is a valid encoding.
     */
    public static function is(string $value): bool {
        if (base64_decode($value, true) !== false) {
            return true;
        }
        $encoded = strtr($value, '-_', '+/');
        $padded = $encoded . str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        return base64_decode($padded, true) !== false;
    }

    /**
     * Encodes $value as standard Base64.
     */
    public static function encode(string $value): string {
        return base64_encode($value);
    }

    /**
     * Decodes a standard Base64 string. Strict: rejects any non-alphabet
     * character.
     *
     * @throws RuntimeException When $value is not valid Base64.
     */
    public static function decode(string $value): string {
        $result = base64_decode($value, true);
        if ($result === false) {
            throw new RuntimeException('Invalid base64 input.');
        }
        return $result;
    }

    /**
     * Encodes $value as URL-safe Base64 (replaces '+/' with '-_') with the
     * trailing '=' padding stripped.
     */
    public static function encodeUrl(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Decodes a URL-safe Base64 string. Missing '=' padding is restored before
     * decoding.
     *
     * @throws RuntimeException When $value is not valid URL-safe Base64.
     */
    public static function decodeUrl(string $value): string {
        $encoded = strtr($value, '-_', '+/');
        $padded = $encoded . str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $result = base64_decode($padded, true);
        if ($result === false) {
            throw new RuntimeException('Invalid base64url input.');
        }
        return $result;
    }
}
