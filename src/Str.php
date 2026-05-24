<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

/**
 * Multibyte-safe string helpers.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Str {
    private function __construct() {}

    /**
     * Returns true if the string is empty or contains only whitespace.
     */
    public static function isBlank(string $value): bool {
        return trim($value) === '';
    }

    /**
     * Returns true if the string contains at least one non-whitespace character.
     */
    public static function isNotBlank(string $value): bool {
        return !self::isBlank($value);
    }

    /**
     * Returns true if the string has length zero.
     */
    public static function isEmpty(string $value): bool {
        return $value === '';
    }

    /**
     * Returns the number of Unicode characters in the string.
     */
    public static function length(string $value): int {
        return mb_strlen($value);
    }

    /**
     * Returns the string with its first character uppercased.
     */
    public static function capitalize(string $value): string {
        if ($value === '') {
            return '';
        }
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    /**
     * Returns the string with its first character lowercased.
     */
    public static function uncapitalize(string $value): string {
        if ($value === '') {
            return '';
        }
        return mb_strtolower(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    /**
     * Returns the string with every character uppercased.
     */
    public static function upper(string $value): string {
        return mb_strtoupper($value);
    }

    /**
     * Returns the string with every character lowercased.
     */
    public static function lower(string $value): string {
        return mb_strtolower($value);
    }

    /**
     * Returns true if $haystack contains $needle (an empty needle is always contained).
     */
    public static function contains(string $haystack, string $needle): bool {
        return str_contains($haystack, $needle);
    }

    /**
     * Returns true if $haystack starts with $needle.
     */
    public static function startsWith(string $haystack, string $needle): bool {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Returns true if $haystack ends with $needle.
     */
    public static function endsWith(string $haystack, string $needle): bool {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Strips $chars from both ends of the string (default: ASCII whitespace).
     */
    public static function trim(string $value, string $chars = " \t\n\r\0\x0B"): string {
        return trim($value, $chars);
    }

    /**
     * Strips $chars from the start of the string (default: ASCII whitespace).
     */
    public static function trimStart(string $value, string $chars = " \t\n\r\0\x0B"): string {
        return ltrim($value, $chars);
    }

    /**
     * Strips $chars from the end of the string (default: ASCII whitespace).
     */
    public static function trimEnd(string $value, string $chars = " \t\n\r\0\x0B"): string {
        return rtrim($value, $chars);
    }

    /**
     * Replaces every occurrence of $search with $replacement in $subject.
     */
    public static function replace(string $subject, string $search, string $replacement): string {
        return str_replace($search, $replacement, $subject);
    }

    /**
     * Replaces the first occurrence of $search with $replacement in $subject.
     * Returns $subject unchanged when $search is empty or not found.
     */
    public static function replaceFirst(string $subject, string $search, string $replacement): string {
        if ($search === '') {
            return $subject;
        }
        $pos = strpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }
        return substr_replace($subject, $replacement, $pos, strlen($search));
    }

    /**
     * Replaces the last occurrence of $search with $replacement in $subject.
     * Returns $subject unchanged when $search is empty or not found.
     */
    public static function replaceLast(string $subject, string $search, string $replacement): string {
        if ($search === '') {
            return $subject;
        }
        $pos = strrpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }
        return substr_replace($subject, $replacement, $pos, strlen($search));
    }

    /**
     * Returns the multibyte-safe substring of $value starting at character
     * index $start. When $length is null, takes the rest of the string.
     */
    public static function substring(string $value, int $start, ?int $length = null): string {
        return mb_substr($value, $start, $length);
    }

    /**
     * Returns the 0-based character index of the first occurrence of $needle
     * in $haystack starting at $offset, or -1 when not found.
     */
    public static function indexOf(string $haystack, string $needle, int $offset = 0): int {
        if ($needle === '') {
            return -1;
        }
        $pos = mb_strpos($haystack, $needle, $offset);
        return $pos === false ? -1 : $pos;
    }

    /**
     * Returns the 0-based character index of the last occurrence of $needle
     * in $haystack, or -1 when not found.
     */
    public static function lastIndexOf(string $haystack, string $needle): int {
        if ($needle === '') {
            return -1;
        }
        $pos = mb_strrpos($haystack, $needle);
        return $pos === false ? -1 : $pos;
    }

    /**
     * Returns the number of non-overlapping occurrences of $needle in $haystack
     * (byte-level count via {@see substr_count}).
     */
    public static function count(string $haystack, string $needle): int {
        return $needle === '' ? 0 : substr_count($haystack, $needle);
    }

    /**
     * Truncates $value to at most $length characters, appending $ellipsis when
     * truncation occurs. When $length is shorter than $ellipsis, returns the
     * leading $length characters of $ellipsis.
     *
     * @throws RuntimeException When $length is negative.
     */
    public static function truncate(string $value, int $length, string $ellipsis = '…'): string {
        if ($length < 0) {
            throw new RuntimeException('Length must be non-negative.');
        }
        if (mb_strlen($value) <= $length) {
            return $value;
        }
        $ellipsisLen = mb_strlen($ellipsis);
        if ($length <= $ellipsisLen) {
            return mb_substr($ellipsis, 0, $length);
        }
        return mb_substr($value, 0, $length - $ellipsisLen) . $ellipsis;
    }

    /**
     * Returns a URL-friendly slug of $value: transliterates to ASCII
     * (best-effort via iconv), lowercases, and collapses runs of non-alphanumerics
     * into $separator.
     */
    public static function slug(string $value, string $separator = '-'): string {
        $value = mb_strtolower($value);
        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', $separator, $value) ?? '';
        return trim($value, $separator);
    }

    /**
     * Splits the string on $separator. An empty separator yields individual characters,
     * in which case $limit (if given) controls the chunk size.
     *
     * @return list<string>
     */
    public static function split(string $value, string $separator, ?int $limit = null): array {
        if ($separator === '') {
            return mb_str_split($value, max(1, $limit ?? 1));
        }
        return $limit === null ? explode($separator, $value) : explode($separator, $value, $limit);
    }

    /**
     * Joins iterable items into a string, ignoring blank elements.
     *
     * When $lastSeparator is provided and there are 2+ parts, it is used between
     * the last two elements (e.g. ", " + " and " for an Oxford-style join).
     *
     * @param iterable<mixed> $items
     */
    public static function join(
        iterable $items,
        string $separator,
        string $prefix = '',
        string $suffix = '',
        ?string $lastSeparator = null,
    ): string {
        $parts = [];
        foreach ($items as $item) {
            $str = (string) $item;
            if (self::isNotBlank($str)) {
                $parts[] = $str;
            }
        }

        if ($parts === []) {
            return '';
        }

        if ($lastSeparator === null || count($parts) < 2) {
            return $prefix . implode($separator, $parts) . $suffix;
        }

        $last = array_pop($parts);
        return $prefix . implode($separator, $parts) . $lastSeparator . $last . $suffix;
    }

    /**
     * Wraps a non-blank string with $prefix and $suffix; returns '' if the string is blank.
     */
    public static function wrap(string $value, string $prefix = '', string $suffix = ''): string {
        return self::isBlank($value) ? '' : $prefix . $value . $suffix;
    }

    /**
     * Left-pads the string with $pad up to $length characters (multibyte-aware).
     *
     * @throws RuntimeException When $pad is empty.
     */
    public static function padStart(string $value, int $length, string $pad = ' '): string {
        if ($pad === '') {
            throw new RuntimeException('Pad string cannot be empty.');
        }
        return mb_str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * Right-pads the string with $pad up to $length characters (multibyte-aware).
     *
     * @throws RuntimeException When $pad is empty.
     */
    public static function padEnd(string $value, int $length, string $pad = ' '): string {
        if ($pad === '') {
            throw new RuntimeException('Pad string cannot be empty.');
        }
        return mb_str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * Repeats the string $times times.
     *
     * @throws RuntimeException When $times is negative.
     */
    public static function repeat(string $value, int $times): string {
        if ($times < 0) {
            throw new RuntimeException('Repeat count must be non-negative.');
        }
        return str_repeat($value, $times);
    }

    /**
     * Returns the string with its characters in reverse order (multibyte-aware).
     */
    public static function reverse(string $value): string {
        return implode('', array_reverse(mb_str_split($value)));
    }

    /**
     * Converts the string to camelCase (e.g. "hello world" → "helloWorld").
     */
    public static function toCamelCase(string $value): string {
        return self::uncapitalize(self::toPascalCase($value));
    }

    /**
     * Converts the string to PascalCase (e.g. "hello world" → "HelloWorld").
     */
    public static function toPascalCase(string $value): string {
        return implode('', array_map(self::capitalize(...), self::splitWords($value)));
    }

    /**
     * Converts the string to snake_case (e.g. "HelloWorld" → "hello_world").
     */
    public static function toSnakeCase(string $value): string {
        return implode('_', array_map(mb_strtolower(...), self::splitWords($value)));
    }

    /**
     * Converts the string to kebab-case (e.g. "HelloWorld" → "hello-world").
     */
    public static function toKebabCase(string $value): string {
        return implode('-', array_map(mb_strtolower(...), self::splitWords($value)));
    }

    /**
     * Splits the string into words on whitespace, dashes, underscores, and case
     * transitions (camelCase / PascalCase boundaries). Unicode-aware: handles
     * non-ASCII lowercase/uppercase via \p{Ll} / \p{Lu}.
     *
     * @return list<string>
     */
    private static function splitWords(string $value): array {
        $value = preg_replace('/(\p{Ll}|\p{Nd})(\p{Lu})/u', '$1 $2', $value) ?? $value;
        $value = preg_replace('/(\p{Lu}+)(\p{Lu}\p{Ll})/u', '$1 $2', $value) ?? $value;
        $parts = preg_split('/[\s_\-]+/u', $value) ?: [];
        return array_values(array_filter($parts, static fn(string $p): bool => $p !== ''));
    }
}
