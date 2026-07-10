<?php

declare(strict_types=1);

namespace Rak200\Utils;

use BcMath\Number;
use UnitEnum;

use function class_exists;
use function class_parents;
use function class_uses;
use function get_debug_type;
use function interface_exists;
use function is_a;
use function is_bool;
use function is_callable;
use function is_iterable;
use function is_object;
use function is_resource;
use function is_scalar;
use function is_string;
use function is_subclass_of;
use function trait_exists;

/**
 * Type-checking predicates. Every method accepts `mixed` so it can be used as
 * a type guard on values whose shape is not yet known.
 *
 * Topic-specific predicates live with their tier-1 class:
 * - String shape: {@see Str::isBlank()}, {@see Str::isNotBlank()}.
 * - Array shape: {@see Arr::isList()}, {@see Arr::isAssoc()}.
 * - Numeric sign: {@see Num::isPositive()}, {@see Num::isNegative()}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Type
{
    private function __construct() {}

    /**
     * Returns the resolved type name of $value (delegates to
     * {@see get_debug_type()}): one of `int`, `float`, `string`, `bool`,
     * `null`, `array`, `resource (closed)`, the leaf `resource (type)`
     * label for an open resource, or — for objects — the fully-qualified
     * class name (`Closure` and `stdClass` for anonymous values).
     */
    public static function of(mixed $value): string
    {
        return get_debug_type($value);
    }

    /**
     * Alias of {@see Str::is()}.
     *
     * @phpstan-assert-if-true string $value
     *
     * @phpstan-assert-if-false !string $value
     */
    public static function isStr(mixed $value): bool
    {
        return Str::is($value);
    }

    /**
     * Returns true if $value is a bool.
     *
     * @phpstan-assert-if-true bool $value
     *
     * @phpstan-assert-if-false !bool $value
     */
    public static function isBool(mixed $value): bool
    {
        return is_bool($value);
    }

    /**
     * Alias of {@see Num::isInt()}.
     *
     * @phpstan-assert-if-true int $value
     *
     * @phpstan-assert-if-false !int $value
     */
    public static function isInt(mixed $value): bool
    {
        return Num::isInt($value);
    }

    /**
     * Alias of {@see Num::isFloat()}.
     *
     * @phpstan-assert-if-true float $value
     *
     * @phpstan-assert-if-false !float $value
     */
    public static function isFloat(mixed $value): bool
    {
        return Num::isFloat($value);
    }

    /**
     * Alias of {@see Arr::is()}.
     *
     * @phpstan-assert-if-true array<mixed> $value
     *
     * @phpstan-assert-if-false !array<mixed> $value
     */
    public static function isArray(mixed $value): bool
    {
        return Arr::is($value);
    }

    /**
     * Returns true if $value is an object.
     *
     * @phpstan-assert-if-true object $value
     *
     * @phpstan-assert-if-false !object $value
     */
    public static function isObject(mixed $value): bool
    {
        return is_object($value);
    }

    /**
     * Alias of {@see Enum::is()}. Enum class-name strings are not accepted —
     * use {@see isA()} with {@see UnitEnum} for that.
     *
     * @phpstan-assert-if-true UnitEnum $value
     *
     * @phpstan-assert-if-false !UnitEnum $value
     */
    public static function isEnum(mixed $value): bool
    {
        return Enum::is($value);
    }

    /**
     * Returns true if $value is callable (closure, function name string,
     * `[$obj, 'method']`, invokable object, etc.).
     *
     * @phpstan-assert-if-true callable $value
     */
    public static function isCallable(mixed $value): bool
    {
        return is_callable($value);
    }

    /**
     * Returns true if $value is iterable (array or {@see \Traversable}).
     *
     * @phpstan-assert-if-true iterable<mixed> $value
     *
     * @phpstan-assert-if-false !iterable<mixed> $value
     */
    public static function isIterable(mixed $value): bool
    {
        return is_iterable($value);
    }

    /**
     * Returns true if $value is null.
     *
     * @phpstan-assert-if-true null $value
     *
     * @phpstan-assert-if-false !null $value
     */
    public static function isNull(mixed $value): bool
    {
        return $value === null;
    }

    /**
     * Returns true if $value is a scalar (int, float, string, or bool).
     *
     * @phpstan-assert-if-true scalar $value
     *
     * @phpstan-assert-if-false !scalar $value
     */
    public static function isScalar(mixed $value): bool
    {
        return is_scalar($value);
    }

    /**
     * Alias of {@see Num::is()}.
     *
     * @phpstan-assert-if-true int|float|numeric-string|Number $value
     */
    public static function isNumeric(mixed $value): bool
    {
        return Num::is($value);
    }

    /**
     * Returns true if $value is an open resource.
     *
     * @phpstan-assert-if-true resource $value
     */
    public static function isResource(mixed $value): bool
    {
        return is_resource($value);
    }

    /**
     * Returns true if $value is an object that is an instance of $class
     * (directly, as a subclass, or implementing it when $class is an
     * interface). Class-name strings are not accepted — use {@see isA()}.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @phpstan-assert-if-true T $value
     */
    public static function isInstance(mixed $value, string $class): bool
    {
        return $value instanceof $class;
    }

    /**
     * Returns true if $value is a string naming an existing class
     * (autoload-aware).
     *
     * @phpstan-assert-if-true class-string $value
     */
    public static function isClassName(mixed $value): bool
    {
        return is_string($value) && class_exists($value);
    }

    /**
     * Returns true if $value is a string naming an existing interface
     * (autoload-aware).
     *
     * @phpstan-assert-if-true class-string $value
     */
    public static function isInterfaceName(mixed $value): bool
    {
        return is_string($value) && interface_exists($value);
    }

    /**
     * Shortcut for {@see is_a()} with `$allow_string = true`: returns true
     * when $value is either an object that is an instance of $class, or a
     * string naming a class that is or extends/implements $class.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @phpstan-assert-if-true T|class-string<T> $value
     */
    public static function isA(mixed $value, string $class): bool
    {
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
     *
     * @param class-string<T> $class
     *
     * @phpstan-assert-if-true T|class-string<T> $value
     */
    public static function isSubclass(mixed $value, string $class): bool
    {
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
    public static function usesTrait(mixed $value, string $trait, bool $recursive = false): bool
    {
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
    private static function allTraits(object|string $value): array
    {
        $name = is_object($value) ? $value::class : $value;
        $classes = [$name => $name] + (class_parents($value) ?: []);
        $traits = [];
        foreach ($classes as $class) {
            $traits += self::traitUses($class);
        }

        return $traits;
    }

    /**
     * Returns the traits $class uses directly, plus — transitively — the traits
     * those traits themselves use.
     *
     * @return array<string, string> trait names keyed by themselves
     */
    private static function traitUses(string $class): array
    {
        $traits = class_uses($class) ?: [];
        foreach ($traits as $used) {
            $traits += self::traitUses($used);
        }

        return $traits;
    }
}
