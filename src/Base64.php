<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

final class Base64 {
    private function __construct() {}

    public static function encode(string $value): string {
        return base64_encode($value);
    }

    public static function decode(string $value): string {
        $result = base64_decode($value, true);
        if ($result === false) {
            throw new RuntimeException('Invalid base64 input.');
        }
        return $result;
    }

    public static function encodeUrl(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

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
