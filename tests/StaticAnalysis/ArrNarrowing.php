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
use Rak200\Utils\Iter;

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

/*
 * `Arr::sort` / `sortBy` carry `@return ($preserveKeys is true ? array<K, T> :
 * list<T>)`, so the key type survives the call only on the preserving branch.
 * Both branches are pinned here because the failure mode is silent: a
 * conditional that degrades to a plain `array<K, T>` still passes every runtime
 * test, and only costs consumers the `list` they were promised.
 *
 * Note the preserving branch of a `list` input: the keys survive, so the result
 * is `array<int, T>` and NOT a list — the one genuinely surprising case.
 */

/**
 * @param array<string, int> $map
 */
function arrSortKeepsTheKeyTypeOnlyWhenAsked(array $map): void
{
    assertType('array<string, int>', Arr::sort($map, null, true));
    assertType('list<int>', Arr::sort($map));
}

/**
 * @param list<int> $list
 */
function arrSortOfAListPreservingKeysIsNotAList(array $list): void
{
    // `int<0, max>`, not plain `int`: the keys came from a list, and PHPStan
    // keeps that range even though their order is now the sorted one.
    assertType('array<int<0, max>, int>', Arr::sort($list, null, true));
    assertType('list<int>', Arr::sort($list));
}

/**
 * @param array<string, int> $map
 */
function arrSortByKeepsTheKeyTypeOnlyWhenAsked(array $map): void
{
    assertType('array<string, int>', Arr::sortBy($map, static fn (int $v): int => $v, true));
    assertType('list<int>', Arr::sortBy($map, static fn (int $v): int => $v));
}

/*
 * `Arr::search` carries the array's own key type, so it is at least as precise
 * as the `array_search` it wraps — actually more, since throwing on a miss
 * drops the native's `|false` arm: over a list the native gives
 * `int<0, max>|false` and this gives `int<0, max>`.
 *
 * `searchOrNull` cannot do the same, and the assert below pins the *imprecise*
 * type deliberately. PHPStan does not resolve a template inside a union with
 * null, and the only spelling that does resolve —
 * `($array is non-empty-array ? K : null)` — is untrue here: a non-empty array
 * may simply not contain the value, and claiming otherwise would make callers'
 * `=== null` checks look like dead code. Widening back is the honest choice, so
 * this assert exists to stop someone "fixing" it into a lie that type-checks.
 */

/**
 * Every key shape, so a regression cannot hide in whichever one is untested:
 * a list (keys are a range), an int-keyed map that is not a list, a
 * string-keyed map, and a mixed-key array where `array-key` is the honest
 * answer rather than a fallback.
 *
 * @param list<string>          $list
 * @param array<int, string>    $intMap
 * @param array<string, int>    $stringMap
 * @param array<array-key, int> $mixedMap
 */
function arrSearchCarriesTheKeyType(array $list, array $intMap, array $stringMap, array $mixedMap): void
{
    assertType('int<0, max>', Arr::search($list, 'a'));
    assertType('int', Arr::search($intMap, 'a'));
    assertType('string', Arr::search($stringMap, 1));
    // Rendered with parentheses because K resolved to its `array-key` bound
    // rather than to a narrower key type — the honest answer for a mixed-key
    // array, and visibly distinct from the pre-fix `int|string` above.
    assertType('(int|string)', Arr::search($mixedMap, 1));
}

/**
 * @param list<string>       $list
 * @param array<string, int> $map
 */
function arrSearchOrNullCannotCarryIt(array $list, array $map): void
{
    assertType('int|string|null', Arr::searchOrNull($list, 'a'));
    assertType('int|string|null', Arr::searchOrNull($map, 1));
}

/*
 * The first/last key family carries the array's own key type. The bare halves
 * get it from a guard that narrows `$array` to `non-empty-array<K, mixed>`,
 * which is the only context where `array_key_first()` propagates the template.
 * The `*OrNull` halves get it from `($array is non-empty-array ? K : null)`,
 * which is *true* here — a non-empty array always has a first and a last key —
 * where the identical shape on `searchOrNull` would be a lie. That contrast is
 * the whole reason one of the two shipped wide and this one did not.
 *
 * The plain `null|K` these used to carry silently degraded to `int|string|null`:
 * a template bounded by `array-key` collapses into the surrounding null-union
 * and loses K. Nothing at runtime changes if it degrades again, which is why the
 * pinning has to happen here.
 */

/**
 * @param list<string>                 $list
 * @param non-empty-list<string>       $nonEmptyList
 * @param array<string, int>           $map
 * @param non-empty-array<string, int> $nonEmptyMap
 */
function arrFirstLastKeyCarryTheKeyType(
    array $list,
    array $nonEmptyList,
    array $map,
    array $nonEmptyMap,
): void {
    // The bare halves: already correct, pinned so a regression in the same
    // annotation family cannot hide behind the two below.
    assertType('int<0, max>', Arr::firstKey($list));
    assertType('string', Arr::lastKey($map));

    // Possibly empty: the condition is undecidable, so both arms survive.
    assertType('int<0, max>|null', Arr::firstKeyOrNull($list));
    assertType('string|null', Arr::lastKeyOrNull($map));

    // Statically non-empty: the null arm is dropped outright — the win a
    // consumer actually feels, since it removes a `=== null` they cannot reach.
    assertType('int<0, max>', Arr::firstKeyOrNull($nonEmptyList));
    assertType('string', Arr::lastKeyOrNull($nonEmptyMap));
}

/*
 * `Arr::removeAt` is list-preserving: closing the gap renumbers the integer
 * keys, so a list in gives a list out. A regression to a plain `array` would
 * leave every runtime test green, which is why it is pinned here.
 */

/**
 * @param list<int>          $list
 * @param array<string, int> $map
 */
function arrRemoveAtPreservesListness(array $list, array $map): void
{
    assertType('list<int>', Arr::removeAt($list, 1));
    // `array<int>` is how PHPStan renders `array<array-key, int>`: the string
    // keys survive, so list-ness is not claimed for a map.
    assertType('array<int>', Arr::removeAt($map, 1));
}

/*
 * The `int<0, max>` group. A count and a 0-based position are never negative,
 * and saying so is what lets these feed a `Countable::count()` implementation —
 * PHPStan analyses that method as returning `int<0, max>`, so a plain `int`
 * cannot satisfy it. Nothing at runtime changes if the range is dropped, which
 * is exactly why it needs pinning here rather than in the suite.
 */

/**
 * @param array<string, int> $map
 */
function arrCountAndKeyPositionAreNonNegative(array $map): void
{
    assertType('int<0, max>', Arr::count($map));
    assertType('int<0, max>', Arr::keyPosition($map, 'a'));
    assertType('int<0, max>|null', Arr::keyPositionOrNull($map, 'a'));
}

/**
 * @param iterable<int> $source
 */
function iterCountIsNonNegative(iterable $source): void
{
    assertType('int<0, max>', Iter::count($source));
}
