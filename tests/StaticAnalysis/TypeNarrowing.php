<?php

declare(strict_types=1);

/*
 * PHPStan-only narrowing fixtures — see the header of ArrNarrowing.php for how
 * these files are analysed but never executed.
 */

namespace Rak200\Utils\Tests\StaticAnalysis;

use Rak200\Utils\Type;

use function PHPStan\Testing\assertType;

/*
 * Resolving an *unvalidated* class name — one that arrives as a runtime string
 * from configuration, a constructor argument or a type discriminator, so the
 * caller cannot annotate it `class-string`.
 *
 * `Type::isInstance()` / `isA()` declare `@param class-string<T>`, so passing a
 * bare `string` is a PHPStan error. The composition below is the supported way
 * in: `isClassName` and `isInterfaceName` both carry
 * `@phpstan-assert-if-true class-string $value`, so an `||` of the two narrows
 * the name in the true branch, and `isInstance()` then type-checks.
 *
 * Both halves are needed. `isClassName` alone is `class_exists()`, which is
 * false for an interface — the trap that makes the single-predicate version
 * look correct while silently rejecting every interface. Enums need no third
 * predicate: an enum is a class, so `class_exists()` already covers it.
 *
 * These asserts exist because the whole technique is invisible at runtime: the
 * guard keeps working if the annotations are reverted, and only the analysis
 * would quietly stop narrowing.
 */

function typeNameGuardNarrowsAnUnvalidatedStringToClassString(string $name): void
{
    if (Type::isClassName($name) || Type::isInterfaceName($name)) {
        assertType('class-string', $name);
    }
}

function typeNameGuardLetsIsInstanceAcceptARuntimeName(mixed $value, string $name): void
{
    if (Type::isClassName($name) || Type::isInterfaceName($name)) {
        // The point of the guard: no `argument.type` error on this call.
        if (Type::isInstance($value, $name)) {
            assertType('object', $value);
        }
    }
}

function typeIsClassNameAloneAlsoNarrows(string $name): void
{
    // It narrows just as well — the reason it is not enough is a *runtime*
    // one (interfaces return false), not a typing one.
    if (Type::isClassName($name)) {
        assertType('class-string', $name);
    }
}
