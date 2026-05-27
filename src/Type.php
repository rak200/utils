<?php

declare(strict_types=1);

namespace Rak200\Utils;

use BcMath\Number;
use function class_exists, interface_exists, is_a, is_array, is_bool, is_callable,
    is_float, is_int, is_iterable, is_numeric, is_object, is_resource, is_scalar,
    is_string, preg_match;

/**
 * Type-checking predicates. Every method accepts `mixed` so it can be used as
 * a type guard on values whose shape is not yet known.
 *
 * Topic-specific predicates live with their tier-1 class:
 * - String shape: {@see Str::isBlank()}, {@see Str::isNonEmptyStr()}.
 * - Array shape: {@see Arr::isList()}, {@see Arr::isAssoc()}, {@see Arr::isNonEmptyArray()}.
 * - Integer sign: {@see Num::isPositiveInt()}, {@see Num::isNegativeInt()}, {@see Num::isNonNegativeInt()}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Type {
    private function __construct() {}

    /**
     * Returns true if $value is a string.
     */
    public static function isStr(mixed $value): bool {
        return is_string($value);
    }

    /**
     * Returns true if $value is a bool.
     */
    public static function isBool(mixed $value): bool {
        return is_bool($value);
    }

    /**
     * Returns true if $value is an int.
     */
    public static function isInt(mixed $value): bool {
        return is_int($value);
    }

    /**
     * Returns true if $value is a float.
     */
    public static function isFloat(mixed $value): bool {
        return is_float($value);
    }

    /**
     * Returns true if $value is an array (list or associative).
     */
    public static function isArray(mixed $value): bool {
        return is_array($value);
    }

    /**
     * Returns true if $value is an object.
     */
    public static function isObject(mixed $value): bool {
        return is_object($value);
    }

    /**
     * Returns true if $value is callable (closure, function name string,
     * `[$obj, 'method']`, invokable object, etc.).
     */
    public static function isCallable(mixed $value): bool {
        return is_callable($value);
    }

    /**
     * Returns true if $value is iterable (array or {@see \Traversable}).
     */
    public static function isIterable(mixed $value): bool {
        return is_iterable($value);
    }

    /**
     * Returns true if $value is null.
     */
    public static function isNull(mixed $value): bool {
        return $value === null;
    }

    /**
     * Returns true if $value is a scalar (int, float, string, or bool).
     */
    public static function isScalar(mixed $value): bool {
        return is_scalar($value);
    }

    /**
     * Returns true if $value is an int, a float, a numeric string, or a
     * {@see Number} instance.
     */
    public static function isNumeric(mixed $value): bool {
        return is_int($value)
            || is_float($value)
            || (is_string($value) && is_numeric($value))
            || $value instanceof Number;
    }

    /**
     * Returns true if $value is an open resource.
     */
    public static function isResource(mixed $value): bool {
        return is_resource($value);
    }

    /**
     * Returns true if $value is a string in numeric format (delegates to
     * PHP's {@see is_numeric()}, which accepts decimals, leading sign,
     * exponent notation, and surrounding whitespace).
     */
    public static function isNumericStr(mixed $value): bool {
        return is_string($value) && is_numeric($value);
    }

    /**
     * Returns true if $value is an int, or a string that parses cleanly as
     * an integer (optional leading sign, digits only — no decimal point,
     * exponent, or surrounding whitespace).
     */
    public static function isIntLike(mixed $value): bool {
        if (is_int($value)) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        return preg_match('/^[+-]?\d+$/', $value) === 1;
    }

    /**
     * Returns true if $value is an object that is an instance of $class
     * (directly, as a subclass, or implementing it when $class is an
     * interface). Class-name strings are not accepted — use {@see isA()}.
     */
    public static function isInstanceOf(mixed $value, string $class): bool {
        return $value instanceof $class;
    }

    /**
     * Returns true if $value is a string naming an existing class
     * (autoload-aware).
     */
    public static function isClassName(mixed $value): bool {
        return is_string($value) && class_exists($value);
    }

    /**
     * Returns true if $value is a string naming an existing interface
     * (autoload-aware).
     */
    public static function isInterfaceName(mixed $value): bool {
        return is_string($value) && interface_exists($value);
    }

    /**
     * Shortcut for {@see is_a()} with `$allow_string = true`: returns true
     * when $value is either an object that is an instance of $class, or a
     * string naming a class that is or extends/implements $class.
     */
    public static function isA(mixed $value, string $class): bool {
        if (!is_object($value) && !is_string($value)) {
            return false;
        }
        return is_a($value, $class, true);
    }
}
