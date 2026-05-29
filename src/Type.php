<?php

declare(strict_types=1);

namespace Rak200\Utils;

use BcMath\Number;
use UnitEnum;
use function class_exists, class_parents, class_uses, get_debug_type, interface_exists,
    is_a, is_array, is_bool, is_callable, is_float, is_int, is_iterable, is_numeric,
    is_object, is_resource, is_scalar, is_string, is_subclass_of, preg_match, trait_exists;

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
     * Returns the resolved type name of $value (delegates to
     * {@see get_debug_type()}): one of `int`, `float`, `string`, `bool`,
     * `null`, `array`, `resource (closed)`, the leaf `resource (type)`
     * label for an open resource, or — for objects — the fully-qualified
     * class name (`Closure` and `stdClass` for anonymous values).
     */
    public static function of(mixed $value): string {
        return get_debug_type($value);
    }

    /**
     * Returns true if $value is a string.
     *
     * @phpstan-assert-if-true string $value
     * @phpstan-assert-if-false !string $value
     */
    public static function isStr(mixed $value): bool {
        return is_string($value);
    }

    /**
     * Returns true if $value is a bool.
     *
     * @phpstan-assert-if-true bool $value
     * @phpstan-assert-if-false !bool $value
     */
    public static function isBool(mixed $value): bool {
        return is_bool($value);
    }

    /**
     * Returns true if $value is an int.
     *
     * @phpstan-assert-if-true int $value
     * @phpstan-assert-if-false !int $value
     */
    public static function isInt(mixed $value): bool {
        return is_int($value);
    }

    /**
     * Returns true if $value is a float.
     *
     * @phpstan-assert-if-true float $value
     * @phpstan-assert-if-false !float $value
     */
    public static function isFloat(mixed $value): bool {
        return is_float($value);
    }

    /**
     * Returns true if $value is an array (list or associative).
     *
     * @phpstan-assert-if-true array<mixed> $value
     * @phpstan-assert-if-false !array<mixed> $value
     */
    public static function isArray(mixed $value): bool {
        return is_array($value);
    }

    /**
     * Returns true if $value is an object.
     *
     * @phpstan-assert-if-true object $value
     * @phpstan-assert-if-false !object $value
     */
    public static function isObject(mixed $value): bool {
        return is_object($value);
    }

    /**
     * Returns true if $value is an enum case — an instance of
     * {@see \UnitEnum}, which every pure and backed enum implements.
     * Enum class-name strings are not accepted — use {@see isA()} with
     * {@see \UnitEnum} for that.
     *
     * @phpstan-assert-if-true UnitEnum $value
     * @phpstan-assert-if-false !UnitEnum $value
     */
    public static function isEnum(mixed $value): bool {
        return $value instanceof UnitEnum;
    }

    /**
     * Returns true if $value is callable (closure, function name string,
     * `[$obj, 'method']`, invokable object, etc.).
     *
     * @phpstan-assert-if-true callable $value
     */
    public static function isCallable(mixed $value): bool {
        return is_callable($value);
    }

    /**
     * Returns true if $value is iterable (array or {@see \Traversable}).
     *
     * @phpstan-assert-if-true iterable<mixed> $value
     * @phpstan-assert-if-false !iterable<mixed> $value
     */
    public static function isIterable(mixed $value): bool {
        return is_iterable($value);
    }

    /**
     * Returns true if $value is null.
     *
     * @phpstan-assert-if-true null $value
     * @phpstan-assert-if-false !null $value
     */
    public static function isNull(mixed $value): bool {
        return $value === null;
    }

    /**
     * Returns true if $value is a scalar (int, float, string, or bool).
     *
     * @phpstan-assert-if-true scalar $value
     * @phpstan-assert-if-false !scalar $value
     */
    public static function isScalar(mixed $value): bool {
        return is_scalar($value);
    }

    /**
     * Returns true if $value is an int, a float, a numeric string (with no
     * surrounding whitespace), or a {@see Number} instance.
     *
     * @phpstan-assert-if-true int|float|numeric-string|Number $value
     */
    public static function isNumeric(mixed $value): bool {
        return is_int($value)
            || is_float($value)
            || (is_string($value) && is_numeric($value)
                && !Str::isWhitespace($value[0]) && !Str::isWhitespace($value[-1]))
            || $value instanceof Number;
    }

    /**
     * Returns true if $value is an open resource.
     *
     * @phpstan-assert-if-true resource $value
     */
    public static function isResource(mixed $value): bool {
        return is_resource($value);
    }

    /**
     * Returns true if $value is a string in numeric format (decimals, leading
     * sign, exponent notation; no surrounding whitespace).
     *
     * @phpstan-assert-if-true numeric-string $value
     */
    public static function isNumericStr(mixed $value): bool {
        return is_string($value) && is_numeric($value)
            && !Str::isWhitespace($value[0]) && !Str::isWhitespace($value[-1]);
    }

    /**
     * Returns true if $value is an int, or a string that parses cleanly as
     * an integer (optional leading sign, digits only — no decimal point,
     * exponent, or surrounding whitespace).
     *
     * @phpstan-assert-if-true int|numeric-string $value
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
     *
     * @template T of object
     * @param class-string<T> $class
     * @phpstan-assert-if-true T $value
     */
    public static function isInstanceOf(mixed $value, string $class): bool {
        return $value instanceof $class;
    }

    /**
     * Returns true if $value is a string naming an existing class
     * (autoload-aware).
     *
     * @phpstan-assert-if-true class-string $value
     */
    public static function isClassName(mixed $value): bool {
        return is_string($value) && class_exists($value);
    }

    /**
     * Returns true if $value is a string naming an existing interface
     * (autoload-aware).
     *
     * @phpstan-assert-if-true class-string $value
     */
    public static function isInterfaceName(mixed $value): bool {
        return is_string($value) && interface_exists($value);
    }

    /**
     * Shortcut for {@see is_a()} with `$allow_string = true`: returns true
     * when $value is either an object that is an instance of $class, or a
     * string naming a class that is or extends/implements $class.
     *
     * @template T of object
     * @param class-string<T> $class
     * @phpstan-assert-if-true T|class-string<T> $value
     */
    public static function isA(mixed $value, string $class): bool {
        if (!is_object($value) && !is_string($value)) {
            return false;
        }
        return is_a($value, $class, true);
    }

    /**
     * Returns true if $value is an object or class-name string that is a
     * proper subclass of $class — it extends $class, or implements it when
     * $class is an interface. Unlike {@see isA()}, the exact class itself
     * returns false (a class is not its own subclass). Shortcut for
     * {@see is_subclass_of()} with `$allow_string = true`.
     *
     * @template T of object
     * @param class-string<T> $class
     * @phpstan-assert-if-true T|class-string<T> $value
     */
    public static function isSubclassOf(mixed $value, string $class): bool {
        if (!is_object($value) && !is_string($value)) {
            return false;
        }
        return is_subclass_of($value, $class, true);
    }

    /**
     * Returns true if $value (an object or class-name string) uses $trait.
     * By default only traits applied directly on the class count; pass
     * $recursive = true to also match traits inherited from parent classes
     * and traits used by other traits (nested). A string naming no existing
     * class or trait yields false.
     */
    public static function usesTrait(mixed $value, string $trait, bool $recursive = false): bool {
        if (is_string($value)) {
            if (!class_exists($value) && !trait_exists($value)) {
                return false;
            }
        } elseif (!is_object($value)) {
            return false;
        }
        $traits = $recursive ? self::allTraits($value) : (class_uses($value) ?: []);
        return isset($traits[$trait]);
    }

    /**
     * Collects every trait reachable from $value: those used by the class
     * itself, by each of its parent classes, and — transitively — by those
     * traits.
     *
     * @return array<string, string> trait names keyed by themselves
     */
    private static function allTraits(object|string $value): array {
        $name = is_object($value) ? $value::class : $value;
        $classes = [$name => $name] + (class_parents($value) ?: []);
        $traits = [];
        foreach ($classes as $class) {
            $traits += self::traitUses($class);
        }
        return $traits;
    }

    /**
     * @return array<string, string> $class's direct traits plus the traits
     *                               they themselves use, transitively
     */
    private static function traitUses(string $class): array {
        $traits = class_uses($class) ?: [];
        foreach ($traits as $used) {
            $traits += self::traitUses($used);
        }
        return $traits;
    }
}
