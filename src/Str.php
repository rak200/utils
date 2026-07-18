<?php

declare(strict_types=1);

namespace Rak200\Utils;

use InvalidArgumentException;
use Stringable;
use UnderflowException;

use function array_pop;
use function chr;
use function ctype_alnum;
use function ctype_alpha;
use function ctype_digit;
use function ctype_space;
use function explode;
use function function_exists;
use function iconv;
use function implode;
use function is_string;
use function levenshtein;
use function ltrim;
use function max;
use function mb_check_encoding;
use function mb_chr;
use function mb_convert_case;
use function mb_ord;
use function mb_str_pad;
use function mb_str_split;
use function mb_stripos;
use function mb_strlen;
use function mb_strpos;
use function mb_strripos;
use function mb_strrpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function min;
use function ord;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function rtrim;
use function similar_text;
use function sscanf;
use function str_contains;
use function str_ends_with;
use function str_ireplace;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function strspn;
use function strtolower;
use function strtr;
use function substr_count;
use function substr_replace;
use function trim;
use function vsprintf;
use function wordwrap;

/**
 * Multibyte-safe string helpers.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Str
{
    private function __construct() {}

    /**
     * Returns true if $value is a string. Domain predicate for {@see Str};
     * {@see Type::isStr()} is an alias.
     *
     * @phpstan-assert-if-true string $value
     *
     * @phpstan-assert-if-false !string $value
     */
    public static function is(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Returns true if $value is null, an empty string, or a string containing
     * only whitespace. Non-string, non-null values return false. Accepts
     * `mixed` so it can be used as a guard on values whose type is not yet
     * known.
     */
    public static function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }

    /**
     * Returns true if $value is a string with at least one non-whitespace
     * character. Non-strings always return false.
     */
    public static function isNotBlank(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /**
     * Returns true if the string has length zero.
     */
    public static function isEmpty(string $value): bool
    {
        return $value === '';
    }

    /**
     * Returns true when every character of $value is ASCII whitespace
     * (`[ \t\n\r\v\f]`); false for the empty string. Wraps {@see ctype_space()}.
     */
    public static function isWhitespace(string $value): bool
    {
        return ctype_space($value);
    }

    /**
     * Returns true when $value is non-empty and every character is an ASCII
     * digit `0-9`. ASCII-only (multibyte digits are not recognised); the empty
     * string is false. Wraps {@see ctype_digit()} without its int-argument trap.
     */
    public static function isDigits(string $value): bool
    {
        return $value !== '' && ctype_digit($value);
    }

    /**
     * Returns true when $value is non-empty and every character is an ASCII
     * letter `a-z`/`A-Z`. ASCII-only; the empty string is false. Wraps
     * {@see ctype_alpha()}.
     */
    public static function isAlpha(string $value): bool
    {
        return $value !== '' && ctype_alpha($value);
    }

    /**
     * Returns true when $value is non-empty and every character is an ASCII
     * letter or digit. ASCII-only; the empty string is false. Wraps
     * {@see ctype_alnum()}.
     */
    public static function isAlnum(string $value): bool
    {
        return $value !== '' && ctype_alnum($value);
    }

    /**
     * Returns the number of Unicode characters in the string.
     */
    public static function len(string $value): int
    {
        return mb_strlen($value);
    }

    /**
     * Returns the number of bytes in the string — its raw byte length, the
     * byte-level counterpart to the character count {@see len()} returns.
     * The two are equal for pure-ASCII input and diverge once multibyte
     * characters are present (e.g. "é" is one character but two bytes in UTF-8).
     */
    public static function byteLen(string $value): int
    {
        return strlen($value);
    }

    /**
     * Returns the raw byte values (0–255) of $value as a 0-indexed list — the
     * byte-string counterpart to {@see Hex::toBytes()}. These are bytes, not
     * Unicode code points (use {@see ord()} for a character's code point).
     *
     * @return list<int>
     */
    public static function toBytes(string $value): array
    {
        $bytes = [];
        $len = strlen($value);
        for ($i = 0; $i < $len; ++$i) {
            $bytes[] = ord($value[$i]);
        }

        return $bytes;
    }

    /**
     * Builds a binary string from a list of byte values (0–255). Inverse of
     * {@see toBytes()}; mirrors {@see Hex::fromBytes()}.
     *
     * @param list<int> $bytes
     *
     * @throws InvalidArgumentException when a value is outside 0–255
     */
    public static function fromBytes(array $bytes): string
    {
        $result = '';
        foreach ($bytes as $byte) {
            if ($byte < 0 || $byte > 255) {
                throw new InvalidArgumentException("Byte value out of range: {$byte}.");
            }
            $result .= chr($byte);
        }

        return $result;
    }

    /**
     * Returns the length of the initial segment of $value consisting only of
     * characters present in $chars — optionally limited to the window starting
     * at byte offset $start and spanning $length bytes. Byte-level (via
     * {@see strspn}); equals {@see len()} exactly when every character of
     * $value is in $chars.
     */
    public static function span(string $value, string $chars, int $start = 0, ?int $length = null): int
    {
        return strspn($value, $chars, $start, $length);
    }

    /**
     * Returns the string with its first character uppercased.
     */
    public static function capitalize(string $value): string
    {
        if ($value === '') {
            // @infection-ignore-all: falling through concatenates two empty substrings — same result
            return '';
        }

        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    /**
     * Returns the string with its first character lowercased.
     */
    public static function uncapitalize(string $value): string
    {
        if ($value === '') {
            // @infection-ignore-all: falling through concatenates two empty substrings — same result
            return '';
        }

        return mb_strtolower(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    /**
     * Returns the string with every character uppercased.
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value);
    }

    /**
     * Returns the string with every character lowercased.
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value);
    }

    /**
     * Returns the string title-cased: the first letter of each word uppercased
     * and the rest lowercased (multibyte-aware, via {@see mb_convert_case()}).
     * E.g. "hello WORLD" → "Hello World".
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE);
    }

    /**
     * Returns true if $haystack contains $needle (an empty needle is always contained).
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Returns true if $haystack starts with $needle.
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Returns true if $haystack ends with $needle.
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Strips $chars from both ends of the string (default: ASCII whitespace).
     */
    public static function trim(string $value, string $chars = " \t\n\r\0\x0B"): string
    {
        return trim($value, $chars);
    }

    /**
     * Strips $chars from the start of the string (default: ASCII whitespace).
     */
    public static function trimStart(string $value, string $chars = " \t\n\r\0\x0B"): string
    {
        return ltrim($value, $chars);
    }

    /**
     * Strips $chars from the end of the string (default: ASCII whitespace).
     */
    public static function trimEnd(string $value, string $chars = " \t\n\r\0\x0B"): string
    {
        return rtrim($value, $chars);
    }

    /**
     * Replaces every occurrence of $search with $replacement in $subject. Pass
     * $ignoreCase = true for a case-insensitive match (via {@see str_ireplace()}).
     */
    public static function replace(string $subject, string $search, string $replacement, bool $ignoreCase = false): string
    {
        return $ignoreCase
            ? str_ireplace($search, $replacement, $subject)
            : str_replace($search, $replacement, $subject);
    }

    /**
     * Replaces the first occurrence of $search with $replacement in $subject.
     * Returns $subject unchanged when $search is empty or not found.
     */
    public static function replaceFirst(string $subject, string $search, string $replacement): string
    {
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
    public static function replaceLast(string $subject, string $search, string $replacement): string
    {
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
    public static function replaceAt(string $value, int $start, int $length, string $replacement): string
    {
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
     * @throws InvalidArgumentException when $from and $to differ in character length
     */
    public static function translate(string $value, string $from, string $to): string
    {
        $fromChars = mb_str_split($from);
        $toChars = mb_str_split($to);
        if (Arr::count($fromChars) !== Arr::count($toChars)) {
            throw new InvalidArgumentException('Translation strings must have the same length.');
        }
        if ($fromChars === []) {
            // @infection-ignore-all: falling through calls strtr with an empty map — an identity
            return $value;
        }

        return strtr($value, Arr::combine($fromChars, $toChars));
    }

    /**
     * Returns the multibyte-safe substring of $value starting at character
     * index $start. When $length is null, takes the rest of the string.
     */
    public static function sub(string $value, int $start, ?int $length = null): string
    {
        return mb_substr($value, $start, $length);
    }

    /**
     * Returns the 0-based character index of the first occurrence of $needle
     * in $haystack starting at $offset, or -1 when not found. Pass
     * $ignoreCase = true for a case-insensitive search.
     */
    public static function indexOf(string $haystack, string $needle, int $offset = 0, bool $ignoreCase = false): int
    {
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
    public static function lastIndexOf(string $haystack, string $needle, int $offset = 0, bool $ignoreCase = false): int
    {
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
    public static function count(string $haystack, string $needle): int
    {
        return $needle === '' ? 0 : substr_count($haystack, $needle);
    }

    /**
     * Returns the part of $subject before the first occurrence of $search.
     * Returns $subject unchanged when $search is empty or not found.
     */
    public static function before(string $subject, string $search): string
    {
        if ($search === '') {
            // @infection-ignore-all: falling through hits indexOf's empty-needle -1, whose branch also returns $subject
            return $subject;
        }
        $pos = self::indexOf($subject, $search);

        return $pos === -1 ? $subject : self::sub($subject, 0, $pos);
    }

    /**
     * Returns the part of $subject after the first occurrence of $search.
     * Returns $subject unchanged when $search is empty or not found.
     */
    public static function after(string $subject, string $search): string
    {
        if ($search === '') {
            // @infection-ignore-all: falling through hits indexOf's empty-needle -1, whose branch also returns $subject
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
     * @throws InvalidArgumentException when $length is negative
     */
    public static function trunc(string $value, int $length, string $ellipsis = '…'): string
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Length must be non-negative.');
        }
        if (mb_strlen($value) <= $length) {
            return $value;
        }
        $ellipsisLen = mb_strlen($ellipsis);
        if (/* @infection-ignore-all: at equality both branches yield the full ellipsis */ $length <= $ellipsisLen) {
            return mb_substr($ellipsis, 0, $length);
        }

        return mb_substr($value, 0, $length - $ellipsisLen) . $ellipsis;
    }

    /**
     * Returns a URL-friendly slug of $value: transliterates to ASCII
     * (best-effort via iconv), lowercases, and collapses runs of non-alphanumerics
     * into $separator.
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        // @infection-ignore-all: pre-lowering only changes the case iconv transliterates from; the divergent
        // outputs are exotic case-only translit entries, which vary by iconv implementation — not portably assertable
        $value = mb_strtolower($value);
        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }
        // @infection-ignore-all: defensive re-lower — after pre-lowering, only platform-dependent translit
        // entries can emit uppercase ASCII, so the difference is not portably assertable
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', $separator, $value) ?? '';

        return Str::trim($value, $separator);
    }

    /**
     * Masks part of $value for safe display of PII (card numbers, e-mails, …).
     * Within the window starting at character index $start and spanning $length
     * characters, every character not present in $keep is replaced by the first
     * character of $mask, preserving length; characters in $keep (e.g. formatting
     * separators) pass through unchanged. A negative $start counts from the end;
     * a null $length runs to the end; a negative $length leaves that many
     * characters untouched at the end. Multibyte-aware.
     *
     * Non-contiguous patterns (e.g. masking the 1st and 3rd groups but not the
     * 2nd) are produced by composing two calls.
     *
     * @throws InvalidArgumentException when $mask is empty
     */
    public static function mask(string $value, int $start = 0, ?int $length = null, string $mask = '*', string $keep = ''): string
    {
        if ($mask === '') {
            throw new InvalidArgumentException('Mask string cannot be empty.');
        }
        $total = mb_strlen($value);
        $from = $start < 0 ? max(0, $total + $start) : min($start, $total);
        $to = $length === null
            ? $total
            : ($length < 0 ? max($from, $total + $length) : min($total, $from + $length));
        // @infection-ignore-all: falling through with an empty window masks nothing and reassembles $value — same result
        if ($from >= $to) {
            return $value;
        }
        $maskChar = mb_substr($mask, 0, 1);
        $masked = '';
        foreach (mb_str_split(mb_substr($value, $from, $to - $from)) as $char) {
            $masked .= str_contains($keep, $char) ? $char : $maskChar;
        }

        return mb_substr($value, 0, $from) . $masked . mb_substr($value, $to);
    }

    /**
     * Splits the string on $separator (default `''`). An empty separator yields
     * individual characters, in which case $limit (if given) controls the chunk
     * size; otherwise $limit caps the number of pieces (the final piece keeps the
     * remainder).
     *
     * @return list<string>
     */
    public static function split(string $value, string $separator = '', ?int $limit = null): array
    {
        if ($separator === '') {
            return mb_str_split($value, max(1, /* @infection-ignore-all: with a null limit, max(1, 0) is still 1 */ $limit ?? 1));
        }

        return $limit === null ? explode($separator, $value) : explode($separator, $value, $limit);
    }

    /**
     * Joins iterable items into a string, casting each to string and placing
     * $separator between consecutive items — the iterable-accepting counterpart
     * of {@see implode()} ($separator defaults to `''`, concatenating). For
     * dropping blank items, wrapping the result with a prefix/suffix, or an
     * Oxford-style final separator, see {@see joinNatural()}.
     *
     * @param iterable<null|bool|float|int|string|Stringable> $items
     */
    public static function join(iterable $items, string $separator = ''): string
    {
        $parts = [];
        foreach ($items as $item) {
            // @infection-ignore-all: implode applies the identical string cast to each element
            $parts[] = (string) $item;
        }

        return implode($separator, $parts);
    }

    /**
     * Joins iterable items into a natural-language string: blank items are
     * dropped, $prefix / $suffix wrap a non-empty result, and $lastSeparator (if
     * given, with 2+ parts) joins the final two elements (e.g. ", " + " and " for
     * an Oxford-style join). Returns '' when no non-blank items remain.
     *
     * @param iterable<null|bool|float|int|string|Stringable> $items
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
     * Wraps a non-blank string with $prefix and $suffix; returns '' if the string is blank.
     */
    public static function wrap(string $value, string $prefix = '', string $suffix = ''): string
    {
        return self::isBlank($value) ? '' : $prefix . $value . $suffix;
    }

    /**
     * Left-pads the string with $pad up to $length characters (multibyte-aware).
     *
     * @throws InvalidArgumentException when $pad is empty
     */
    public static function padStart(string $value, int $length, string $pad = ' '): string
    {
        if ($pad === '') {
            throw new InvalidArgumentException('Pad string cannot be empty.');
        }

        return mb_str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * Right-pads the string with $pad up to $length characters (multibyte-aware).
     *
     * @throws InvalidArgumentException when $pad is empty
     */
    public static function padEnd(string $value, int $length, string $pad = ' '): string
    {
        if ($pad === '') {
            throw new InvalidArgumentException('Pad string cannot be empty.');
        }

        return mb_str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * Repeats the string $times times.
     *
     * @throws InvalidArgumentException when $times is negative
     */
    public static function repeat(string $value, int $times): string
    {
        if ($times < 0) {
            throw new InvalidArgumentException('Repeat count must be non-negative.');
        }

        return str_repeat($value, $times);
    }

    /**
     * Returns the string with its characters in reverse order (multibyte-aware).
     */
    public static function reverse(string $value): string
    {
        return implode('', Arr::reverse(mb_str_split($value)));
    }

    /**
     * Returns the Unicode code point of the first character of $value.
     *
     * @throws UnderflowException       when $value is empty
     * @throws InvalidArgumentException when $value is not valid UTF-8
     */
    public static function ord(string $value): int
    {
        if ($value === '') {
            throw new UnderflowException('Cannot take the code point of an empty string.');
        }
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException('Invalid UTF-8 sequence.');
        }

        return mb_ord($value);
    }

    /**
     * Returns the character for the given Unicode $codepoint (0 to 0x10FFFF).
     *
     * @throws InvalidArgumentException when $codepoint is outside the valid Unicode range
     */
    public static function chr(int $codepoint): string
    {
        if ($codepoint < 0 || $codepoint > 0x10FFFF) {
            throw new InvalidArgumentException("Invalid code point: {$codepoint}.");
        }

        return mb_chr($codepoint);
    }

    /**
     * Converts the string to camelCase (e.g. "hello world" → "helloWorld").
     */
    public static function toCamel(string $value): string
    {
        return self::uncapitalize(self::toPascal($value));
    }

    /**
     * Converts the string to PascalCase (e.g. "hello world" → "HelloWorld").
     */
    public static function toPascal(string $value): string
    {
        return implode('', Arr::map(self::splitWords($value), self::capitalize(...)));
    }

    /**
     * Converts the string to snake_case (e.g. "HelloWorld" → "hello_world").
     */
    public static function toSnake(string $value): string
    {
        return implode('_', Arr::map(self::splitWords($value), self::lower(...)));
    }

    /**
     * Converts the string to kebab-case (e.g. "HelloWorld" → "hello-world").
     */
    public static function toKebab(string $value): string
    {
        return implode('-', Arr::map(self::splitWords($value), self::lower(...)));
    }

    /**
     * Wraps $value so no line exceeds $width characters, breaking on spaces with
     * $break. With $cut = true, words longer than $width are split mid-word.
     * Byte-level (via {@see wordwrap()}); reliable for ASCII text.
     *
     * @throws InvalidArgumentException when $width is less than 1
     */
    public static function wordWrap(string $value, int $width = 75, string $break = "\n", bool $cut = false): string
    {
        if ($width < 1) {
            throw new InvalidArgumentException('Width must be at least 1.');
        }

        return wordwrap($value, $width, $break, $cut);
    }

    /**
     * Returns the number of words in $value — maximal runs of letters or digits,
     * Unicode-aware. Punctuation and whitespace separate words, so "it's" counts
     * as two.
     */
    public static function wordCount(string $value): int
    {
        $count = preg_match_all('/[\p{L}\p{N}]+/u', $value);

        return $count === false ? 0 : $count;
    }

    /**
     * Formats $template printf-style with the given $args, like
     * {@see vsprintf()}. E.g. `Str::format('%s is %d', 'x', 5)` → "x is 5".
     */
    public static function format(string $template, bool|float|int|string|null ...$args): string
    {
        return vsprintf($template, $args);
    }

    /**
     * Parses $value against the printf-style $format and returns the extracted
     * values — the inverse of {@see format()}, via {@see sscanf()}. Each
     * conversion that finds no match yields null in its slot.
     *
     * @return list<null|float|int|string>
     */
    public static function scan(string $value, string $format): array
    {
        /** @var null|int|list<null|float|int|string> $result */
        $result = sscanf($value, $format);

        return Arr::is($result) ? $result : [];
    }

    /**
     * Returns the Levenshtein edit distance between $a and $b — the minimum
     * number of single-character insertions, deletions, or substitutions to turn
     * one into the other. Byte-level (not multibyte-aware); returns -1 when
     * either string exceeds 255 bytes.
     */
    public static function levenshtein(string $a, string $b): int
    {
        return levenshtein($a, $b);
    }

    /**
     * Returns how similar $a and $b are as a percentage from 0.0 to 100.0
     * (via {@see similar_text()}). Byte-level and asymmetric — swapping the
     * arguments can yield a different result.
     */
    public static function similarity(string $a, string $b): float
    {
        // @infection-ignore-all: similar_text always overwrites the reference (0.0 even for two empty strings)
        $percentage = 0.0;
        similar_text($a, $b, $percentage);

        return $percentage;
    }

    /**
     * Splits the string into words on whitespace, dashes, underscores, and case
     * transitions (camelCase / PascalCase boundaries). Unicode-aware: handles
     * non-ASCII lowercase/uppercase via \p{Ll} / \p{Lu}.
     *
     * @return list<string>
     */
    private static function splitWords(string $value): array
    {
        $value = preg_replace('/(\p{Ll}|\p{Nd})(\p{Lu})/u', '$1 $2', $value) ?? $value;
        $value = preg_replace('/(\p{Lu}+)(\p{Lu}\p{Ll})/u', '$1 $2', $value) ?? $value;
        $parts = preg_split('/[\s_\-]+/u', $value) ?: [];

        return Arr::values(Arr::filter($parts, static fn (string $p): bool => $p !== ''));
    }
}
