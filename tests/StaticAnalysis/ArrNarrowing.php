<?php

declare(strict_types=1);

/*
 * PHPStan-only narrowing fixtures. These files are analysed by `composer
 * phpstan` (their `assertType()` calls fail the analysis when the inferred type
 * drifts from the expected one) but never executed by PHPUnit — they carry no
 * `Test` suffix, so the suite does not collect them, and `PHPStan\Testing\
 * assertType` exists only inside the analyser. They lock in the PHPDoc
 * `@phpstan-assert*` annotations that the runtime suite cannot cover: a reverted
 * annotation still leaves every runtime test green, but breaks the asserts here.
 */

namespace Rak200\Utils\Tests\StaticAnalysis;

use Rak200\Utils\Arr;

use function PHPStan\Testing\assertType;

/**
 * `Arr::isList` narrows a `mixed` guard subject to `list<mixed>` in the true
 * branch (`@phpstan-assert-if-true list<mixed> $value`), so a guarded `foreach`
 * needs no companion `Arr::is` call for the narrowing.
 */
function arrIsListNarrowsMixedToList(mixed $value): void
{
    if (Arr::isList($value)) {
        assertType('list<mixed>', $value);
    }
}
