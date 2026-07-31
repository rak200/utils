<?php

declare(strict_types=1);

/*
 * PHPStan-only narrowing fixtures — see the header of ArrNarrowing.php for how
 * these files are analysed but never executed.
 */

namespace Rak200\Utils\Tests\StaticAnalysis;

use BcMath\Number;
use Rak200\Utils\Num;

use function PHPStan\Testing\assertType;

/*
 * The four aggregators declare `($values is iterable<int> ? int : float|int|
 * Number)`, so an all-int input keeps its int-ness instead of widening to the
 * full union. That is what lets `Num::sum()` be returned from a plain
 * `int`-typed method, which the unconditional `float|int|Number` forbade.
 *
 * Both branches are pinned because the failure mode is silent: a conditional
 * that degrades to the flat union still passes every runtime test, and only
 * costs consumers the `int` they were promised.
 *
 * On `sum` / `product` the int branch is true up to `PHP_INT_MAX`, past which
 * PHP promotes to float — the same claim, and the same caveat, that PHPStan's
 * own stub makes for `array_sum()`. `min` / `max` carry no caveat at all: they
 * return one of the elements rather than computing over them.
 */

/**
 * @param list<int>          $ints
 * @param array<string, int> $intMap
 * @param iterable<int>      $intIterable
 */
function numAggregatorsKeepIntness(array $ints, array $intMap, iterable $intIterable): void
{
    assertType('int', Num::sum($ints));
    assertType('int', Num::product($ints));
    assertType('int', Num::min($ints));
    assertType('int', Num::max($ints));

    // The key type is irrelevant — `iterable<int>` binds the value type only —
    // and a lazy source qualifies exactly as an array does.
    assertType('int', Num::sum($intMap));
    assertType('int', Num::sum($intIterable));

    // An empty literal satisfies `iterable<int>` vacuously, and `sum([])` really
    // does return the int 0, so the branch is correct rather than merely lucky.
    assertType('int', Num::sum([]));
}

/**
 * @param list<float>                $floats
 * @param list<float|int>            $mixed
 * @param list<Number>               $numbers
 * @param iterable<float|int|Number> $anything
 */
function numAggregatorsWidenOtherwise(array $floats, array $mixed, array $numbers, iterable $anything): void
{
    // One non-int element is enough to take the false branch, including the
    // `float|int` union — `iterable<float|int>` is not `iterable<int>`.
    assertType('BcMath\Number|float|int', Num::sum($floats));
    assertType('BcMath\Number|float|int', Num::sum($mixed));
    assertType('BcMath\Number|float|int', Num::sum($numbers));
    assertType('BcMath\Number|float|int', Num::sum($anything));

    assertType('BcMath\Number|float|int', Num::product($floats));
    assertType('BcMath\Number|float|int', Num::min($floats));
    assertType('BcMath\Number|float|int', Num::max($floats));
}
