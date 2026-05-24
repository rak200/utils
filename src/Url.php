<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;
use function http_build_query, parse_str, parse_url, rawurldecode, rawurlencode, sprintf;

/**
 * URL parsing, building, and query-string encode/decode.
 *
 * Parsing returns the same keyed components as PHP's {@see parse_url} (`scheme`,
 * `host`, `port`, `user`, `pass`, `path`, `query`, `fragment`), and {@see build}
 * accepts the same shape — so `Url::build(Url::parse($u))` round-trips. Query
 * helpers use RFC 3986 percent-encoding (the URL-safe variant of `application/
 * x-www-form-urlencoded`, with spaces as `%20` rather than `+`).
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Url {
    private function __construct() {}

    /**
     * Parses $url into its components.
     *
     * @throws RuntimeException When $url is not a valid URL.
     * @return array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, path?: string, query?: string, fragment?: string}
     */
    public static function parse(string $url): array {
        $result = self::parseOrNull($url);
        if ($result === null) {
            throw new RuntimeException(sprintf('Cannot parse "%s" as URL.', $url));
        }
        return $result;
    }

    /**
     * Parses $url into its components, or returns null when $url is malformed.
     *
     * @return array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, path?: string, query?: string, fragment?: string}|null
     */
    public static function parseOrNull(string $url): ?array {
        $result = parse_url($url);
        return $result === false ? null : $result;
    }

    /**
     * Builds a URL from its components (same shape as {@see parse}). Missing
     * pieces are omitted; `userinfo`, `port`, `query`, and `fragment` are only
     * emitted when present.
     *
     * @param array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, path?: string, query?: string, fragment?: string} $components
     */
    public static function build(array $components): string {
        $result = '';
        if (isset($components['scheme'])) {
            $result .= $components['scheme'] . '://';
        }
        if (isset($components['user'])) {
            $result .= $components['user'];
            if (isset($components['pass'])) {
                $result .= ':' . $components['pass'];
            }
            $result .= '@';
        }
        if (isset($components['host'])) {
            $result .= $components['host'];
        }
        if (isset($components['port'])) {
            $result .= ':' . $components['port'];
        }
        if (isset($components['path'])) {
            $result .= $components['path'];
        }
        if (isset($components['query']) && $components['query'] !== '') {
            $result .= '?' . $components['query'];
        }
        if (isset($components['fragment'])) {
            $result .= '#' . $components['fragment'];
        }
        return $result;
    }

    /**
     * Percent-encodes $value per RFC 3986 (spaces become `%20`, not `+`).
     */
    public static function encode(string $value): string {
        return rawurlencode($value);
    }

    /**
     * Reverses {@see encode}. Also accepts `+` (form-style spaces) only as a
     * literal `+`; use {@see decodeQuery} to parse a full query string where
     * `+` should mean space.
     */
    public static function decode(string $value): string {
        return rawurldecode($value);
    }

    /**
     * Builds an `application/x-www-form-urlencoded` query string from $params.
     * Nested arrays are encoded with PHP's bracket syntax (e.g. `tag[0]=a`).
     *
     * @param array<int|string, mixed> $params
     */
    public static function encodeQuery(array $params): string {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Parses a query string back into an associative array. Bracket syntax in
     * the input produces nested arrays (e.g. `tag[]=a&tag[]=b` → `['tag' => ['a', 'b']]`).
     *
     * @return array<string, mixed>
     */
    public static function decodeQuery(string $query): array {
        $result = [];
        parse_str($query, $result);
        return $result;
    }

    /**
     * Returns true when $url has a scheme component (e.g. `https:`, `mailto:`).
     */
    public static function isAbsolute(string $url): bool {
        $parsed = self::parseOrNull($url);
        return $parsed !== null && isset($parsed['scheme']);
    }
}
