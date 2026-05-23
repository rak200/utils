<?php

declare(strict_types=1);

namespace Rak200\Utils;

use JsonException;

final class Json {
    private function __construct() {}

    public static function encode(mixed $value, int $flags = 0): string {
        return json_encode($value, $flags | JSON_THROW_ON_ERROR);
    }

    public static function decode(string $json, bool $assoc = true): mixed {
        return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
    }

    public static function isValid(string $json): bool {
        try {
            self::decode($json);
            return true;
        } catch (JsonException) {
            return false;
        }
    }
}
