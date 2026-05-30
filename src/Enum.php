<?php

declare(strict_types=1);

namespace Rak200\Utils;

use BackedEnum;
use RuntimeException;
use UnitEnum;

/**
 * Class-level operations on enums. Complements the language: PHP ships
 * `cases()`, `from()`, `tryFrom()` on the enum itself; this class fills the
 * gaps — listing names/values, looking up cases by name (no native
 * `fromName()`), random pick, and form-friendly `[name => value]` maps.
 *
 * The instance-side predicate (*is this value an enum case?*) lives at
 * {@see Type::isEnum()}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Enum {
    private function __construct() {}

    /**
     * Returns true if $value is an enum case (instance of {@see UnitEnum}).
     * Domain predicate for {@see Enum}; {@see Type::isEnum()} is an alias.
     *
     * @phpstan-assert-if-true UnitEnum $value
     * @phpstan-assert-if-false !UnitEnum $value
     */
    public static function is(mixed $value): bool {
        return $value instanceof UnitEnum;
    }

    /**
     * Returns true if $value is a backed enum case (instance of
     * {@see BackedEnum}). For the kind of backing, see {@see isBackedInt()}
     * and {@see isBackedStr()}.
     *
     * @phpstan-assert-if-true BackedEnum $value
     * @phpstan-assert-if-false !BackedEnum $value
     */
    public static function isBacked(mixed $value): bool {
        return $value instanceof BackedEnum;
    }

    /**
     * Returns the names of every case in $enumClass, in declaration order.
     *
     * @param class-string<UnitEnum> $enumClass
     * @return list<string>
     */
    public static function names(string $enumClass): array {
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
     * @return list<int|string>
     * @throws RuntimeException When $enumClass is not a backed enum.
     */
    public static function values(string $enumClass): array {
        if (!Type::isSubclassOf($enumClass, BackedEnum::class)) {
            throw new RuntimeException("$enumClass is not a backed enum.");
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
     * @param class-string<T> $enumClass
     * @return T
     * @throws RuntimeException When no case has that name.
     */
    public static function fromName(string $enumClass, string $name): UnitEnum {
        $case = self::tryFromName($enumClass, $name);
        if ($case === null) {
            throw new RuntimeException("$enumClass has no case named \"$name\".");
        }
        return $case;
    }

    /**
     * Returns the case of $enumClass whose name is $name, or null when no case
     * matches.
     *
     * @template T of UnitEnum
     * @param class-string<T> $enumClass
     * @return T|null
     */
    public static function tryFromName(string $enumClass, string $name): ?UnitEnum {
        foreach ($enumClass::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Returns a cryptographically-secure random case of $enumClass.
     *
     * @template T of UnitEnum
     * @param class-string<T> $enumClass
     * @return T
     * @throws RuntimeException When $enumClass has no cases.
     */
    public static function random(string $enumClass): UnitEnum {
        $cases = $enumClass::cases();
        if ($cases === []) {
            throw new RuntimeException("$enumClass has no cases.");
        }
        return Rand::choice($cases);
    }

    /**
     * Returns a `name => value` map for a backed enum, or a `name => name` map
     * for a pure enum. Useful for form/select option lists.
     *
     * @param class-string<UnitEnum> $enumClass
     * @return array<string, int|string>
     */
    public static function toArray(string $enumClass): array {
        $map = [];
        foreach ($enumClass::cases() as $case) {
            $map[$case->name] = static::scalar($case);
        }
        return $map;
    }

    /**
     * Returns a single scalar representation of $case — the backed value for a
     * backed enum case, or the name for a pure enum case.
     */
    public static function scalar(UnitEnum $case): int|string {
        return static::isBacked($case) ? $case->value : $case->name;
    }

    /**
     * Returns true when $case is an int-backed enum case.
     *
     * @phpstan-assert-if-true BackedEnum $case
     */
    public static function isBackedInt(UnitEnum $case): bool {
        return static::isBacked($case) && Num::isInt($case->value);
    }

    /**
     * Returns true when $case is a string-backed enum case.
     *
     * @phpstan-assert-if-true BackedEnum $case
     */
    public static function isBackedStr(UnitEnum $case): bool {
        return static::isBacked($case) && Type::isStr($case->value);
    }
}
