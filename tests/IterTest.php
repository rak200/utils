<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use ArrayIterator;
use Exception;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Iter;
use RuntimeException;

/**
 * @internal
 *
 * @coversNothing
 */
final class IterTest extends TestCase
{
    // ---- Sources ----------------------------------------------------------

    public function testRangeAscending(): void
    {
        $this->assertSame([0, 2, 4, 6, 8, 10], Iter::toArray(Iter::range(0, 10, 2)));
    }

    public function testRangeDescending(): void
    {
        $this->assertSame([5, 4, 3, 2, 1], Iter::toArray(Iter::range(5, 1, -1)));
    }

    public function testRangeSingleElement(): void
    {
        $this->assertSame([1], Iter::toArray(Iter::range(1, 1)));
    }

    public function testRangeEmptyWhenDirectionCannotReachEnd(): void
    {
        $this->assertSame([], Iter::toArray(Iter::range(5, 1)));
    }

    public function testRangeInfiniteBoundedByTake(): void
    {
        $this->assertSame([0, 1, 2, 3], Iter::toArray(Iter::take(Iter::range(0), 4)));
    }

    public function testRangeThrowsOnZeroStep(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::range(1, 10, 0);
    }

    public function testRepeatFinite(): void
    {
        $this->assertSame(['x', 'x', 'x'], Iter::toArray(Iter::repeat('x', 3)));
    }

    public function testRepeatZero(): void
    {
        $this->assertSame([], Iter::toArray(Iter::repeat('x', 0)));
    }

    public function testRepeatInfiniteBoundedByTake(): void
    {
        $this->assertSame(['x', 'x'], Iter::toArray(Iter::take(Iter::repeat('x'), 2)));
    }

    public function testRepeatThrowsOnNegativeTimes(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::repeat('x', -1);
    }

    public function testCycleFinite(): void
    {
        $this->assertSame([1, 2, 3, 1, 2, 3, 1], Iter::toArray(Iter::take(Iter::cycle([1, 2, 3]), 7)));
    }

    public function testCycleEmptyYieldsNothing(): void
    {
        $this->assertSame([], Iter::toArray(Iter::cycle([])));
    }

    public function testCycleOverSinglePassGeneratorSource(): void
    {
        $source = Iter::range(1, 3);
        $this->assertSame([1, 2, 3, 1, 2], Iter::toArray(Iter::take(Iter::cycle($source), 5)));
    }

    public function testIterate(): void
    {
        $this->assertSame([1, 2, 4, 8], Iter::toArray(Iter::take(Iter::iterate(1, static fn (int $n): int => $n * 2), 4)));
    }

    public function testTimes(): void
    {
        $this->assertSame([0, 1, 4], Iter::toArray(Iter::times(3, static fn (int $i): int => $i * $i)));
    }

    public function testTimesZero(): void
    {
        $this->assertSame([], Iter::toArray(Iter::times(0, static fn (int $i): int => $i)));
    }

    public function testTimesThrowsOnNegativeCount(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::times(-1, static fn (int $i): int => $i);
    }

    // ---- Lazy transforms --------------------------------------------------

    public function testMapReindexed(): void
    {
        $this->assertSame([2, 4, 6], Iter::toArray(Iter::map([1, 2, 3], static fn (int $n): int => $n * 2)));
    }

    public function testMapPreservesKeysAndReceivesKey(): void
    {
        $result = Iter::map(['a' => 1, 'b' => 2], static fn (int $v, string $k): string => $k.$v);
        $this->assertSame(['a' => 'a1', 'b' => 'b2'], Iter::toArray($result, true));
    }

    public function testFilterPreservesKeys(): void
    {
        $result = Iter::filter(['a' => 1, 'b' => 2, 'c' => 3], static fn (int $n): bool => $n > 1);
        $this->assertSame(['b' => 2, 'c' => 3], Iter::toArray($result, true));
    }

    public function testFlatMapReindexed(): void
    {
        $result = Iter::flatMap([1, 2, 3], static fn (int $n): array => [$n, $n * 10]);
        $this->assertSame([1, 10, 2, 20, 3, 30], Iter::toArray($result));
    }

    public function testTake(): void
    {
        $this->assertSame([1, 2], Iter::toArray(Iter::take([1, 2, 3, 4], 2)));
    }

    public function testTakeMoreThanAvailable(): void
    {
        $this->assertSame([1, 2], Iter::toArray(Iter::take([1, 2], 5)));
    }

    public function testTakeZero(): void
    {
        $this->assertSame([], Iter::toArray(Iter::take([1, 2, 3], 0)));
    }

    public function testTakeThrowsOnNegativeCount(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::take([1, 2, 3], -1);
    }

    public function testDrop(): void
    {
        $this->assertSame([3, 4], Iter::toArray(Iter::drop([1, 2, 3, 4], 2)));
    }

