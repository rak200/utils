<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Arr;
use RuntimeException;

final class ArrTest extends TestCase {
    public function testIsEmptyIsNotEmpty(): void {
        $this->assertTrue(Arr::isEmpty([]));
        $this->assertFalse(Arr::isEmpty([1]));
        $this->assertFalse(Arr::isNotEmpty([]));
        $this->assertTrue(Arr::isNotEmpty([1]));
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
        $this->assertNull(Arr::firstOrNull([]));
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
        $this->assertNull(Arr::lastOrNull([]));
    }

    public function testFindReturnsMatchingValue(): void {
        $this->assertSame(2, Arr::find([1, 2, 3], fn(int $v): bool => $v > 1));
    }

    public function testFindThrowsWhenNoMatch(): void {
        $this->expectException(RuntimeException::class);
        Arr::find([1, 2, 3], fn(int $v): bool => $v > 99);
    }

    public function testFindOrNullReturnsNullWhenNoMatch(): void {
        $this->assertNull(Arr::findOrNull([1, 2, 3], fn(int $v): bool => $v > 99));
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
}
