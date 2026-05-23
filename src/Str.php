<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

final class Str {
    private function __construct() {}

    public static function isBlank(string $value): bool {
        return trim($value) === '';
    }

    public static function isNotBlank(string $value): bool {
        return !self::isBlank($value);
    }

    public static function isEmpty(string $value): bool {
        return $value === '';
    }

    public static function length(string $value): int {
        return mb_strlen($value);
    }

    public static function capitalize(string $value): string {
        if ($value === '') {
            return '';
        }
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    public static function uncapitalize(string $value): string {
        if ($value === '') {
            return '';
        }
        return mb_strtolower(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    public static function upper(string $value): string {
        return mb_strtoupper($value);
    }

    public static function lower(string $value): string {
        return mb_strtolower($value);
    }

    public static function contains(string $haystack, string $needle): bool {
        return $needle === '' || str_contains($haystack, $needle);
    }

    public static function startsWith(string $haystack, string $needle): bool {
        return str_starts_with($haystack, $needle);
    }

    public static function endsWith(string $haystack, string $needle): bool {
        return str_ends_with($haystack, $needle);
    }

    public static function trim(string $value, string $chars = " \t\n\r\0\x0B"): string {
        return trim($value, $chars);
    }

    public static function trimStart(string $value, string $chars = " \t\n\r\0\x0B"): string {
        return ltrim($value, $chars);
    }

    public static function trimEnd(string $value, string $chars = " \t\n\r\0\x0B"): string {
        return rtrim($value, $chars);
    }

    public static function replace(string $subject, string $search, string $replacement): string {
        return str_replace($search, $replacement, $subject);
    }

    /**
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

    public static function wrap(string $value, string $prefix = '', string $suffix = ''): string {
        return self::isBlank($value) ? '' : $prefix . $value . $suffix;
    }

    public static function padStart(string $value, int $length, string $pad = ' '): string {
        if ($pad === '') {
            throw new RuntimeException('Pad string cannot be empty.');
        }
        return mb_str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    public static function padEnd(string $value, int $length, string $pad = ' '): string {
        if ($pad === '') {
            throw new RuntimeException('Pad string cannot be empty.');
        }
        return mb_str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    public static function repeat(string $value, int $times): string {
        if ($times < 0) {
            throw new RuntimeException('Repeat count must be non-negative.');
        }
        return str_repeat($value, $times);
    }

    public static function reverse(string $value): string {
        return implode('', array_reverse(mb_str_split($value)));
    }

    public static function toCamelCase(string $value): string {
        return self::uncapitalize(self::toPascalCase($value));
    }

    public static function toPascalCase(string $value): string {
        return implode('', array_map(self::capitalize(...), self::splitWords($value)));
    }

    public static function toSnakeCase(string $value): string {
        return implode('_', array_map(mb_strtolower(...), self::splitWords($value)));
    }

    public static function toKebabCase(string $value): string {
        return implode('-', array_map(mb_strtolower(...), self::splitWords($value)));
    }

    /**
     * @return list<string>
     */
    private static function splitWords(string $value): array {
        $value = preg_replace('/([a-z\d])([A-Z])/u', '$1 $2', $value) ?? $value;
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/u', '$1 $2', $value) ?? $value;
        $parts = preg_split('/[\s_\-]+/u', $value) ?: [];
        return array_values(array_filter($parts, static fn(string $p): bool => $p !== ''));
    }
}
