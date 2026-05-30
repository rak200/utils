<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Arr;
use RuntimeException;

final class ArrTest extends TestCase {
    public function testIs(): void {
        $this->assertTrue(Arr::is([]));
        $this->assertTrue(Arr::is([1, 2, 3]));
        $this->assertTrue(Arr::is(['a' => 1]));
        $this->assertFalse(Arr::is(null));
        $this->assertFalse(Arr::is('abc'));
        $this->assertFalse(Arr::is(42));
        $this->assertFalse(Arr::is(new \stdClass()));
    }

    public function testIsEmptyIsNotEmpty(): void {
        $this->assertTrue(Arr::isEmpty([]));
        $this->assertFalse(Arr::isEmpty([1]));
        $this->assertFalse(Arr::isNotEmpty([]));
        $this->assertTrue(Arr::isNotEmpty([1]));
    }

    public function testIsList(): void {
        $this->assertTrue(Arr::isList([]));
        $this->assertTrue(Arr::isList([1, 2, 3]));
        $this->assertTrue(Arr::isList(['a', 'b']));
        $this->assertFalse(Arr::isList(['a' => 1]));
        $this->assertFalse(Arr::isList([1 => 'a', 0 => 'b']));
        $this->assertFalse(Arr::isList('abc'));
        $this->assertFalse(Arr::isList(null));
    }

    public function testIsAssoc(): void {
        $this->assertTrue(Arr::isAssoc(['a' => 1]));
        $this->assertTrue(Arr::isAssoc([1 => 'a', 0 => 'b']));
        $this->assertFalse(Arr::isAssoc([]));
        $this->assertFalse(Arr::isAssoc([1, 2, 3]));
        $this->assertFalse(Arr::isAssoc(new \stdClass()));
    }

    public function testIsNonEmptyArray(): void {
        $this->assertTrue(Arr::isNonEmptyArray([1]));
        $this->assertTrue(Arr::isNonEmptyArray(['a' => 1]));
        $this->assertFalse(Arr::isNonEmptyArray([]));
        $this->assertFalse(Arr::isNonEmptyArray('a'));
        $this->assertFalse(Arr::isNonEmptyArray(null));
    }

    public function testFirstReturnsFirstElement(): void {
        $this->assertSame(1, Arr::first([1, 2, 3]));
        $this->assertSame('a', Arr::first(['x' => 'a', 'y' => 'b']));
    }

    public function testFirstThrowsOnEmpty(): void {
        $this->expectException(RuntimeException::class);
        Arr::first([]);
    }

    public function testFirstOrNullReturnsNullOnEmpty(): void {
        /** @var array<int, int> $empty */
        $empty = [];
        $this->assertNull(Arr::firstOrNull($empty));
        $this->assertSame(1, Arr::firstOrNull([1, 2]));
    }

    public function testLastReturnsLastElement(): void {
        $this->assertSame(3, Arr::last([1, 2, 3]));
    }

    public function testLastThrowsOnEmpty(): void {
        $this->expectException(RuntimeException::class);
        Arr::last([]);
    }

    public function testLastOrNullReturnsNullOnEmpty(): void {
        /** @var array<int, int> $empty */
        $empty = [];
        $this->assertNull(Arr::lastOrNull($empty));
    }

    public function testFindReturnsMatchingValue(): void {
        $this->assertSame(2, Arr::find([1, 2, 3], fn(int $v): bool => $v > 1));
    }

    public function testFindThrowsWhenNoMatch(): void {
        $this->expectException(RuntimeException::class);
        /** @var array<int, int> $values */
        $values = [1, 2, 3];
        Arr::find($values, fn(int $v): bool => $v > 99);
    }

    public function testFindOrNullReturnsNullWhenNoMatch(): void {
        /** @var array<int, int> $values */
        $values = [1, 2, 3];
        $this->assertNull(Arr::findOrNull($values, fn(int $v): bool => $v > 99));
        $this->assertSame(2, Arr::findOrNull([1, 2, 3], fn(int $v): bool => $v === 2));
    }

    public function testFilterPreservesKeys(): void {
        $result = Arr::filter([1, 2, 3, 4], fn(int $v): bool => $v % 2 === 0);
        $this->assertSame([1 => 2, 3 => 4], $result);
    }

    public function testMapPreservesKeys(): void {
        $result = Arr::map(['a' => 1, 'b' => 2], fn(int $v): int => $v * 10);
        $this->assertSame(['a' => 10, 'b' => 20], $result);
    }

    public function testReduce(): void {
        $this->assertSame(10, Arr::reduce([1, 2, 3, 4], fn(int $acc, int $v): int => $acc + $v, 0));
    }

