<?php

declare(strict_types=1);

namespace Rak200\Utils;

use JsonException;
use function json_decode, json_encode;

/**
 * JSON helpers — always throw {@see JsonException} on malformed input/output
 * (JSON_THROW_ON_ERROR is forced).
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Json {
    private function __construct() {}

    /**
     * Encodes $value as a JSON string. $flags is OR'd with JSON_THROW_ON_ERROR.
     *
     * @throws JsonException When $value cannot be encoded.
     */
    public static function encode(mixed $value, int $flags = 0): string {
        return json_encode($value, $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * Decodes the JSON string $json. With $assoc=true (default) objects become
     * associative arrays; with $assoc=false they become stdClass instances.
     *
     * @throws JsonException When $json is not valid JSON.
     */
    public static function decode(string $json, bool $assoc = true): mixed {
        return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Returns true when $json parses successfully as JSON.
     */
    public static function isValid(string $json): bool {
        try {
            self::decode($json);
            return true;
        } catch (JsonException) {
            return false;
        }
    }
}
