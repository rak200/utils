<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;
use function array_pop, chr, ctype_alnum, ctype_alpha, ctype_digit, ctype_space, explode,
    function_exists, iconv, implode, is_string, levenshtein, ltrim, max, mb_check_encoding, mb_chr,
    mb_convert_case, mb_ord, min, mb_str_pad, mb_str_split, mb_stripos, mb_strlen, mb_strpos,
    mb_strripos, mb_strrpos, mb_strtolower, mb_strtoupper, mb_substr, ord, preg_match_all,
    preg_replace, preg_split, rtrim, similar_text, sscanf, str_contains, str_ends_with, str_ireplace,
    str_repeat, str_replace, str_starts_with, strlen, strpos, strrpos, strspn, strtolower, strtr,
    substr_count, substr_replace, trigger_error, trim, vsprintf, wordwrap;

/**
 * Multibyte-safe string helpers.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Str {
    private function __construct() {}

    /**
     * Returns true if $value is a string. Domain predicate for {@see Str};
     * {@see Type::isStr()} is an alias.
     *
     * @phpstan-assert-if-true string $value
     * @phpstan-assert-if-false !string $value
     */
    public static function is(mixed $value): bool {
        return is_string($value);
    }

    /**
     * Returns true if $value is null, an empty string, or a string containing
     * only whitespace. Non-string, non-null values return false. Accepts
     * `mixed` so it can be used as a guard on values whose type is not yet
     * known.
     */
    public static function isBlank(mixed $value): bool {
        if ($value === null) {
            return true;
        }
        return is_string($value) && trim($value) === '';
    }

    /**
     * Returns true if $value is a string with at least one non-whitespace
     * character. Non-strings always return false.
     */
    public static function isNotBlank(mixed $value): bool {
        return is_string($value) && trim($value) !== '';
    }

    /**
     * Returns true if the string has length zero.
     */
    public static function isEmpty(string $value): bool {
        return $value === '';
    }

    /**
     * Returns true if $value is a string with at least one character
     * (whitespace counts). Non-strings always return false.
     *
     * @deprecated since 1.14.0, redundant under strict typing — use a typed
     *             `string` parameter, or `Str::is($v) && $v !== ''` when a
     *             `mixed` guard is genuinely needed. Will be removed in 2.0.0.
     */
    public static function isNonEmptyStr(mixed $value): bool {
        return is_string($value) && $value !== '';
    }

    /**
     * Returns true when every character of $value is ASCII whitespace
     * (`[ \t\n\r\v\f]`); false for the empty string. Wraps {@see ctype_space()}.
     */
    public static function isWhitespace(string $value): bool {
        return ctype_space($value);
    }

    /**
     * Returns true when $value is non-empty and every character is an ASCII
     * digit `0-9`. ASCII-only (multibyte digits are not recognised); the empty
     * string is false. Wraps {@see ctype_digit()} without its int-argument trap.
     */
    public static function isDigits(string $value): bool {
        return $value !== '' && ctype_digit($value);
    }

    /**
     * Returns true when $value is non-empty and every character is an ASCII
     * letter `a-z`/`A-Z`. ASCII-only; the empty string is false. Wraps
     * {@see ctype_alpha()}.
     */
    public static function isAlpha(string $value): bool {
        return $value !== '' && ctype_alpha($value);
    }

    /**
     * Returns true when $value is non-empty and every character is an ASCII
     * letter or digit. ASCII-only; the empty string is false. Wraps
     * {@see ctype_alnum()}.
     */
    public static function isAlnum(string $value): bool {
        return $value !== '' && ctype_alnum($value);
    }

    /**
     * Returns the number of Unicode characters in the string.
     */
    public static function len(string $value): int {
        return mb_strlen($value);
    }

    /**
     * @deprecated since 1.14.0, use {@see self::len()} instead. Will be removed in 2.0.0.
     */
    public static function length(string $value): int {
        return self::len($value);
    }

    /**
     * Returns the number of bytes in the string — its raw byte length, the
     * byte-level counterpart to the character count {@see len()} returns.
     * The two are equal for pure-ASCII input and diverge once multibyte
     * characters are present (e.g. "é" is one character but two bytes in UTF-8).
     */
    public static function byteLen(string $value): int {
        return strlen($value);
    }

    /**
     * @deprecated since 1.14.0, use {@see self::byteLen()} instead. Will be removed in 2.0.0.
     */
    public static function byteLength(string $value): int {
        return self::byteLen($value);
    }

    /**
     * Returns the raw byte values (0–255) of $value as a 0-indexed list — the
     * byte-string counterpart to {@see Hex::toBytes()}. These are bytes, not
     * Unicode code points (use {@see ord()} for a character's code point).
     *
     * @return list<int>
     */
    public static function toBytes(string $value): array {
        $bytes = [];
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $bytes[] = ord($value[$i]);
        }
        return $bytes;
    }

    /**
     * Builds a binary string from a list of byte values (0–255). Inverse of
     * {@see toBytes()}; mirrors {@see Hex::fromBytes()}.
     *
     * @param list<int> $bytes
     * @throws RuntimeException When a value is outside 0–255.
     */
    public static function fromBytes(array $bytes): string {
        $result = '';
        foreach ($bytes as $byte) {
            if ($byte < 0 || $byte > 255) {
                throw new RuntimeException("Byte value out of range: $byte.");
            }
            $result .= chr($byte);
        }
        return $result;
    }

    /**
     * Returns the length of the initial segment of $value consisting only of
     * characters present in $chars — optionally limited to the window starting
     * at byte offset $start and spanning $length bytes. Byte-level (via
     * {@see strspn}); equals {@see length()} exactly when every character of
     * $value is in $chars.
     */
    public static function span(string $value, string $chars, int $start = 0, ?int $length = null): int {
        return strspn($value, $chars, $start, $length);
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
     * Returns the string title-cased: the first letter of each word uppercased
     * and the rest lowercased (multibyte-aware, via {@see mb_convert_case()}).
     * E.g. "hello WORLD" → "Hello World".
     */
    public static function title(string $value): string {
        return mb_convert_case($value, MB_CASE_TITLE);
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
     * Replaces every occurrence of $search with $replacement in $subject. Pass
     * $ignoreCase = true for a case-insensitive match (via {@see str_ireplace()}).
     */
    public static function replace(string $subject, string $search, string $replacement, bool $ignoreCase = false): string {
        return $ignoreCase
            ? str_ireplace($search, $replacement, $subject)
            : str_replace($search, $replacement, $subject);
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
        return substr_replace($subject, $replacement, $pos, self::byteLen($search));
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
        return substr_replace($subject, $replacement, $pos, self::byteLen($search));
    }

    /**
     * Replaces the $length characters of $value starting at character index
     * $start with $replacement (multibyte-aware). A negative $start counts from
     * the end; a negative $length leaves that many characters untouched at the
     * end. Use $length = 0 to insert without removing anything.
     */
    public static function replaceAt(string $value, int $start, int $length, string $replacement): string {
        $total = mb_strlen($value);
        $from = $start < 0 ? max(0, $total + $start) : min($start, $total);
        $to = $length < 0 ? max($from, $total + $length) : min($total, $from + $length);
        return mb_substr($value, 0, $from) . $replacement . mb_substr($value, $to);
    }

    /**
     * Translates characters in $value: each character of $from is replaced with
     * the character at the same position in $to. Multibyte-aware and applied in a
     * single pass — characters introduced by the replacement are not re-translated.
     *
     * @throws RuntimeException When $from and $to differ in character length.
     */
    public static function translate(string $value, string $from, string $to): string {
        $fromChars = mb_str_split($from);
        $toChars = mb_str_split($to);
        if (Arr::count($fromChars) !== Arr::count($toChars)) {
            throw new RuntimeException('Translation strings must have the same length.');
        }
        if ($fromChars === []) {
            return $value;
        }
        return strtr($value, Arr::combine($fromChars, $toChars));
    }

    /**
     * Returns the multibyte-safe substring of $value starting at character
     * index $start. When $length is null, takes the rest of the string.
     */
    public static function sub(string $value, int $start, ?int $length = null): string {
        return mb_substr($value, $start, $length);
    }

    /**
     * @deprecated since 1.14.0, use {@see self::sub()} instead. Will be removed in 2.0.0.
     */
    public static function substring(string $value, int $start, ?int $length = null): string {
        return self::sub($value, $start, $length);
    }

    /**
     * Returns the 0-based character index of the first occurrence of $needle
     * in $haystack starting at $offset, or -1 when not found. Pass
     * $ignoreCase = true for a case-insensitive search.
     */
    public static function indexOf(string $haystack, string $needle, int $offset = 0, bool $ignoreCase = false): int {
        if ($needle === '') {
            return -1;
        }
        $pos = $ignoreCase
            ? mb_stripos($haystack, $needle, $offset)
            : mb_strpos($haystack, $needle, $offset);
        return $pos === false ? -1 : $pos;
    }

    /**
     * Returns the 0-based character index of the last occurrence of $needle
     * in $haystack, or -1 when not found. $offset bounds the search the same
     * way as {@see mb_strrpos} (positive starts that many characters in;
     * negative stops that many characters before the end). Pass
     * $ignoreCase = true for a case-insensitive search.
     */
    public static function lastIndexOf(string $haystack, string $needle, int $offset = 0, bool $ignoreCase = false): int {
        if ($needle === '') {
            return -1;
        }
        $pos = $ignoreCase
            ? mb_strripos($haystack, $needle, $offset)
            : mb_strrpos($haystack, $needle, $offset);
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
     * Returns the part of $subject before the first occurrence of $search.
     * Returns $subject unchanged when $search is empty or not found.
     */
    public static function before(string $subject, string $search): string {
        if ($search === '') {
            return $subject;
        }
        $pos = self::indexOf($subject, $search);
        return $pos === -1 ? $subject : self::sub($subject, 0, $pos);
    }

    /**
     * Returns the part of $subject after the first occurrence of $search.
     * Returns $subject unchanged when $search is empty or not found.
     */
    public static function after(string $subject, string $search): string {
        if ($search === '') {
            return $subject;
        }
        $pos = self::indexOf($subject, $search);
        return $pos === -1 ? $subject : self::sub($subject, $pos + self::len($search));
    }

    /**
     * Truncates $value to at most $length characters, appending $ellipsis when
     * truncation occurs. When $length is shorter than $ellipsis, returns the
     * leading $length characters of $ellipsis.
     *
     * @throws RuntimeException When $length is negative.
     */
    public static function trunc(string $value, int $length, string $ellipsis = '…'): string {
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
     * @deprecated since 1.14.0, use {@see self::trunc()} instead. Will be removed in 2.0.0.
     */
    public static function truncate(string $value, int $length, string $ellipsis = '…'): string {
        return self::trunc($value, $length, $ellipsis);
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
        return Str::trim($value, $separator);
    }

    /**
     * Splits the string on $separator (default `''`). An empty separator yields
     * individual characters, in which case $limit (if given) controls the chunk
     * size; otherwise $limit caps the number of pieces (the final piece keeps the
     * remainder).
     *
     * @return list<string>
     */
    public static function split(string $value, string $separator = '', ?int $limit = null): array {
        if ($separator === '') {
            return mb_str_split($value, max(1, $limit ?? 1));
        }
        return $limit === null ? explode($separator, $value) : explode($separator, $value, $limit);
    }

    /**
     * Joins iterable items with $separator (default `''` concatenates), like
     * {@see implode()}. $prefix / $suffix wrap a non-empty result; $lastSeparator
     * (with 2+ parts) joins the final two elements (Oxford-style).
     *
     * Note: the default $skipBlanks = true — silently dropping blank items — is
     * deprecated since 1.12.0 and will be removed in 2.0.0 (it emits an
     * `E_USER_DEPRECATED`). Use {@see joinNatural()} to keep that behaviour, or
     * pass $skipBlanks = false for a plain {@see implode()}-style join.
     *
     * @param iterable<int|float|string|bool|\Stringable|null> $items
     */
    public static function join(
        iterable $items,
        string $separator = '',
        string $prefix = '',
        string $suffix = '',
        ?string $lastSeparator = null,
        bool $skipBlanks = true,
    ): string {
        if ($skipBlanks) {
            trigger_error(
                'Str::join() with $skipBlanks = true (the default) is deprecated since 1.12.0 and '
                    . 'will be removed in 2.0.0; use Str::joinNatural() to keep dropping blank items, '
                    . 'or pass $skipBlanks = false for a plain implode()-style join.',
                E_USER_DEPRECATED,
            );
            return self::joinNatural($items, $separator, $prefix, $suffix, $lastSeparator);
        }

        $parts = [];
        foreach ($items as $item) {
            $parts[] = (string) $item;
        }
        return self::assembleJoin($parts, $separator, $prefix, $suffix, $lastSeparator);
    }

    /**
     * Joins iterable items into a natural-language string: blank items are
     * dropped, $prefix / $suffix wrap a non-empty result, and $lastSeparator (if
     * given, with 2+ parts) joins the final two elements (e.g. ", " + " and " for
     * an Oxford-style join). Returns '' when no non-blank items remain.
     *
     * @param iterable<int|float|string|bool|\Stringable|null> $items
     */
    public static function joinNatural(
        iterable $items,
        string $separator = '',
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
        return self::assembleJoin($parts, $separator, $prefix, $suffix, $lastSeparator);
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
        return implode('', Arr::reverse(mb_str_split($value)));
    }

    /**
     * Returns the Unicode code point of the first character of $value.
     *
     * @throws RuntimeException When $value is empty or not valid UTF-8.
     */
    public static function ord(string $value): int {
        if ($value === '') {
            throw new RuntimeException('Cannot take the code point of an empty string.');
        }
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new RuntimeException('Invalid UTF-8 sequence.');
        }
        return mb_ord($value);
    }

    /**
     * Returns the character for the given Unicode $codepoint (0 to 0x10FFFF).
     *
     * @throws RuntimeException When $codepoint is outside the valid Unicode range.
     */
    public static function chr(int $codepoint): string {
        if ($codepoint < 0 || $codepoint > 0x10FFFF) {
            throw new RuntimeException("Invalid code point: $codepoint.");
        }
        return mb_chr($codepoint);
    }

    /**
     * Converts the string to camelCase (e.g. "hello world" → "helloWorld").
     */
    public static function toCamel(string $value): string {
        return self::uncapitalize(self::toPascal($value));
    }

    /**
     * Converts the string to PascalCase (e.g. "hello world" → "HelloWorld").
     */
    public static function toPascal(string $value): string {
        return implode('', Arr::map(self::splitWords($value), self::capitalize(...)));
    }

    /**
     * Converts the string to snake_case (e.g. "HelloWorld" → "hello_world").
     */
    public static function toSnake(string $value): string {
        return implode('_', Arr::map(self::splitWords($value), self::lower(...)));
    }

    /**
     * Converts the string to kebab-case (e.g. "HelloWorld" → "hello-world").
     */
    public static function toKebab(string $value): string {
        return implode('-', Arr::map(self::splitWords($value), self::lower(...)));
    }

    /**
     * @deprecated since 1.2.0, use {@see self::toCamel()} instead. Will be removed in 2.0.0.
     */
    public static function toCamelCase(string $value): string {
        return self::toCamel($value);
    }

    /**
     * @deprecated since 1.2.0, use {@see self::toPascal()} instead. Will be removed in 2.0.0.
     */
    public static function toPascalCase(string $value): string {
        return self::toPascal($value);
    }

    /**
     * @deprecated since 1.2.0, use {@see self::toSnake()} instead. Will be removed in 2.0.0.
     */
    public static function toSnakeCase(string $value): string {
        return self::toSnake($value);
    }

    /**
     * @deprecated since 1.2.0, use {@see self::toKebab()} instead. Will be removed in 2.0.0.
     */
    public static function toKebabCase(string $value): string {
        return self::toKebab($value);
    }

    /**
     * Wraps $value so no line exceeds $width characters, breaking on spaces with
     * $break. With $cut = true, words longer than $width are split mid-word.
     * Byte-level (via {@see wordwrap()}); reliable for ASCII text.
     *
     * @throws RuntimeException When $width is less than 1.
     */
    public static function wordWrap(string $value, int $width = 75, string $break = "\n", bool $cut = false): string {
        if ($width < 1) {
            throw new RuntimeException('Width must be at least 1.');
        }
        return wordwrap($value, $width, $break, $cut);
    }

    /**
     * Returns the number of words in $value — maximal runs of letters or digits,
     * Unicode-aware. Punctuation and whitespace separate words, so "it's" counts
     * as two.
     */
    public static function wordCount(string $value): int {
        $count = preg_match_all('/[\p{L}\p{N}]+/u', $value);
        return $count === false ? 0 : $count;
    }

    /**
     * Formats $template printf-style with the given $args, like
     * {@see vsprintf()}. E.g. `Str::format('%s is %d', 'x', 5)` → "x is 5".
     */
    public static function format(string $template, string|int|float|bool|null ...$args): string {
        return vsprintf($template, $args);
    }

    /**
     * Parses $value against the printf-style $format and returns the extracted
     * values — the inverse of {@see format()}, via {@see sscanf()}. Each
     * conversion that finds no match yields null in its slot.
     *
     * @return list<int|float|string|null>
     */
    public static function scan(string $value, string $format): array {
        $result = sscanf($value, $format);
        return Arr::is($result) ? $result : [];
    }

    /**
     * Returns the Levenshtein edit distance between $a and $b — the minimum
     * number of single-character insertions, deletions, or substitutions to turn
     * one into the other. Byte-level (not multibyte-aware); returns -1 when
     * either string exceeds 255 bytes.
     */
    public static function levenshtein(string $a, string $b): int {
        return levenshtein($a, $b);
    }

    /**
     * Returns how similar $a and $b are as a percentage from 0.0 to 100.0
     * (via {@see similar_text()}). Byte-level and asymmetric — swapping the
     * arguments can yield a different result.
     */
    public static function similarity(string $a, string $b): float {
        $percentage = 0.0;
        similar_text($a, $b, $percentage);
        return $percentage;
    }

    /**
     * Assembles already-collected $parts with $separator, $prefix / $suffix, and
     * the optional Oxford-style $lastSeparator. Shared by {@see join()} and
     * {@see joinNatural()}.
     *
     * @param list<string> $parts
     */
    private static function assembleJoin(
        array $parts,
        string $separator,
        string $prefix,
        string $suffix,
        ?string $lastSeparator,
    ): string {
        if ($parts === []) {
            return '';
        }
        if ($lastSeparator === null || Arr::count($parts) < 2) {
            return $prefix . implode($separator, $parts) . $suffix;
        }
        $last = array_pop($parts);
        return $prefix . implode($separator, $parts) . $lastSeparator . $last . $suffix;
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
        return Arr::values(Arr::filter($parts, static fn(string $p): bool => $p !== ''));
    }
}
