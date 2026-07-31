<?php

declare(strict_types=1);

namespace Rak200\Utils;

use JsonException;
use Rak200\Utils\Exception\MalformedJsonException;

use function json_decode;
use function json_encode;
use function json_validate;

/**
 * JSON helpers — always throw {@see MalformedJsonException} on malformed
 * input/output (JSON_THROW_ON_ERROR is forced).
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Json
{
    private function __construct() {}

    /**
     * Encodes $value as a JSON string. $flags is OR'd with JSON_THROW_ON_ERROR.
     *
     * @throws MalformedJsonException when $value cannot be encoded
     */
    public static function encode(mixed $value, int $flags = 0): string
    {
        try {
            return json_encode($value, $flags | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MalformedJsonException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Decodes the JSON string $json. With $assoc=true (default) objects become
     * associative arrays; with $assoc=false they become stdClass instances.
     *
     * @throws MalformedJsonException when $json is not valid JSON
     */
    public static function decode(string $json, bool $assoc = true): mixed
    {
        try {
            return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MalformedJsonException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns true when $value is a string that parses successfully as JSON.
     * Domain predicate for {@see Json}; accepts `mixed` so it can be used as a
     * guard.
     *
     * @phpstan-assert-if-true string $value
     */
    public static function is(mixed $value): bool
    {
        return Str::is($value) && json_validate($value);
    }
}
