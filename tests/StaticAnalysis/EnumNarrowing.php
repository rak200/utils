<?php

declare(strict_types=1);

/*
 * PHPStan-only narrowing fixtures — see the header of ArrNarrowing.php for how
 * these files are analysed but never executed.
 */

namespace Rak200\Utils\Tests\StaticAnalysis;

use BackedEnum;
use Rak200\Utils\Enum;
use UnitEnum;

use function PHPStan\Testing\assertType;

/*
 * `Enum::isBackedInt` / `isBackedStr` narrow a `UnitEnum` to `BackedEnum` in the
 * true branch (`@phpstan-assert-if-true BackedEnum $case`), which is what makes
 * `$case->value` readable there — as `int|string`, `BackedEnum::$value`'s own
 * type. These two do NOT narrow `$case->value` to `int` / `string`: doing so
 * needs `@phpstan-assert-if-true int $case->value`, which PHPStan rejects on
 * the declaration itself (`assert.unknownExpr`) because the `UnitEnum` parameter
 * has no `value` property — only `BackedEnum` does. The exact narrowing lives on
 * `Enum::isInt` / `isStr` instead, whose `BackedEnum` parameter makes the
 * property assert well-formed, in both branches (a backed value that is not int
 * is string, and vice versa).
 */

function enumIsBackedIntNarrowsUnitToBacked(UnitEnum $case): void
{
    if (Enum::isBackedInt($case)) {
        assertType('BackedEnum', $case);
        assertType('int|string', $case->value);
    }
}

function enumIsBackedStrNarrowsUnitToBacked(UnitEnum $case): void
{
    if (Enum::isBackedStr($case)) {
        assertType('BackedEnum', $case);
        assertType('int|string', $case->value);
    }
}

function enumIsIntNarrowsValueBothBranches(BackedEnum $case): void
{
    if (Enum::isInt($case)) {
        assertType('int', $case->value);
    } else {
        assertType('string', $case->value);
    }
}

function enumIsStrNarrowsValueBothBranches(BackedEnum $case): void
{
    if (Enum::isStr($case)) {
        assertType('string', $case->value);
    } else {
        assertType('int', $case->value);
    }
}
