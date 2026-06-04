<?php

declare(strict_types=1);

namespace Rak200\Utils;

use Stringable;

use function filter_var;
use function function_exists;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function iconv;
use function preg_replace;
use function strip_tags;

/**
 * Input sanitisation and lenient coercion of untrusted values.
 *
 * Two groups of helpers, both total — no method throws:
 * - **Sanitisers** are `string → string` transforms: HTML escaping, tag
 *   stripping, character whitelists, whitespace/control-char cleanup, ASCII
 *   transliteration.
 * - **Coercers** (`to*`) turn a `mixed` value into a typed result, returning the
 *   caller-supplied `$default` when the value cannot be represented. Built for
 *   request data, where every value arrives as an untrusted string and a
 *   fallback is wanted rather than an exception.
 *
 * The coercers differ from {@see Num} parsing on purpose: {@see Num::parseInt()}
 * and friends are strict `string` parsers (no surrounding whitespace; throw or
 * return null), whereas {@see Filter::toInt()} and friends are lenient `mixed`
 * coercers that trim and fall back to a default.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Filter
{
    private function __construct() {}

    /**
     * Escapes $value for safe interpolation into HTML text or attributes:
     * converts `& < > " '` to their entities ({@see htmlspecialchars} with
     * `ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8). Pass $doubleEncode = false to leave
     * existing entities (e.g. `&amp;`) untouched.
     */
    public static function escapeHtml(string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    /**
     * Reverses {@see escapeHtml()}: turns HTML entities back into the literal
     * `& < > " '` characters.
     */
    public static function unescapeHtml(string $value): string
    {
        return htmlspecialchars_decode($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }

    /**
     * Strips HTML and PHP tags from $value. $allowedTags is a list of tags to
     * keep, in the legacy string form (e.g. `'<p><a>'`).
     */
    public static function stripTags(string $value, string $allowedTags = ''): string
    {
        return strip_tags($value, $allowedTags);
    }

    /**
     * Removes every character of $value that is not an ASCII digit (`0-9`).
     */
    public static function digits(string $value): string
    {
        return preg_replace('/[^0-9]+/', '', $value) ?? '';
    }

    /**
     * Removes every character of $value that is not a Unicode letter (`\p{L}`).
     */
    public static function alpha(string $value): string
    {
        return preg_replace('/[^\p{L}]+/u', '', $value) ?? '';
    }

    /**
     * Removes every character of $value that is not a Unicode letter (`\p{L}`)
     * or number (`\p{N}`).
     */
    public static function alnum(string $value): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    }

    /**
     * Collapses every run of whitespace in $value into a single space and trims
     * the ends.
     */
    public static function squish(string $value): string
    {
        return Str::trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /**
     * Removes ASCII and Unicode control characters (`\p{Cc}` — the C0/C1
     * ranges, including null bytes, escapes, tabs, and newlines) from $value.
     */
    public static function stripControl(string $value): string
    {
        return preg_replace('/\p{Cc}/u', '', $value) ?? '';
    }

    /**
     * Transliterates $value to ASCII on a best-effort basis (via {@see iconv}'s
     * `ASCII//TRANSLIT//IGNORE`): accented letters become their plain forms and
     * untranslatable characters are dropped. Returns $value unchanged when the
     * iconv extension is unavailable or transliteration fails.
     */
    public static function ascii(string $value): string
    {
        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                return $transliterated;
            }
        }

        return $value;
    }

    /**
     * Removes characters not allowed in an e-mail address
     * ({@see FILTER_SANITIZE_EMAIL}). Sanitisation only — it does not validate;
     * use {@see Type} guards or PHP's validation filters for that.
     */
    public static function email(string $value): string
    {
        return (string) filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Removes characters not allowed in a URL ({@see FILTER_SANITIZE_URL}).
     * Sanitisation only — it does not validate; see {@see Url::is()} for that.
     */
    public static function url(string $value): string
    {
        return (string) filter_var($value, FILTER_SANITIZE_URL);
    }

    /**
     * Coerces $value to a string: strings pass through; int/float/bool and
     * {@see Stringable} objects are cast; null, arrays, and non-stringable
     * objects yield $default. Note the native bool cast — `true → "1"`,
     * `false → ""`.
     */
    public static function toStr(mixed $value, ?string $default = null): ?string
    {
        if (Str::is($value)) {
            return $value;
        }
        if (Num::isInt($value) || Num::isFloat($value) || Type::isBool($value) || Type::isA($value, Stringable::class)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Coerces $value to an int: ints pass through; strings are trimmed and
     * parsed (digits with an optional sign, via {@see Num::parseIntOrNull()});
     * a float with an integral value is cast. Anything else — including
     * non-integral floats, bools, and numeric strings with a decimal point —
     * yields $default.
     */
    public static function toInt(mixed $value, ?int $default = null): ?int
    {
        if (Num::isInt($value)) {
            return $value;
        }
        if (Str::is($value)) {
            return Num::parseIntOrNull(Str::trim($value)) ?? $default;
        }
        if (Num::isFloat($value) && Num::isFinite($value) && Num::floor($value) === $value) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * Coerces $value to a float: ints and floats are cast; strings are trimmed
     * and parsed (via {@see Num::parseFloatOrNull()}). Anything else yields
     * $default.
     */
    public static function toFloat(mixed $value, ?float $default = null): ?float
    {
        if (Num::isInt($value) || Num::isFloat($value)) {
            return (float) $value;
        }
        if (Str::is($value)) {
            return Num::parseFloatOrNull(Str::trim($value)) ?? $default;
        }

        return $default;
    }

    /**
     * Coerces $value to a bool with HTML-form semantics: bools pass through;
     * `1`/`0` map to true/false; strings (case-insensitive, trimmed) `"1"`,
     * `"true"`, `"on"`, `"yes"` are true and `"0"`, `"false"`, `"off"`, `"no"`,
     * `""` are false. Anything else yields $default.
     */
    public static function toBool(mixed $value, ?bool $default = null): ?bool
    {
        if (Type::isBool($value)) {
            return $value;
        }
        if (Num::isInt($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => $default,
            };
        }
        if (Str::is($value)) {
            return match (Str::lower(Str::trim($value))) {
                '1', 'true', 'on', 'yes' => true,
                '0', 'false', 'off', 'no', '' => false,
                default => $default,
            };
        }

        return $default;
    }
}