    public function testDropThrowsOnNegativeCount(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::drop([1, 2, 3], -1);
    }

    public function testTakeWhile(): void
    {
        $this->assertSame([1, 2], Iter::toArray(Iter::takeWhile([1, 2, 3, 4, 1], static fn (int $n): bool => $n < 3)));
    }

    public function testDropWhile(): void
    {
        $this->assertSame([3, 4, 1], Iter::toArray(Iter::dropWhile([1, 2, 3, 4, 1], static fn (int $n): bool => $n < 3)));
    }

    public function testChunk(): void
    {
        $this->assertSame([[1, 2], [3, 4], [5]], Iter::toArray(Iter::chunk([1, 2, 3, 4, 5], 2)));
    }

    public function testChunkLazyOverInfiniteSource(): void
    {
        $this->assertSame([[1, 2], [3, 4], [5, 6]], Iter::toArray(Iter::take(Iter::chunk(Iter::range(1), 2), 3)));
    }

    public function testChunkThrowsOnSizeBelowOne(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::chunk([1, 2, 3], 0);
    }

    public function testFlattenCompletely(): void
    {
        $this->assertSame([1, 2, 3, 4, 5], Iter::toArray(Iter::flatten([1, [2, 3], [4, [5]]])));
    }

    public function testFlattenToDepth(): void
    {
        $this->assertSame([1, 2, [3]], Iter::toArray(Iter::flatten([1, [2, [3]]], 1)));
    }

    public function testFlattenDescendsTraversables(): void
    {
        $nested = [1, new ArrayIterator([2, 3]), [4]];
        $this->assertSame([1, 2, 3, 4], Iter::toArray(Iter::flatten($nested)));
    }

    public function testFlattenThrowsOnNegativeDepth(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::flatten([1, [2]], -1);
    }

    public function testZipStopsAtShortest(): void
    {
        $this->assertSame([[1, 'a'], [2, 'b']], Iter::toArray(Iter::zip([1, 2, 3], ['a', 'b'])));
    }

    public function testZipWithInfiniteSource(): void
    {
        $result = Iter::zip(Iter::range(1), ['a', 'b', 'c']);
        $this->assertSame([[1, 'a'], [2, 'b'], [3, 'c']], Iter::toArray($result));
    }

    public function testZipWithTraversable(): void
    {
        $result = Iter::zip(new ArrayIterator([1, 2]), [10, 20]);
        $this->assertSame([[1, 10], [2, 20]], Iter::toArray($result));
    }

    public function testZipNoArgs(): void
    {
        $this->assertSame([], Iter::toArray(Iter::zip()));
    }

    public function testConcatReindexed(): void
    {
        $this->assertSame([1, 2, 3, 4], Iter::toArray(Iter::concat([1, 2], [3, 4])));
    }

    public function testConcatPreservesKeys(): void
    {
        $result = Iter::concat(['a' => 1], ['b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2], Iter::toArray($result, true));
    }

    public function testKeys(): void
    {
        $this->assertSame(['a', 'b'], Iter::toArray(Iter::keys(['a' => 1, 'b' => 2])));
    }

    public function testValues(): void
    {
        $this->assertSame(['x', 'y'], Iter::toArray(Iter::values([5 => 'x', 9 => 'y'])));
    }

    public function testUniqueIsStrict(): void
    {
        $this->assertSame([1, '1', 2, 3], Iter::toArray(Iter::unique([1, '1', 1, 2, 2, 3])));
    }

    public function testSlice(): void
    {
        $this->assertSame([20, 30], Iter::toArray(Iter::slice([10, 20, 30, 40, 50], 1, 2)));
    }

    public function testSliceToEnd(): void
    {
        $this->assertSame([20, 30], Iter::toArray(Iter::slice([10, 20, 30], 1)));
    }

    public function testSliceThrowsOnNegativeOffset(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::slice([1, 2, 3], -1);
    }

    public function testSliceThrowsOnNegativeLength(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::slice([1, 2, 3], 0, -1);
    }

    public function testTapPassesValuesThroughAfterSideEffect(): void
    {
        $seen = [];
        $result = Iter::toArray(Iter::tap([1, 2, 3], static function (int $v) use (&$seen): void {
            $seen[] = $v;
        }));
        $this->assertSame([1, 2, 3], $result);
        $this->assertSame([1, 2, 3], $seen);
    }

    // ---- Terminals --------------------------------------------------------

    public function testFirst(): void
    {
        $this->assertSame(5, Iter::first([5, 6, 7]));
    }

    public function testFirstThrowsOnEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::first([]);
    }

    public function testFirstOrNull(): void
    {
        /** @var list<int> $empty */
        $empty = [];
        $this->assertSame(5, Iter::firstOrNull([5, 6]));
        $this->assertNull(Iter::firstOrNull($empty));
    }

    public function testLast(): void
    {
        $this->assertSame(7, Iter::last([5, 6, 7]));
    }

    public function testLastThrowsOnEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::last([]);
    }

