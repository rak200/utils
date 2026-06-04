<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

use function preg_grep;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function preg_replace_callback;
use function preg_split;

/**
 * PCRE regular expression helpers that throw on invalid patterns instead of
 * surfacing the silent `false` / null returns of the underlying preg_* functions.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Regex
{
    private function __construct() {}

    /**
     * Returns true when $pattern is a syntactically valid PCRE pattern (delimited
     * and compilable). Unlike the other {@see Regex} helpers, this never throws
     * on a bad pattern — that is exactly what it tests for.
     */
    public static function is(string $pattern): bool
    {
        return @preg_match($pattern, '') !== false;
    }

    /**
     * Returns true if $pattern matches anywhere in $subject.
     *
     * @throws RuntimeException when $pattern is invalid
     */
    public static function matches(string $pattern, string $subject): bool
    {
        $result = preg_match($pattern, $subject);
        if ($result === false) {
            throw new RuntimeException("Invalid regex pattern: {$pattern}");
        }

        return $result === 1;
    }

    /**
     * Returns the first match of $pattern in $subject, including any capture
     * groups indexed by position and by name.
     *
     * @return array<int|string, string>
     *
     * @throws RuntimeException when $pattern is invalid or there is no match
     */
    public static function match(string $pattern, string $subject): array
    {
        $result = self::matchOrNull($pattern, $subject);
        if ($result === null) {
            throw new RuntimeException("No match for pattern {$pattern}.");
        }

        return $result;
    }

    /**
     * Returns the first match of $pattern in $subject, or null when there is no match.
     *
     * @return null|array<int|string, string>
     *
     * @throws RuntimeException when $pattern is invalid
     */
    public static function matchOrNull(string $pattern, string $subject): ?array
    {
        $matches = [];
        $result = preg_match($pattern, $subject, $matches);
        if ($result === false) {
            throw new RuntimeException("Invalid regex pattern: {$pattern}");
        }

        return $result === 1 ? $matches : null;
    }

    /**
     * Returns every match of $pattern in $subject. The outer keys are the group
     * indexes/names; each value is the list of captures for that group.
     *
     * @return array<int|string, list<string>>
     *
     * @throws RuntimeException when $pattern is invalid
     */
    public static function matchAll(string $pattern, string $subject): array
    {
        $matches = [];
        $result = preg_match_all($pattern, $subject, $matches);
        if ($result === false) {
            throw new RuntimeException("Invalid regex pattern: {$pattern}");
        }

        return $matches;
    }

    /**
     * Replaces every match of $pattern in $subject with $replacement.
     * $replacement may reference capture groups (e.g. "$1", "${name}").
     *
     * @throws RuntimeException when $pattern is invalid
     */
    public static function replace(string $pattern, string $replacement, string $subject): string
    {
        $result = preg_replace($pattern, $replacement, $subject);
        if ($result === null) {
            throw new RuntimeException("Invalid regex pattern: {$pattern}");
        }

        return $result;
    }

    /**
     * Replaces every match of $pattern in $subject with the result of $callback,
     * which receives the matches array and returns the replacement string.
     *
     * @param callable(array<int|string, string>): string $callback
     *
     * @throws RuntimeException when $pattern is invalid
     */
    public static function replaceCallback(string $pattern, callable $callback, string $subject): string
    {
        $result = preg_replace_callback($pattern, $callback, $subject);
        if ($result === null) {
            throw new RuntimeException("Invalid regex pattern: {$pattern}");
        }

        return $result;
    }

    /**
     * Splits $subject on every match of $pattern.
     *
     * @return list<string>
     *
     * @throws RuntimeException when $pattern is invalid
     */
    public static function split(string $pattern, string $subject): array
    {
        $result = preg_split($pattern, $subject);
        if ($result === false) {
            throw new RuntimeException("Invalid regex pattern: {$pattern}");
        }

        return $result;
    }

    /**
     * Returns the elements of $values that match $pattern, preserving their
     * keys. With $invert = true, returns the elements that do *not* match.
     *
     * @param array<array-key, string> $values
     *
     * @return array<array-key, string>
     *
     * @throws RuntimeException when $pattern is invalid
     */
    public static function grep(string $pattern, array $values, bool $invert = false): array
    {
        /** @var array<array-key, string>|false $result */
        $result = preg_grep($pattern, $values, $invert ? PREG_GREP_INVERT : 0);
        if ($result === false) {
            throw new RuntimeException("Invalid regex pattern: {$pattern}");
        }

        return $result;
    }

    /**
     * Escapes regex meta-characters in $value (and the given $delimiter) so
     * the result can be embedded literally inside a pattern.
     */
    public static function quote(string $value, string $delimiter = '/'): string
    {
        return preg_quote($value, $delimiter);
    }
}
