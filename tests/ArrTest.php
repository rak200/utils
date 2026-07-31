<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Arr;
use Rak200\Utils\Exception\BadCallbackException;
use Rak200\Utils\Exception\EmptySourceException;
use Rak200\Utils\Exception\LookupException;
use Rak200\Utils\Exception\MalformedArgumentException;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArrTest extends TestCase
{
    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Arr::is($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable
    {
        yield 'empty array' => [[], true];

        yield 'list' => [[1, 2, 3], true];

        yield 'assoc' => [['a' => 1], true];

        yield 'null' => [null, false];

        yield 'string' => ['abc', false];

        yield 'int' => [42, false];

        yield 'object' => [new stdClass(), false];
    }

    public function testIsEmptyIsNotEmpty(): void
    {
        $this->assertTrue(Arr::isEmpty([]));
        $this->assertFalse(Arr::isEmpty([1]));
        $this->assertFalse(Arr::isNotEmpty([]));
        $this->assertTrue(Arr::isNotEmpty([1]));
    }

    #[DataProvider('isListProvider')]
    public function testIsList(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Arr::isList($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isListProvider(): iterable
    {
        yield 'empty array' => [[], true];

        yield 'list' => [[1, 2, 3], true];

        yield 'string-valued list' => [['a', 'b'], true];

        yield 'assoc' => [['a' => 1], false];

        yield 'out-of-order int keys' => [[1 => 'a', 0 => 'b'], false];

        yield 'string' => ['abc', false];

        yield 'null' => [null, false];
    }

    public function testIsAssoc(): void
    {
        $this->assertTrue(Arr::isAssoc(['a' => 1]));
        $this->assertTrue(Arr::isAssoc([1 => 'a', 0 => 'b']));
        $this->assertFalse(Arr::isAssoc([]));
        $this->assertFalse(Arr::isAssoc([1, 2, 3]));
        $this->assertFalse(Arr::isAssoc(new stdClass()));
    }

    public function testFirstReturnsFirstElement(): void
    {
        $this->assertSame(1, Arr::first([1, 2, 3]));
        $this->assertSame('a', Arr::first(['x' => 'a', 'y' => 'b']));
    }

    public function testFirstThrowsOnEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Arr::first([]);
    }

    public function testFirstOrNullReturnsNullOnEmpty(): void
    {
        /** @var array<int, int> $empty */
        $empty = [];
        $this->assertNull(Arr::firstOrNull($empty));
        $this->assertSame(1, Arr::firstOrNull([1, 2]));
    }

    public function testLastReturnsLastElement(): void
    {
        $this->assertSame(3, Arr::last([1, 2, 3]));
    }

    public function testLastThrowsOnEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Arr::last([]);
    }

    public function testLastOrNullReturnsNullOnEmpty(): void
    {
        /** @var array<int, int> $empty */
        $empty = [];
        $this->assertNull(Arr::lastOrNull($empty));
    }

    public function testLastOrNullReturnsLastElement(): void
    {
        $this->assertSame(3, Arr::lastOrNull([1, 2, 3]));
        $this->assertSame('z', Arr::lastOrNull(['a' => 'x', 'b' => 'z']));
    }

    public function testFindReturnsMatchingValue(): void
    {
        $this->assertSame(2, Arr::find([1, 2, 3], fn (int $v): bool => $v > 1));
    }

    public function testFindThrowsWhenNoMatch(): void
    {
        $this->expectException(LookupException::class);

        /** @var array<int, int> $values */
        $values = [1, 2, 3];
        Arr::find($values, fn (int $v): bool => $v > 99);
    }

    public function testFindOrNullReturnsNullWhenNoMatch(): void
    {
        /** @var array<int, int> $values */
        $values = [1, 2, 3];
        $this->assertNull(Arr::findOrNull($values, fn (int $v): bool => $v > 99));
        $this->assertSame(2, Arr::findOrNull([1, 2, 3], fn (int $v): bool => $v === 2));
    }

    public function testFilterPreservesKeys(): void
    {
        $result = Arr::filter([1, 2, 3, 4], fn (int $v): bool => $v % 2 === 0);
        $this->assertSame([1 => 2, 3 => 4], $result);
    }

    public function testMapPreservesKeys(): void
    {
        $result = Arr::map(['a' => 1, 'b' => 2], fn (int $v): int => $v * 10);
        $this->assertSame(['a' => 10, 'b' => 20], $result);
    }

    public function testFlatMap(): void
    {
        $this->assertSame([1, 10, 2, 20], Arr::flatMap([1, 2], fn (int $n): array => [$n, $n * 10]));
        $this->assertSame([], Arr::flatMap([], fn (int $n): array => [$n]));
        // Elements mapping to an empty iterable simply contribute nothing.
        $this->assertSame([2], Arr::flatMap([1, 2], fn (int $n): array => $n === 1 ? [] : [$n]));
        // One level only: an inner array survives as an element.
        $this->assertSame([1, [2]], Arr::flatMap([1], fn (int $n): array => [$n, [2]]));
    }

    public function testFlatMapReceivesKeyAndAcceptsAnyIterable(): void
    {
        // The shape a MultiMap-style snapshot needs: key plus each value.
        $this->assertSame(
            [['a', 1], ['a', 2], ['b', 3]],
            Arr::flatMap(
                ['a' => [1, 2], 'b' => [3]],
                fn (array $values, string $k): array => Arr::map($values, fn (int $v): array => [$k, $v]),
            ),
        );
        // Any iterable, not just an array — a Generator works too.
        $this->assertSame([1, 2], Arr::flatMap([1], function (int $n): Generator {
            yield $n;

            yield $n + 1;
        }));
    }

    public function testFlatMapThrowsWhenCallbackReturnsNonIterable(): void
    {
        $this->expectException(BadCallbackException::class);
        $this->expectExceptionMessage('Callback must return an iterable. Got: int');

        // Deliberately violates the declared `callable(T, K): iterable<R>` —
        // which is the whole point: the guard exists for callers that do not
        // honour a contract PHP cannot enforce. PHPStan is right to reject the
        // call, so the rejection is what gets ignored here.
        // @phpstan-ignore argument.type, argument.templateType
        Arr::flatMap([1], fn (): mixed => 42);
    }

    public function testReduce(): void
    {
        $this->assertSame(10, Arr::reduce([1, 2, 3, 4], fn (int $acc, int $v): int => $acc + $v, 0));
    }

    public function testReduceReceivesKey(): void
    {
        $keys = [];
        Arr::reduce(
            ['a' => 1, 'b' => 2],
            function (mixed $acc, int $v, string $k) use (&$keys): int {
                $keys[] = $k;

                return $acc + $v;
            },
            0,
        );
        $this->assertSame(['a', 'b'], $keys);
    }

    public function testFlattenWithDefaultDepth(): void
    {
        $this->assertSame([1, 2, 3, 4], Arr::flatten([[1, 2], [3, [4]]]));
    }

    public function testFlattenWithDepth(): void
    {
        $this->assertSame([1, 2, 3, [4]], Arr::flatten([[1, 2], [3, [4]]], 1));
    }

    public function testFlattenRejectsNegativeDepth(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Arr::flatten([[1]], -1);
    }

    public function testGroupBy(): void
    {
        $result = Arr::groupBy([1, 2, 3, 4, 5], fn (int $v): string => $v % 2 === 0 ? 'even' : 'odd');
        $this->assertSame(['odd' => [1, 3, 5], 'even' => [2, 4]], $result);
    }

    public function testPartition(): void
    {
        [$even, $odd] = Arr::partition([1, 2, 3, 4], fn (int $v): bool => $v % 2 === 0);
        $this->assertSame([2, 4], $even);
        $this->assertSame([1, 3], $odd);
    }

    public function testChunk(): void
    {
        $this->assertSame([[1, 2], [3, 4], [5]], Arr::chunk([1, 2, 3, 4, 5], 2));
    }

    public function testChunkRejectsNonPositiveSize(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Arr::chunk([1, 2], 0);
    }

    public function testUnique(): void
    {
        $this->assertSame([1, 2, 3], Arr::unique([1, 2, 2, 3, 1]));
    }

    public function testUniqueIsStrict(): void
    {
        $this->assertSame([1, '1', 2], Arr::unique([1, '1', 2, '1', 1]));
    }

    public function testHas(): void
    {
        $this->assertTrue(Arr::has(['a' => 1], 'a'));
        $this->assertFalse(Arr::has(['a' => 1], 'b'));
        $this->assertTrue(Arr::has(['a' => null], 'a'));
        $this->assertTrue(Arr::has([null], 0));
    }

    public function testHasDotPath(): void
    {
        $data = ['user' => ['name' => 'rak', 'meta' => ['age' => 30]]];
        $this->assertTrue(Arr::has($data, 'user.name'));
        $this->assertTrue(Arr::has($data, 'user.meta.age'));
        $this->assertFalse(Arr::has($data, 'user.email'));
        $this->assertFalse(Arr::has($data, 'user.name.x'));   // descends into a non-array
        // pure dot-path: a key that literally contains a dot is traversed, not matched
        $this->assertFalse(Arr::has(['a.b' => 1], 'a.b'));    // use hasKey() for the literal key
    }

    public function testHasKey(): void
    {
        $this->assertTrue(Arr::hasKey(['a.b' => 1], 'a.b'));
        $this->assertFalse(Arr::hasKey(['a' => ['b' => 1]], 'a.b'));   // pure literal, no traversal
        $this->assertTrue(Arr::hasKey(['a' => null], 'a'));
        $this->assertTrue(Arr::hasKey([null], 0));
    }

    public function testGetSetForget(): void
    {
        $data = ['user' => ['name' => 'rak', 'roles' => ['admin']]];
        $this->assertSame('rak', Arr::get($data, 'user.name'));
        $this->assertSame('admin', Arr::get($data, 'user.roles.0'));

        $this->assertSame(['a' => ['b' => ['c' => 1]]], Arr::set([], 'a.b.c', 1));
        $this->assertSame(['a' => ['x' => 1, 'y' => 2]], Arr::set(['a' => ['x' => 1]], 'a.y', 2));
        // overwrites a non-array value met along the path
        $this->assertSame(['a' => ['b' => 1]], Arr::set(['a' => 5], 'a.b', 1));

        $this->assertSame(['a' => ['c' => 2]], Arr::forget(['a' => ['b' => 1, 'c' => 2]], 'a.b'));
        $this->assertSame(['a' => 1], Arr::forget(['a' => 1], 'x.y'));   // unresolved → unchanged copy
    }

    public function testSetForgetAreImmutable(): void
    {
        $original = ['a' => ['b' => 1]];
        Arr::set($original, 'a.c', 2);
        Arr::forget($original, 'a.b');
        $this->assertSame(['a' => ['b' => 1]], $original);
    }

    public function testGetOrNull(): void
    {
        $this->assertNull(Arr::getOrNull(['a' => 1], 'x.y'));
        $this->assertSame(1, Arr::getOrNull(['a' => 1], 'a'));
        $this->assertNull(Arr::getOrNull(['a' => null], 'a.b'));
    }

    public function testGetThrowsWhenMissing(): void
    {
        $this->expectException(LookupException::class);
        Arr::get(['a' => 1], 'x.y');
    }

    public function testGetKey(): void
    {
        $this->assertSame(1, Arr::getKey(['a.b' => 1], 'a.b'));   // literal key, no traversal
        $this->assertNull(Arr::getKey(['a' => null], 'a'));       // present null is returned
        $this->assertSame(1, Arr::getKey([1], 0));
    }

    public function testGetKeyThrowsWhenMissing(): void
    {
        $this->expectException(LookupException::class);
        Arr::getKey(['a' => ['b' => 1]], 'a.b');   // no dot-traversal → not found
    }

    public function testGetKeyOrNull(): void
    {
        $this->assertSame(1, Arr::getKeyOrNull(['a.b' => 1], 'a.b'));
        $this->assertNull(Arr::getKeyOrNull(['a' => ['b' => 1]], 'a.b'));   // no traversal
        $this->assertNull(Arr::getKeyOrNull(['a' => null], 'a'));           // present null → null
    }

    public function testDotUndot(): void
    {
        $this->assertSame(
            ['a.b.c' => 1, 'x' => 2],
            Arr::dot(['a' => ['b' => ['c' => 1]], 'x' => 2]),
        );
        $this->assertSame(
            ['a' => ['b' => 1, 'c' => 2]],
            Arr::undot(['a.b' => 1, 'a.c' => 2]),
        );
        // round-trip
        $nested = ['user' => ['name' => 'rak', 'roles' => ['admin', 'editor']]];
        $this->assertSame($nested, Arr::undot(Arr::dot($nested)));
    }

    public function testKeysValues(): void
    {
        $this->assertSame(['a', 'b'], Arr::keys(['a' => 1, 'b' => 2]));
        $this->assertSame([1, 2], Arr::values(['a' => 1, 'b' => 2]));
    }

    public function testZip(): void
    {
        $this->assertSame(
            [[1, 'a'], [2, 'b'], [3, 'c']],
            Arr::zip([1, 2, 3], ['a', 'b', 'c']),
        );
    }

    public function testZipPadsShorterArraysWithNull(): void
    {
        $this->assertSame(
            [[1, 'a'], [2, 'b'], [3, null]],
            Arr::zip([1, 2, 3], ['a', 'b']),
        );
    }

    public function testZipWithNoArrays(): void
    {
        $this->assertSame([], Arr::zip());
    }

    public function testRangeAscending(): void
    {
        $this->assertSame([1, 2, 3], Arr::range(1, 3));
    }

    public function testRangeDescending(): void
    {
        $this->assertSame([5, 3, 1], Arr::range(5, 1, -2));
    }

    public function testRangeRejectsZeroStep(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Arr::range(1, 10, 0);
    }

    public function testRangeReturnsEmptyWhenDirectionCannotReachEnd(): void
    {
        $this->assertSame([], Arr::range(5, 1));       // ascending step, start > end
        $this->assertSame([], Arr::range(1, 5, -1));   // descending step, start < end
    }

    public function testContains(): void
    {
        $this->assertTrue(Arr::contains([1, 2, 3], 2));
        $this->assertFalse(Arr::contains([1, 2, 3], 4));
        $this->assertFalse(Arr::contains([1, 2, 3], '2'));
        $this->assertTrue(Arr::contains([1, 2, 3], '2', strict: false));
    }

    public function testPluck(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3],
        ];
        $this->assertSame([1, 2, 3], Arr::pluck($rows, 'id'));
        $this->assertSame(['a', 'b', null], Arr::pluck($rows, 'name'));
    }

    public function testPluckWithIndexKey(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
        ];
        $this->assertSame([1 => 'a', 2 => 'b'], Arr::pluck($rows, 'name', 'id'));
        $this->assertSame([1 => null], Arr::pluck([['id' => 1]], 'name', 'id')); // missing value → null
        // later collision overwrites, like array_column
        $dup = [['k' => 'x', 'v' => 1], ['k' => 'x', 'v' => 2]];
        $this->assertSame(['x' => 2], Arr::pluck($dup, 'v', 'k'));
    }

    public function testPluckThrowsWhenIndexKeyMissing(): void
    {
        $this->expectException(LookupException::class);
        Arr::pluck([['name' => 'a']], 'name', 'id');
    }

    public function testKeyByColumn(): void
    {
        $rows = [
            ['id' => 'a', 'v' => 1],
            ['id' => 'b', 'v' => 2],
        ];
        $this->assertSame(
            ['a' => ['id' => 'a', 'v' => 1], 'b' => ['id' => 'b', 'v' => 2]],
            Arr::keyBy($rows, 'id'),
        );
    }

    public function testKeyByCallable(): void
    {
        $result = Arr::keyBy(
            [1, 2, 3, 4],
            fn (int $v): string => $v % 2 === 0 ? 'even' : 'odd',
        );
        $this->assertSame(['odd' => 3, 'even' => 4], $result);
    }

    public function testKeyByThrowsWhenColumnMissing(): void
    {
        $this->expectException(LookupException::class);
        Arr::keyBy([['x' => 1]], 'missing');
    }

    public function testKeyByThrowsWhenResolvedKeyIsNotIntOrString(): void
    {
        $this->expectException(BadCallbackException::class);
        Arr::keyBy([['id' => 1.5]], 'id');   // a float is not a valid array key
    }

    public function testSort(): void
    {
        $this->assertSame([1, 2, 3], Arr::sort([3, 1, 2]));
        $this->assertSame(
            [3, 2, 1],
            Arr::sort([1, 2, 3], fn (int $a, int $b): int => $b <=> $a),
        );
    }

    public function testSortPreservingKeys(): void
    {
        $this->assertSame(
            ['a' => 1, 'b' => 2, 'c' => 3],
            Arr::sort(['c' => 3, 'a' => 1, 'b' => 2], preserveKeys: true),
        );
        $this->assertSame(
            ['c' => 3, 'b' => 2, 'a' => 1],
            Arr::sort(['c' => 3, 'a' => 1, 'b' => 2], fn (int $a, int $b): int => $b <=> $a, true),
        );
        // A list keeps its keys too, so the result is NOT a list.
        $this->assertSame([1 => 'a', 0 => 'b'], Arr::sort(['b', 'a'], preserveKeys: true));

        $original = ['c' => 3, 'a' => 1];
        Arr::sort($original, preserveKeys: true);
        $this->assertSame(['c' => 3, 'a' => 1], $original); // immutable
    }

    public function testSortPreservingKeysIsStableForEqualElements(): void
    {
        // PHP sorts have been stable since 8.0: a comparator that calls every
        // pair equal must leave insertion order untouched.
        $this->assertSame(
            ['c' => 3, 'a' => 1, 'b' => 2],
            Arr::sort(['c' => 3, 'a' => 1, 'b' => 2], fn (int $a, int $b): int => 0, true),
        );
    }

    public function testSortBy(): void
    {
        $people = [
            ['name' => 'c', 'age' => 30],
            ['name' => 'a', 'age' => 10],
            ['name' => 'b', 'age' => 20],
        ];
        $sorted = Arr::sortBy($people, fn (array $p): int => $p['age']);
        $this->assertSame(['a', 'b', 'c'], array_column($sorted, 'name'));
    }

    public function testSortByPreservingKeys(): void
    {
        $this->assertSame(
            ['short' => 'a', 'mid' => 'bb', 'long' => 'ccc'],
            Arr::sortBy(
                ['long' => 'ccc', 'short' => 'a', 'mid' => 'bb'],
                static fn (string $v): int => strlen($v),
                true,
            ),
        );
        // The extractor still receives the key as its second argument.
        $this->assertSame(
            ['a' => 1, 'b' => 2],
            Arr::sortBy(['b' => 2, 'a' => 1], static fn (int $v, string $k): string => $k, true),
        );
        // A list keeps its keys too, so the result is NOT a list.
        $this->assertSame(
            [1 => 'a', 0 => 'bb'],
            Arr::sortBy(['bb', 'a'], static fn (string $v): int => strlen($v), true),
        );

        $original = ['b' => 2, 'a' => 1];
        Arr::sortBy($original, static fn (int $v): int => $v, true);
        $this->assertSame(['b' => 2, 'a' => 1], $original); // immutable
    }

    public function testSortByPreservingKeysIsStableForEqualElements(): void
    {
        $this->assertSame(
            ['c' => 3, 'a' => 1, 'b' => 2],
            Arr::sortBy(['c' => 3, 'a' => 1, 'b' => 2], static fn (int $v): int => 0, true),
        );
    }

    public function testMerge(): void
    {
        $this->assertSame(
            ['a' => 1, 'b' => 3, 'c' => 4],
            Arr::merge(['a' => 1, 'b' => 2], ['b' => 3, 'c' => 4]),
        );
        $this->assertSame([], Arr::merge());
    }

    public function testPickExcept(): void
    {
        $a = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->assertSame(['a' => 1, 'c' => 3], Arr::pick($a, ['a', 'c']));
        $this->assertSame(['b' => 2], Arr::except($a, ['a', 'c']));
    }

    public function testCount(): void
    {
        $this->assertSame(0, Arr::count([]));
        $this->assertSame(3, Arr::count([1, 2, 3]));
        $this->assertSame(2, Arr::count(['a' => 1, 'b' => 2]));
    }

    public function testReverse(): void
    {
        $this->assertSame([3, 2, 1], Arr::reverse([1, 2, 3]));
        $this->assertSame(['b' => 2, 'a' => 1], Arr::reverse(['a' => 1, 'b' => 2]));
        $this->assertSame([2 => 3, 1 => 2, 0 => 1], Arr::reverse([1, 2, 3], true)); // preserve keys
    }

    public function testSlice(): void
    {
        $this->assertSame([2, 3], Arr::slice([1, 2, 3, 4, 5], 1, 2));
        $this->assertSame([4, 5], Arr::slice([1, 2, 3, 4, 5], -2));         // negative offset
        $this->assertSame([2, 3, 4], Arr::slice([1, 2, 3, 4, 5], 1, -1));   // negative length
        $this->assertSame([1 => 2, 2 => 3], Arr::slice([1, 2, 3], 1, 2, true)); // preserve keys
    }

    public function testRemoveAt(): void
    {
        $list = [1, 2, 3, 4, 5];
        $this->assertSame([1, 3, 4, 5], Arr::removeAt($list, 1));           // default length 1
        $this->assertSame([1, 4, 5], Arr::removeAt($list, 1, 2));
        $this->assertSame([1, 2, 3, 4], Arr::removeAt($list, -1));          // negative index
        $this->assertSame([1, 2, 3, 4, 5], Arr::removeAt($list, 1, 0));     // nothing to remove
        $this->assertSame([1], Arr::removeAt($list, 1, 99));                // length past the end

        $original = [1, 2, 3];
        Arr::removeAt($original, 1);
        $this->assertSame([1, 2, 3], $original); // immutable
    }

    public function testRemoveAtOutOfRangeIndexClampsOnlyOnTheNegativeSide(): void
    {
        // Past the end removes nothing, but a far-negative index clamps to the
        // start and removes the first element — array_splice's own asymmetry,
        // which Arr::slice shares.
        $this->assertSame([1, 2, 3], Arr::removeAt([1, 2, 3], 10));
        $this->assertSame([2, 3], Arr::removeAt([1, 2, 3], -10));
    }

    public function testRemoveAtNegativeLengthStopsBeforeTheEnd(): void
    {
        // Same convention as Arr::slice's own $length, not an error.
        $this->assertSame([1, 5], Arr::removeAt([1, 2, 3, 4, 5], 1, -1));
        $this->assertSame([1, 2, 3, 4, 5], Arr::removeAt([1, 2, 3, 4, 5], 1, -9));
    }

    public function testRemoveAtKeepsStringKeysAndRenumbersIntegerOnes(): void
    {
        $this->assertSame(
            ['a' => 1, 'c' => 3, 0 => 9],
            Arr::removeAt(['a' => 1, 'b' => 2, 'c' => 3, 5 => 9], 1),
        );
    }

    /**
     * @param array<string, int>|list<int> $source
     */
    #[DataProvider('removeAtParityProvider')]
    public function testRemoveAtMatchesArraySpliceExactly(array $source, int $index, int $length): void
    {
        $native = $source;
        array_splice($native, $index, $length);

        $this->assertSame($native, Arr::removeAt($source, $index, $length));
    }

    /**
     * @return iterable<string, array{array<array-key, int>, int, int}>
     */
    public static function removeAtParityProvider(): iterable
    {
        $list = [1, 2, 3, 4, 5];
        $map = ['a' => 1, 'b' => 2, 'c' => 3, 5 => 9];

        foreach ([[1, 1], [0, 1], [-1, 1], [-10, 1], [10, 1], [1, 0], [1, 99], [1, -1], [1, -9]] as [$index, $length]) {
            yield "list index {$index} length {$length}" => [$list, $index, $length];

            yield "map index {$index} length {$length}" => [$map, $index, $length];
        }
    }

    public function testFlip(): void
    {
        $this->assertSame([1 => 'a', 2 => 'b'], Arr::flip(['a' => 1, 'b' => 2]));
        $this->assertSame(['x' => 0, 'y' => 1], Arr::flip(['x', 'y']));
    }

    public function testCombine(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], Arr::combine(['a', 'b'], [1, 2]));
        $this->assertSame([], Arr::combine([], []));
    }

    public function testCombineThrowsOnLengthMismatch(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Arr::combine(['a', 'b'], [1]);
    }

    public function testDiff(): void
    {
        $this->assertSame([0 => 1, 2 => 3], Arr::diff([1, 2, 3, 4], [2, 4]));
        $this->assertSame([1, 2, 3], Arr::diff([1, 2, 3]));               // no others → unchanged
        $this->assertSame([1 => 2], Arr::diff([1, 2, 3], [1], [3]));      // multiple others
    }

    public function testIntersect(): void
    {
        $this->assertSame([1 => 2, 2 => 3], Arr::intersect([1, 2, 3], [2, 3, 4]));
        $this->assertSame([1, 2, 3], Arr::intersect([1, 2, 3]));          // no others → unchanged
        $this->assertSame([1 => 2], Arr::intersect([1, 2, 3], [2, 3], [2])); // common to all
    }

    public function testSearch(): void
    {
        $this->assertSame(1, Arr::search(['a', 'b', 'c'], 'b'));
        $this->assertSame('y', Arr::search(['x' => 1, 'y' => 2], 2));
        $this->assertSame(0, Arr::search([5, 6], 5)); // key 0 is not mistaken for "not found"
        $this->assertSame(1, Arr::searchOrNull([1, 2, 3], 2));
        $this->assertNull(Arr::searchOrNull([1, 2, 3], 9));
        $this->assertNull(Arr::searchOrNull([0, 1], '0')); // strict by default
        $this->assertSame(0, Arr::searchOrNull([0, 1], '0', false)); // loose
    }

    public function testSearchLooseComparison(): void
    {
        // The match must be at a non-zero key: a comparison that degenerated
        // into an assignment would be truthy on the first element and answer 0.
        $this->assertSame(1, Arr::search(['a', '2', 3], 2, false));
        $this->assertSame(2, Arr::search([false, null, '7'], 7, false));
        // Strict mode rejects what loose mode accepts, on the same input.
        $this->assertSame(1, Arr::search(['a', '2', 3], '2'));
    }

    public function testSearchThrowsWhenMissing(): void
    {
        $this->expectException(LookupException::class);
        Arr::search([1, 2, 3], 9);
    }

    public function testKeyPosition(): void
    {
        $map = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->assertSame(0, Arr::keyPosition($map, 'a'));
        $this->assertSame(1, Arr::keyPosition($map, 'b'));
        $this->assertSame(2, Arr::keyPosition($map, 'c'));
        // Over a list the position and the key coincide, which is why the
        // interesting cases above are the keyed ones.
        $this->assertSame(2, Arr::keyPosition(['x', 'y', 'z'], 2));

        $this->assertSame(1, Arr::keyPositionOrNull($map, 'b'));
        $this->assertNull(Arr::keyPositionOrNull($map, 'nope'));
        $this->assertNull(Arr::keyPositionOrNull([], 'a'));
    }

    public function testKeyPositionFollowsHasKeyOnNumericStringKeys(): void
    {
        // PHP stores '1' as the int key 1, so both spellings must find it —
        // otherwise keyPosition would disagree with hasKey on the same input.
        $array = ['1' => 'a', 'x' => 'b', 7 => 'c'];
        $this->assertSame(0, Arr::keyPosition($array, '1'));
        $this->assertSame(0, Arr::keyPosition($array, 1));
        $this->assertSame(2, Arr::keyPosition($array, '7'));
        $this->assertSame(2, Arr::keyPosition($array, 7));

        // ' 1' is never normalised, so it stays a distinct (absent) string key.
        $this->assertNull(Arr::keyPositionOrNull($array, ' 1'));
        $this->assertFalse(Arr::hasKey($array, ' 1'));
        // The two agree on every spelling above.
        foreach (['1', 1, '7', 7, 'x'] as $key) {
            $this->assertSame(Arr::hasKey($array, $key), Arr::keyPositionOrNull($array, $key) !== null);
        }
    }

    public function testKeyPositionThrowsWhenMissing(): void
    {
        $this->expectException(LookupException::class);
        $this->expectExceptionMessage('Key "nope" not found in array.');
        Arr::keyPosition(['a' => 1], 'nope');
    }

    public function testCountValues(): void
    {
        $this->assertSame(['a' => 2, 'b' => 1], Arr::countValues(['a', 'b', 'a']));
        $this->assertSame([1 => 2, 2 => 1], Arr::countValues([1, 1, 2]));
        $this->assertSame([], Arr::countValues([]));
    }

    public function testAppend(): void
    {
        $this->assertSame([1, 2, 3], Arr::append([1, 2], 3));
        $this->assertSame([1, 2, 3, 4], Arr::append([1, 2], 3, 4));
        $this->assertSame(['a' => 1, 0 => 2], Arr::append(['a' => 1], 2)); // keeps string keys
        $original = [1, 2];
        $this->assertSame([1, 2, 3], Arr::append($original, 3)); // new array
        $this->assertSame([1, 2], $original); // immutable
    }

    public function testPrepend(): void
    {
        $this->assertSame([0, 1, 2], Arr::prepend([1, 2], 0));
        $this->assertSame([-1, 0, 1, 2], Arr::prepend([1, 2], -1, 0));
        $this->assertSame([1, 2], Arr::prepend([1, 2]));            // no values → unchanged
        $this->assertSame([3, 'a' => 1], Arr::prepend(['a' => 1], 3)); // string keys kept
    }

    public function testShift(): void
    {
        $this->assertSame([10, [20, 30]], Arr::shift([10, 20, 30]));
        $this->assertSame([1, ['b' => 2]], Arr::shift(['a' => 1, 'b' => 2])); // string keys kept
        $this->assertSame(['only', []], Arr::shift(['only']));
        $original = [1, 2, 3];
        Arr::shift($original);
        $this->assertSame([1, 2, 3], $original); // immutable
    }

    public function testShiftOrNull(): void
    {
        $this->assertSame([10, [20, 30]], Arr::shiftOrNull([10, 20, 30]));
        $this->assertNull(Arr::shiftOrNull([]));
    }

    public function testShiftThrowsWhenEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Arr::shift([]);
    }

    public function testPop(): void
    {
        $this->assertSame([30, [10, 20]], Arr::pop([10, 20, 30]));
        $this->assertSame([2, ['a' => 1]], Arr::pop(['a' => 1, 'b' => 2])); // string keys kept
        $this->assertSame(['only', []], Arr::pop(['only']));
        $original = [1, 2, 3];
        Arr::pop($original);
        $this->assertSame([1, 2, 3], $original); // immutable
    }

    public function testPopOrNull(): void
    {
        $this->assertSame([30, [10, 20]], Arr::popOrNull([10, 20, 30]));
        $this->assertNull(Arr::popOrNull([]));
    }

    public function testPopThrowsWhenEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Arr::pop([]);
    }

    public function testFirstLastKey(): void
    {
        $this->assertSame('x', Arr::firstKey(['x' => 1, 'y' => 2]));
        $this->assertSame('y', Arr::lastKey(['x' => 1, 'y' => 2]));
        $this->assertSame(0, Arr::firstKey([10, 20]));
        $this->assertSame(1, Arr::lastKey([10, 20]));

        // Through a variable, not the literal `[]`: the *OrNull pair now declares
        // `($array is non-empty-array ? K : null)`, so over a literal empty array
        // PHPStan folds the result to `null` and reports the assertion as always
        // true. The runtime contract is still worth asserting — same idiom as
        // testFirstOrNullReturnsNullOnEmpty().
        /** @var array<string, int> $empty */
        $empty = [];
        $this->assertNull(Arr::firstKeyOrNull($empty));
        $this->assertNull(Arr::lastKeyOrNull($empty));
        $this->assertSame('x', Arr::firstKeyOrNull(['x' => 1]));
    }

    public function testFirstKeyThrowsWhenEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Arr::firstKey([]);
    }

    public function testLastKeyThrowsWhenEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Arr::lastKey([]);
    }

    public function testSortKeys(): void
    {
        $this->assertSame(['a' => 2, 'b' => 1, 'c' => 3], Arr::sortKeys(['b' => 1, 'a' => 2, 'c' => 3]));
        $this->assertSame(['c' => 3, 'b' => 1, 'a' => 2], Arr::sortKeys(['b' => 1, 'a' => 2, 'c' => 3], true));
        $original = ['b' => 1, 'a' => 2];
        Arr::sortKeys($original);
        $this->assertSame(['b' => 1, 'a' => 2], $original); // immutable
    }

    public function testFill(): void
    {
        $this->assertSame(['x', 'x', 'x'], Arr::fill(3, 'x'));
        $this->assertSame([], Arr::fill(0, 'x'));
    }

    public function testFillThrowsForNegativeCount(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Arr::fill(-1, 'x');
    }

    public function testFillKeys(): void
    {
        $this->assertSame(['a' => 0, 'b' => 0], Arr::fillKeys(['a', 'b'], 0));
        $this->assertSame([], Arr::fillKeys([], 0));
    }

    public function testLastKeyOrNullNonEmpty(): void
    {
        $this->assertSame('b', Arr::lastKeyOrNull(['a' => 1, 'b' => 2]));
    }

    public function testChunkSizeOne(): void
    {
        $this->assertSame([[1], [2], [3]], Arr::chunk([1, 2, 3], 1));
    }

    public function testUndotMultipleTopLevelKeys(): void
    {
        $this->assertSame(
            ['a' => ['b' => 1], 'c' => 2],
            Arr::undot(['a.b' => 1, 'c' => 2]),
        );
    }

    public function testZipReindexesStringKeys(): void
    {
        $this->assertSame(
            [[1, 3], [2, 4]],
            Arr::zip(['x' => 1, 'y' => 2], [3, 4]),
        );
    }

    public function testSearchIsStrictByDefault(): void
    {
        $this->expectException(LookupException::class);
        Arr::search([0], '0');
    }

    public function testSortByExtractedKeyNotValue(): void
    {
        $this->assertSame(['a', 'bb', 'ccc'], Arr::sortBy(['bb', 'a', 'ccc'], static fn (string $v): int => strlen($v)));
    }

    public function testShiftEmptyMessage(): void
    {
        $this->expectException(EmptySourceException::class);
        $this->expectExceptionMessage('Cannot shift from an empty array.');
        Arr::shift([]);
    }

    public function testPopEmptyMessage(): void
    {
        $this->expectException(EmptySourceException::class);
        $this->expectExceptionMessage('Cannot pop from an empty array.');
        Arr::pop([]);
    }

    public function testGetWithIntKey(): void
    {
        $this->assertSame(10, Arr::get([10, 20], 0));
    }

    public function testForgetKeepsSiblings(): void
    {
        $this->assertSame(
            ['a' => 1, 'c' => 3],
            Arr::forget(['a' => 1, 'b' => 2, 'c' => 3], 'b'),
        );
        $this->assertSame(
            ['a' => ['y' => 2], 'b' => 3, 'c' => 4],
            Arr::forget(['a' => ['x' => 1, 'y' => 2], 'b' => 3, 'c' => 4], 'a.x'),
        );
    }

    public function testDotWithNestedIntKeys(): void
    {
        $this->assertSame(['0.0' => 1], Arr::dot([[1]]));
    }
}
