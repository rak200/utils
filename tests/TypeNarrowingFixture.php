<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use ArrayIterator;
use Rak200\Utils\Type;

/**
 * Static-analysis fixture — NOT a PHPUnit test.
 *
 * Each `assertType()` call pins down the type-narrowing contract of a
 * {@see Type} predicate; PHPStan (level max) verifies the asserted type
 * matches what it infers in the truthy branch. If a `@phpstan-assert`
 * annotation regresses, PHPStan fails here.
 *
 * The file name deliberately does not end in `Test`, so PHPUnit never loads
 * or runs it — `PHPStan\Testing\assertType()` has no runtime definition
 * (it is resolved only during static analysis).
 */
final class TypeNarrowingFixture {
    public function scalars(mixed $value): void {
        if (Type::isStr($value)) {
            \PHPStan\Testing\assertType('string', $value);
        }
        if (Type::isInt($value)) {
            \PHPStan\Testing\assertType('int', $value);
        }
        if (Type::isFloat($value)) {
            \PHPStan\Testing\assertType('float', $value);
        }
        if (Type::isBool($value)) {
            \PHPStan\Testing\assertType('bool', $value);
        }
        if (Type::isNumericStr($value)) {
            \PHPStan\Testing\assertType('numeric-string', $value);
        }
    }

    /** Mirrors Caster::toNumber(): the int|float branch must cast to numeric-string. */
    public function numberConstruction(mixed $value): void {
        if (Type::isInt($value) || Type::isFloat($value)) {
            \PHPStan\Testing\assertType('float|int', $value);
        }
    }

    public function instanceOf(mixed $value): void {
        if (Type::isInstanceOf($value, ArrayIterator::class)) {
            \PHPStan\Testing\assertType(ArrayIterator::class, $value);
        }
    }
}
