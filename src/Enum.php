<?php

declare(strict_types=1);

namespace Rak200\Utils;

use BackedEnum;
use InvalidArgumentException;
use OutOfBoundsException;
use UnderflowException;
use UnitEnum;

/**
 * Class-level operations on enums. Complements the language: PHP ships
 * `cases()`, `from()`, `tryFrom()` on the enum itself; this class fills the
 * gaps — listing names/values, looking up cases by name (no native
 * `fromName()`) or loosely by backed value (the native `tryFrom()` is strictly
 * typed), random pick, and form-friendly `[name => value]` maps.
 *
 * The instance-side predicate (*is this value an enum case?*) lives at
 * {@see Type::isEnum()}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Enum
{
    private function __construct() {}

    /**
     * Returns true if $value is an enum case (instance of {@see UnitEnum}).
     * Domain predicate for {@see Enum}; {@see Type::isEnum()} is an alias.
     *
     * @phpstan-assert-if-true UnitEnum $value
     *
     * @phpstan-assert-if-false !UnitEnum $value
     */
    public static function is(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }

    /**
     * Returns true if $value is a backed enum case (instance of
     * {@see BackedEnum}). For the kind of backing, see {@see isBackedInt()}
     * and {@see isBackedStr()}.
     *
     * @phpstan-assert-if-true BackedEnum $value
     *
     * @phpstan-assert-if-false !BackedEnum $value
     */
    public static function isBacked(mixed $value): bool
    {
        return $value instanceof BackedEnum;
    }

    /**
     * Returns the names of every case in $enumClass, in declaration order.
     *
     * @param class-string<UnitEnum> $enumClass
     *
     * @return list<string>
     */
    public static function names(string $enumClass): array
    {
        $names = [];
        foreach ($enumClass::cases() as $case) {
            $names[] = $case->name;
        }

        return $names;
    }

    /**
     * Returns the backed values of every case in $enumClass, in declaration order.
     *
     * @param class-string<UnitEnum> $enumClass
     *
     * @return list<int|string>
     *
     * @throws InvalidArgumentException when $enumClass is not a backed enum
     */
    public static function values(string $enumClass): array
    {
        if (!Type::isSubclass($enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException("{$enumClass} is not a backed enum.");
        }
        $values = [];
        foreach ($enumClass::cases() as $case) {
            $values[] = $case->value;
        }

        return $values;
    }

    /**
     * Returns the case of $enumClass whose name is $name.
     *
     * @template T of UnitEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T
     *
     * @throws OutOfBoundsException when no case has that name
     */
    public static function fromName(string $enumClass, string $name): UnitEnum
    {
        $case = self::tryFromName($enumClass, $name);
        if ($case === null) {
            throw new OutOfBoundsException("{$enumClass} has no case named \"{$name}\".");
        }

        return $case;
    }

    /**
     * Returns the case of $enumClass whose name is $name, or null when no case
     * matches.
     *
     * @template T of UnitEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return null|T
     */
    public static function tryFromName(string $enumClass, string $name): ?UnitEnum
    {
        foreach ($enumClass::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Returns the case of $enumClass whose backed value is $value, coercing the
     * scalar to the backing type first — so `'2'` matches an int-backed case and
     * `2` a string-backed `'2'`, where the native `from()` is strictly typed and
     * rejects both. See {@see tryFromValue()} for the accepted scalars.
     *
     * @template T of UnitEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T
     *
     * @throws InvalidArgumentException when $enumClass is not a backed enum
     * @throws OutOfBoundsException     when no case carries that value
     */
    public static function fromValue(string $enumClass, mixed $value): UnitEnum
    {
        if (!Type::isSubclass($enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException("{$enumClass} is not a backed enum.");
        }
        $case = self::tryFromValue($enumClass, $value);
        if ($case === null) {
            $display = Filter::toStr($value) ?? Type::of($value);

            throw new OutOfBoundsException("{$enumClass} has no case with value \"{$display}\".");
        }

        return $case;
    }

    /**
     * Returns the case of $enumClass whose backed value is $value, or null when
     * none matches. The loose counterpart of the native `tryFrom()`: the scalar
     * is coerced to the enum's backing type first, so `'2'` matches an int-backed
     * case and `2` a string-backed `'2'`.
     *
     * Accepts an `int` or a `string` — for an int-backed enum a string is parsed
     * by {@see Num::parseIntOrNull()}, strictly (surrounding whitespace is
     * rejected, matching {@see Num::is()}). Any other type yields null; extract
     * the scalar (from a {@see \Stringable}, say) before calling.
     *
     * A total, throw-free read: a pure enum and an enum with no cases both yield
     * null rather than throwing, so a caller can fall back to
     * {@see tryFromName()} without a prior guard.
     *
     * @template T of UnitEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return null|T
     */
    public static function tryFromValue(string $enumClass, mixed $value): ?UnitEnum
    {
        if (!Type::isSubclass($enumClass, BackedEnum::class)) {
            return null;
        }
        $cases = $enumClass::cases();
        if ($cases === []) {
            return null;
        }
        $backing = self::isInt($cases[0])
            ? (Num::isInt($value) ? $value : (Type::isStr($value) ? Num::parseIntOrNull($value) : null))
            : (Num::isInt($value) || Type::isStr($value) ? (string) $value : null);

        return $backing === null ? null : $enumClass::tryFrom($backing);
    }

    /**
     * Returns a cryptographically-secure random case of $enumClass.
     *
     * @template T of UnitEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T
     *
     * @throws UnderflowException when $enumClass has no cases
     */
    public static function random(string $enumClass): UnitEnum
    {
        $cases = $enumClass::cases();
        if ($cases === []) {
            throw new UnderflowException("{$enumClass} has no cases.");
        }

        return Rand::choice($cases);
    }

    /**
     * Returns a `name => value` map for a backed enum, or a `name => name` map
     * for a pure enum. Useful for form/select option lists.
     *
     * @param class-string<UnitEnum> $enumClass
     *
     * @return array<string, int|string>
     */
    public static function toArray(string $enumClass): array
    {
        $map = [];
        foreach ($enumClass::cases() as $case) {
            $map[$case->name] = self::scalar($case);
        }

        return $map;
    }

    /**
     * Returns a single scalar representation of $case — the backed value for a
     * backed enum case, or the name for a pure enum case.
     */
    public static function scalar(UnitEnum $case): int|string
    {
        return self::isBacked($case) ? $case->value : $case->name;
    }

    /**
     * Returns true when $case is an int-backed enum case.
     *
     * @phpstan-assert-if-true BackedEnum $case
     */
    public static function isBackedInt(UnitEnum $case): bool
    {
        return self::isBacked($case) && Num::isInt($case->value);
    }

    /**
     * Returns true when $case is a string-backed enum case.
     *
     * @phpstan-assert-if-true BackedEnum $case
     */
    public static function isBackedStr(UnitEnum $case): bool
    {
        return self::isBacked($case) && Type::isStr($case->value);
    }

    /**
     * Returns true when the backed case carries an int value. Unlike
     * {@see isBackedInt()}, the input is already known to be backed, which
     * lets the analyzer narrow $case->value exactly: int in the true branch,
     * string in the false branch.
     *
     * @phpstan-assert-if-true int $case->value
     *
     * @phpstan-assert-if-false string $case->value
     */
    public static function isInt(BackedEnum $case): bool
    {
        return Num::isInt($case->value);
    }

    /**
     * Returns true when the backed case carries a string value. Unlike
     * {@see isBackedStr()}, the input is already known to be backed, which
     * lets the analyzer narrow $case->value exactly: string in the true
     * branch, int in the false branch.
     *
     * @phpstan-assert-if-true string $case->value
     *
     * @phpstan-assert-if-false int $case->value
     */
    public static function isStr(BackedEnum $case): bool
    {
        return Type::isStr($case->value);
    }

    /**
     * Returns the int backing of $case, or null when $case is not int-backed
     * (a pure enum case, or a string-backed one). The value-extracting
     * complement to {@see isInt()}: a total, throw-free read that takes the
     * broad {@see UnitEnum} — so a pure or wrong-typed case collapses to null
     * with no prior {@see isBacked()} guard, where {@see isInt()} only narrows.
     */
    public static function intOrNull(UnitEnum $case): ?int
    {
        return self::isBacked($case) && Num::isInt($case->value) ? $case->value : null;
    }

    /**
     * Returns the string backing of $case, or null when $case is not
     * string-backed (a pure enum case, or an int-backed one). The
     * value-extracting complement to {@see isStr()}: a total, throw-free read
     * that takes the broad {@see UnitEnum} — so a pure or wrong-typed case
     * collapses to null with no prior {@see isBacked()} guard, where
     * {@see isStr()} only narrows.
     */
    public static function strOrNull(UnitEnum $case): ?string
    {
        return self::isBacked($case) && Type::isStr($case->value) ? $case->value : null;
    }
}