    public function testLastOrNull(): void
    {
        /** @var list<int> $empty */
        $empty = [];
        $this->assertSame(7, Iter::lastOrNull([5, 6, 7]));
        $this->assertNull(Iter::lastOrNull($empty));
    }

    public function testFind(): void
    {
        $this->assertSame(2, Iter::find([1, 2, 3, 4], static fn (int $n): bool => $n % 2 === 0));
    }

    public function testFindThrowsWhenNoneMatch(): void
    {
        /** @var list<int> $odd */
        $odd = [1, 3, 5];
        $this->expectException(RuntimeException::class);
        Iter::find($odd, static fn (int $n): bool => $n % 2 === 0);
    }

    public function testFindOrNull(): void
    {
        /** @var list<int> $odd */
        $odd = [1, 3];
        $this->assertSame(2, Iter::findOrNull([1, 2, 3], static fn (int $n): bool => $n % 2 === 0));
        $this->assertNull(Iter::findOrNull($odd, static fn (int $n): bool => $n % 2 === 0));
    }

    public function testNth(): void
    {
        $this->assertSame(20, Iter::nth([10, 20, 30], 1));
    }

    public function testNthThrowsWhenOutOfRange(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::nth([10], 5);
    }

    public function testNthThrowsOnNegativeIndex(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::nth([10, 20], -1);
    }

    public function testNthOrNull(): void
    {
        $this->assertSame(20, Iter::nthOrNull([10, 20, 30], 1));
        $this->assertNull(Iter::nthOrNull([10], 5));
    }

    public function testNthOrNullThrowsOnNegativeIndex(): void
    {
        $this->expectException(RuntimeException::class);
        Iter::nthOrNull([10, 20], -1);
    }

    public function testReduce(): void
    {
        $this->assertSame(10, Iter::reduce([1, 2, 3, 4], static fn (int $c, int $v): int => $c + $v, 0));
    }

    public function testCount(): void
    {
        $this->assertSame(3, Iter::count([1, 2, 3]));
    }

    public function testCountConsumesBoundedInfiniteSource(): void
    {
        $this->assertSame(5, Iter::count(Iter::take(Iter::range(1), 5)));
    }

    public function testContainsStrict(): void
    {
        $this->assertTrue(Iter::contains([1, 2, 3], 2));
        $this->assertFalse(Iter::contains([1, 2, 3], '2'));
    }

    public function testContainsLoose(): void
    {
        $this->assertTrue(Iter::contains([1, 2, 3], '2', false));
    }

    public function testAny(): void
    {
        /** @var list<int> $odd */
        $odd = [1, 3, 5];
        $this->assertTrue(Iter::any([1, 2, 3], static fn (int $n): bool => $n % 2 === 0));
        $this->assertFalse(Iter::any($odd, static fn (int $n): bool => $n % 2 === 0));
    }

    public function testEvery(): void
    {
        /** @var list<int> $even */
        $even = [2, 4, 6];
        $this->assertTrue(Iter::every($even, static fn (int $n): bool => $n % 2 === 0));
        $this->assertFalse(Iter::every([2, 3, 4], static fn (int $n): bool => $n % 2 === 0));
    }

    public function testEveryVacuouslyTrueOnEmpty(): void
    {
        /** @var list<int> $empty */
        $empty = [];
        $this->assertTrue(Iter::every($empty, static fn (int $n): bool => $n > 0));
    }

    public function testToArrayReindexesByDefault(): void
    {
        $this->assertSame([1, 2], Iter::toArray(['a' => 1, 'b' => 2]));
    }

    public function testToArrayPreservesKeys(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], Iter::toArray(['a' => 1, 'b' => 2], true));
    }

    // ---- Laziness & single-pass contract ----------------------------------

    public function testTransformsAreLazy(): void
    {
        $calls = 0;
        $mapped = Iter::map(Iter::range(1), static function (int $n) use (&$calls): int {
            ++$calls;

            return $n * 2;
        });
        $result = Iter::toArray(Iter::take($mapped, 2));
        $this->assertSame([2, 4], $result);
        $this->assertSame(2, $calls);
    }

    public function testGeneratorIsSinglePass(): void
    {
        $gen = Iter::map([1, 2, 3], static fn (int $n): int => $n);
        $this->assertSame([1, 2, 3], Iter::toArray($gen));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot traverse an already closed generator');
        Iter::toArray($gen);
    }

    public function testComposedInfinitePipelineTerminates(): void
    {
        $squares = Iter::map(Iter::range(1), static fn (int $n): int => $n * $n);
        $this->assertSame([1, 4, 9, 16, 25], Iter::toArray(Iter::take($squares, 5)));
    }
}