    public function testReduceReceivesKey(): void {
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

    public function testFlattenWithDefaultDepth(): void {
        $this->assertSame([1, 2, 3, 4], Arr::flatten([[1, 2], [3, [4]]]));
    }

    public function testFlattenWithDepth(): void {
        $this->assertSame([1, 2, 3, [4]], Arr::flatten([[1, 2], [3, [4]]], 1));
    }

    public function testFlattenRejectsNegativeDepth(): void {
        $this->expectException(RuntimeException::class);
        Arr::flatten([[1]], -1);
    }

    public function testGroupBy(): void {
        $result = Arr::groupBy([1, 2, 3, 4, 5], fn(int $v): string => $v % 2 === 0 ? 'even' : 'odd');
        $this->assertSame(['odd' => [1, 3, 5], 'even' => [2, 4]], $result);
    }

    public function testPartition(): void {
        [$even, $odd] = Arr::partition([1, 2, 3, 4], fn(int $v): bool => $v % 2 === 0);
        $this->assertSame([2, 4], $even);
        $this->assertSame([1, 3], $odd);
    }

    public function testChunk(): void {
        $this->assertSame([[1, 2], [3, 4], [5]], Arr::chunk([1, 2, 3, 4, 5], 2));
    }

    public function testChunkRejectsNonPositiveSize(): void {
        $this->expectException(RuntimeException::class);
        Arr::chunk([1, 2], 0);
    }

    public function testUnique(): void {
        $this->assertSame([1, 2, 3], Arr::unique([1, 2, 2, 3, 1]));
    }

    public function testHas(): void {
        $this->assertTrue(Arr::has(['a' => 1], 'a'));
        $this->assertFalse(Arr::has(['a' => 1], 'b'));
        $this->assertTrue(Arr::has(['a' => null], 'a'));
    }

    public function testKeysValues(): void {
        $this->assertSame(['a', 'b'], Arr::keys(['a' => 1, 'b' => 2]));
        $this->assertSame([1, 2], Arr::values(['a' => 1, 'b' => 2]));
    }

    public function testZip(): void {
        $this->assertSame(
            [[1, 'a'], [2, 'b'], [3, 'c']],
            Arr::zip([1, 2, 3], ['a', 'b', 'c']),
        );
    }

    public function testZipPadsShorterArraysWithNull(): void {
        $this->assertSame(
            [[1, 'a'], [2, 'b'], [3, null]],
            Arr::zip([1, 2, 3], ['a', 'b']),
        );
    }

    public function testRangeAscending(): void {
        $this->assertSame([1, 2, 3], Arr::range(1, 3));
    }

    public function testRangeDescending(): void {
        $this->assertSame([5, 3, 1], Arr::range(5, 1, -2));
    }

    public function testRangeRejectsZeroStep(): void {
        $this->expectException(RuntimeException::class);
        Arr::range(1, 10, 0);
    }

    public function testContains(): void {
        $this->assertTrue(Arr::contains([1, 2, 3], 2));
        $this->assertFalse(Arr::contains([1, 2, 3], 4));
        $this->assertFalse(Arr::contains([1, 2, 3], '2'));
        $this->assertTrue(Arr::contains([1, 2, 3], '2', strict: false));
    }

    public function testPluck(): void {
        $rows = [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3],
        ];
        $this->assertSame([1, 2, 3], Arr::pluck($rows, 'id'));
        $this->assertSame(['a', 'b', null], Arr::pluck($rows, 'name'));
    }

    public function testKeyByColumn(): void {
        $rows = [
            ['id' => 'a', 'v' => 1],
            ['id' => 'b', 'v' => 2],
        ];
        $this->assertSame(
            ['a' => ['id' => 'a', 'v' => 1], 'b' => ['id' => 'b', 'v' => 2]],
            Arr::keyBy($rows, 'id'),
        );
    }

    public function testKeyByCallable(): void {
        $result = Arr::keyBy(
            [1, 2, 3, 4],
            fn(int $v): string => $v % 2 === 0 ? 'even' : 'odd',
        );
        $this->assertSame(['odd' => 3, 'even' => 4], $result);
    }

    public function testKeyByThrowsWhenColumnMissing(): void {
        $this->expectException(RuntimeException::class);
        Arr::keyBy([['x' => 1]], 'missing');
    }

    public function testSort(): void {
        $this->assertSame([1, 2, 3], Arr::sort([3, 1, 2]));
        $this->assertSame(
            [3, 2, 1],
            Arr::sort([1, 2, 3], fn(int $a, int $b): int => $b <=> $a),
        );
    }

    public function testSortBy(): void {
        $people = [
            ['name' => 'c', 'age' => 30],
            ['name' => 'a', 'age' => 10],
            ['name' => 'b', 'age' => 20],
        ];
        $sorted = Arr::sortBy($people, fn(array $p): int => $p['age']);
        $this->assertSame(['a', 'b', 'c'], array_column($sorted, 'name'));
    }

    public function testMerge(): void {
        $this->assertSame(
            ['a' => 1, 'b' => 3, 'c' => 4],
            Arr::merge(['a' => 1, 'b' => 2], ['b' => 3, 'c' => 4]),
        );
        $this->assertSame([], Arr::merge());
    }

    public function testPickExcept(): void {
        $a = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->assertSame(['a' => 1, 'c' => 3], Arr::pick($a, ['a', 'c']));
        $this->assertSame(['b' => 2], Arr::except($a, ['a', 'c']));
    }
}
