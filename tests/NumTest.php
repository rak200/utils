<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use BcMath\Number;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Num;
use RoundingMode;
use RuntimeException;

/**
 * @internal
 *
 * @coversNothing
 */
final class NumTest extends TestCase
{
    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Num::is($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable
    {
        yield 'int' => [5, true];

        yield 'float' => [5.5, true];

        yield 'numeric string' => ['5.5', true];

        yield 'exponent string' => ['-1e3', true];

        yield 'Number' => [new Number('123.456'), true];

        yield 'non-numeric string' => ['abc', false];

        yield 'surrounding spaces' => [' 42 ', false];

        yield 'leading space' => [' 42', false];

        yield 'trailing newline' => ["42\n", false];

        yield 'trailing tab' => ["1.5\t", false];

        yield 'null' => [null, false];

        yield 'bool' => [true, false];

        yield 'array' => [[], false];
    }

    #[DataProvider('isIntProvider')]
    public function testIsInt(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Num::isInt($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isIntProvider(): iterable
    {
        yield 'int' => [5, true];

        yield 'float' => [5.0, false];

        yield 'numeric str' => ['5', false];

        yield 'padded' => [' 5 ', false];
    }

    #[DataProvider('isFloatProvider')]
    public function testIsFloat(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Num::isFloat($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isFloatProvider(): iterable
    {
        yield 'float' => [5.0, true];

        yield 'int' => [5, false];

        yield 'numeric str' => ['5.0', false];

        yield 'padded' => [' 5.0 ', false];
    }

    public function testParseInt(): void
    {
        $this->assertSame(42, Num::parseInt('42'));
        $this->assertSame(-42, Num::parseInt('-42'));
        $this->assertSame(42, Num::parseInt('+42'));
        $this->assertSame(255, Num::parseInt('ff', 16));
        $this->assertSame(10, Num::parseInt('1010', 2));
    }

    public function testParseIntThrowsOnInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        Num::parseInt('abc');
    }

    public function testParseIntOrNullReturnsNullOnInvalid(): void
    {
        $this->assertNull(Num::parseIntOrNull('abc'));
        $this->assertNull(Num::parseIntOrNull(''));
        $this->assertNull(Num::parseIntOrNull('12.5'));
        $this->assertNull(Num::parseIntOrNull('2', 2));
    }

    public function testParseIntOrNullRejectsSurroundingWhitespace(): void
    {
        $this->assertNull(Num::parseIntOrNull(' 42 '));
        $this->assertNull(Num::parseIntOrNull(' 42'));
        $this->assertNull(Num::parseIntOrNull("42\n"));
    }

    public function testParseIntRejectsInvalidBase(): void
    {
        $this->expectException(RuntimeException::class);
        Num::parseIntOrNull('1', 37);
    }

    public function testParseFloat(): void
    {
        $this->assertSame(3.14, Num::parseFloat('3.14'));
        $this->assertSame(-3.14, Num::parseFloat('-3.14'));
        $this->assertSame(1.5e3, Num::parseFloat('1.5e3'));
    }

    public function testParseFloatThrowsOnInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        Num::parseFloat('abc');
    }

    public function testParseFloatOrNullReturnsNullOnInvalid(): void
    {
        $this->assertNull(Num::parseFloatOrNull('abc'));
        $this->assertNull(Num::parseFloatOrNull(''));
    }

    public function testParseFloatOrNullRejectsSurroundingWhitespace(): void
    {
        $this->assertNull(Num::parseFloatOrNull(' 3.14 '));
        $this->assertNull(Num::parseFloatOrNull(' 3.14'));
        $this->assertNull(Num::parseFloatOrNull("3.14\n"));
        $this->assertNull(Num::parseFloatOrNull("1.5e3\t"));
    }

    public function testClamp(): void
    {
        $this->assertSame(5, Num::clamp(5, 0, 10));
        $this->assertSame(0, Num::clamp(-3, 0, 10));
        $this->assertSame(10, Num::clamp(15, 0, 10));
    }

    public function testClampRejectsMinGreaterThanMax(): void
    {
        $this->expectException(RuntimeException::class);
        Num::clamp(5, 10, 0);
    }

    public function testInRange(): void
    {
        $this->assertTrue(Num::inRange(5, 0, 10));
        $this->assertTrue(Num::inRange(0, 0, 10));
        $this->assertTrue(Num::inRange(10, 0, 10));
        $this->assertFalse(Num::inRange(-1, 0, 10));
        $this->assertFalse(Num::inRange(11, 0, 10));
    }

    public function testRound(): void
    {
        $this->assertSame(3.0, Num::round(2.5));
        $this->assertSame(2.5, Num::round(2.5, 1));
        $this->assertSame(2.0, Num::round(2.5, 0, RoundingMode::HalfTowardsZero));
    }

    public function testFormat(): void
    {
        $this->assertSame('1,234.50', Num::format(1234.5));
        $this->assertSame('1.234,50', Num::format(1234.5, 2, ',', '.'));
        $this->assertSame('1,234', Num::format(1234, 0));
    }

    public function testSumAvgMinMax(): void
    {
        $this->assertSame(10, Num::sum([1, 2, 3, 4]));
        $this->assertSame(10.5, Num::sum([1, 2, 3, 4.5]));
        $this->assertSame(2.5, Num::avg([1, 2, 3, 4]));
        $this->assertSame(1, Num::min([3, 1, 2]));
        $this->assertSame(3, Num::max([3, 1, 2]));
    }

    public function testAvgThrowsOnEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        Num::avg([]);
    }

    public function testMinThrowsOnEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        Num::min([]);
    }

    public function testMaxThrowsOnEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        Num::max([]);
    }

    public function testAbsSign(): void
    {
        $this->assertSame(5, Num::abs(-5));
        $this->assertSame(5.5, Num::abs(-5.5));
        $this->assertSame(1, Num::sign(5));
        $this->assertSame(-1, Num::sign(-5));
        $this->assertSame(0, Num::sign(0));
    }

    public function testPow(): void
    {
        $this->assertSame(8, Num::pow(2, 3));
        $this->assertSame(0.25, Num::pow(2, -2));
    }

    public function testSqrt(): void
    {
        $this->assertSame(4.0, Num::sqrt(16));
        $this->assertSame(1.5, Num::sqrt(2.25));
    }

    public function testSqrtRejectsNegative(): void
    {
        $this->expectException(RuntimeException::class);
        Num::sqrt(-1);
    }

    public function testFloorAndCeil(): void
    {
        $this->assertSame(2.0, Num::floor(2.9));
        $this->assertSame(-3.0, Num::floor(-2.1));
        $this->assertSame(3.0, Num::ceil(2.1));
        $this->assertSame(-2.0, Num::ceil(-2.9));
        $this->assertSame(2.4, Num::floor(2.49, 1));
        $this->assertSame(2.5, Num::ceil(2.41, 1));
    }

    public function testModFollowsDividendSign(): void
    {
        $this->assertSame(1, Num::mod(7, 3));
        $this->assertSame(-1, Num::mod(-7, 3));
        $this->assertSame(0.5, Num::mod(2.5, 1.0));
    }

    public function testModRejectsZeroDivisor(): void
    {
        $this->expectException(RuntimeException::class);
        Num::mod(5, 0);
    }

    public function testParseNumberReturnsBigNumber(): void
    {
        $n = Num::parseNumber('123456789012345678901234567890.5');
        $this->assertInstanceOf(Number::class, $n);
        $this->assertSame('123456789012345678901234567890.5', (string) $n);
    }

    public function testParseNumberOrNullRejectsNonNumeric(): void
    {
        $this->assertNull(Num::parseNumberOrNull('abc'));
        $this->assertNull(Num::parseNumberOrNull(''));
        $this->assertNull(Num::parseNumberOrNull('1e'));
    }

    public function testParseNumberAcceptsScientificNotation(): void
    {
        // Scientific notation is expanded to its exact decimal form, matching
        // the strings Num::is() reports as numeric.
        $this->assertSame('1500', (string) Num::parseNumber('1.5e3'));
        $this->assertSame('0.0015', (string) Num::parseNumber('1.5e-3'));
        $this->assertSame('-250', (string) Num::parseNumber('-2.5E+2'));
        $this->assertSame('10000000000', (string) Num::parseNumber('1e10'));
        $this->assertSame('5', (string) Num::parseNumber('.5e1'));
    }

    public function testParseNumberPreservesPrecisionOnScientificNotation(): void
    {
        // No narrowing through float: every digit survives the expansion.
        $this->assertSame(
            '12345678901234567890.12345',
            (string) Num::parseNumber('1.234567890123456789012345e19'),
        );
    }

    public function testParseNumberOrNullRejectsExcessiveExponent(): void
    {
        // Well-formed per Num::is(), but its decimal form is impractical, so it
        // cannot be represented as a Number.
        $this->assertNull(Num::parseNumberOrNull('1e999999999'));
    }

    public function testParseNumberOrNullRejectsSurroundingWhitespace(): void
    {
        $this->assertNull(Num::parseNumberOrNull(' 42 '));
        $this->assertNull(Num::parseNumberOrNull(' 1.5'));
        $this->assertNull(Num::parseNumberOrNull("1.5\t"));
    }

    public function testParseNumberThrowsOnInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        Num::parseNumber('xyz');
    }

    public function testSumWidensToNumber(): void
    {
        $result = Num::sum([1, 2, new Number('0.5')]);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('3.5', (string) $result);
    }

    public function testAvgWidensToNumber(): void
    {
        $result = Num::avg([new Number('1'), new Number('2'), new Number('3')]);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('2', (string) $result);
    }

    public function testMinMaxPropagateNumber(): void
    {
        $a = new Number('1.5');
        $b = new Number('2.5');
        $this->assertSame($a, Num::min([$b, $a]));
        $this->assertSame($b, Num::max([$a, $b]));
    }

    public function testAbsAndSignWithNumber(): void
    {
        $this->assertSame('5', (string) Num::abs(new Number('-5')));
        $this->assertSame(-1, Num::sign(new Number('-3.2')));
        $this->assertSame(1, Num::sign(new Number('3.2')));
        $this->assertSame(0, Num::sign(new Number('0')));
    }

    public function testClampInRangeWithNumber(): void
    {
        $this->assertSame('5', (string) Num::clamp(new Number('5'), new Number('0'), new Number('10')));
        $this->assertEquals(new Number('0'), Num::clamp(new Number('-3'), new Number('0'), new Number('10')));
        $this->assertTrue(Num::inRange(new Number('5'), new Number('0'), new Number('10')));
        $this->assertFalse(Num::inRange(new Number('-1'), new Number('0'), new Number('10')));
    }

    public function testPowSqrtFloorCeilWithNumber(): void
    {
        $this->assertInstanceOf(Number::class, Num::pow(new Number('2'), new Number('10')));
        $this->assertSame('1024', (string) Num::pow(new Number('2'), 10));
        $this->assertSame('1.4142135623', (string) Num::sqrt(new Number('2')));
        $this->assertEquals(new Number('2'), Num::floor(new Number('2.9')));
        $this->assertEquals(new Number('3'), Num::ceil(new Number('2.1')));
        $this->assertEquals(new Number('2.4'), Num::floor(new Number('2.49'), 1));
        $this->assertEquals(new Number('2.5'), Num::ceil(new Number('2.41'), 1));
    }

    public function testModWithNumber(): void
    {
        $this->assertEquals(new Number('1'), Num::mod(new Number('7'), new Number('3')));
        $this->assertEquals(new Number('-1'), Num::mod(new Number('-7'), new Number('3')));
    }

    public function testModByZeroNumberThrows(): void
    {
        $this->expectException(RuntimeException::class);
        Num::mod(new Number('5'), new Number('0'));
    }

    public function testSqrtRejectsNegativeNumber(): void
    {
        $this->expectException(RuntimeException::class);
        Num::sqrt(new Number('-1'));
    }

    public function testFloorCeilWithNumberAndNegativePrecision(): void
    {
        $this->assertEquals(new Number('1200'), Num::floor(new Number('1234.5'), -2));
        $this->assertEquals(new Number('1300'), Num::ceil(new Number('1234.5'), -2));
    }

    public function testFormatNumberWholeWithoutThousandsSeparator(): void
    {
        $this->assertSame('1234', Num::format(new Number('1234'), 0, '.', ''));
    }

    public function testParseIntOrNullRejectsSignOnly(): void
    {
        $this->assertNull(Num::parseIntOrNull('-'));
        $this->assertNull(Num::parseIntOrNull('+'));
    }

    public function testAggregationsAcceptAnyIterable(): void
    {
        // the aggregations take iterable, not just array — exercise a Generator
        $gen = static function (): Generator {
            yield from [1, 2, 3, 4];
        };
        $this->assertSame(10, Num::sum($gen()));
        $this->assertSame(24, Num::product($gen()));
        $this->assertSame(2.5, Num::avg($gen()));
        $this->assertSame(1, Num::min($gen()));
        $this->assertSame(4, Num::max($gen()));
    }

    public function testRoundPreservesNumber(): void
    {
        $result = Num::round(new Number('1.2345'), 2);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('1.23', (string) $result);
    }

    public function testFormatWithNumberPreservesPrecision(): void
    {
        $this->assertSame(
            '12,345,678,901,234,567,890.50',
            Num::format(new Number('12345678901234567890.5'), 2),
        );
        $this->assertSame(
            '1.234,57',
            Num::format(new Number('1234.567'), 2, ',', '.'),
        );
        $this->assertSame('-100.00', Num::format(new Number('-100'), 2));
    }

    public function testIntDiv(): void
    {
        $this->assertSame(3, Num::intDiv(7, 2));
        $this->assertSame(-3, Num::intDiv(-7, 2));
        $this->assertSame(-3, Num::intDiv(7, -2));
        $this->assertSame(3, Num::intDiv(-7, -2));
        $this->assertSame(0, Num::intDiv(1, 2));
    }

    public function testIntDivByZeroThrows(): void
    {
        $this->expectException(RuntimeException::class);
        Num::intDiv(1, 0);
    }

    public function testIsFinite(): void
    {
        $this->assertTrue(Num::isFinite(42));
        $this->assertTrue(Num::isFinite(-1));
        $this->assertTrue(Num::isFinite(3.14));
        $this->assertTrue(Num::isFinite('2.5'));
        $this->assertTrue(Num::isFinite(new Number('1.5')));
        $this->assertFalse(Num::isFinite(INF));
        $this->assertFalse(Num::isFinite(-INF));
        $this->assertFalse(Num::isFinite(NAN));
        $this->assertFalse(Num::isFinite('1e400'));   // overflows to INF
        $this->assertFalse(Num::isFinite('abc'));
        $this->assertFalse(Num::isFinite(null));
        $this->assertFalse(Num::isFinite([]));
    }

    public function testIsNan(): void
    {
        $this->assertTrue(Num::isNan(NAN));
        $this->assertFalse(Num::isNan(1.0));
        $this->assertFalse(Num::isNan(INF));
        $this->assertFalse(Num::isNan(1));
        $this->assertFalse(Num::isNan('1.5'));
        $this->assertFalse(Num::isNan('abc'));
        $this->assertFalse(Num::isNan(null));
    }

    public function testIsInfinite(): void
    {
        $this->assertTrue(Num::isInfinite(INF));
        $this->assertTrue(Num::isInfinite(-INF));
        $this->assertTrue(Num::isInfinite('1e400')); // overflows to INF
        $this->assertFalse(Num::isInfinite(1.0));
        $this->assertFalse(Num::isInfinite(NAN));
        $this->assertFalse(Num::isInfinite(1));
        $this->assertFalse(Num::isInfinite('1.5'));
        $this->assertFalse(Num::isInfinite('abc'));
        $this->assertFalse(Num::isInfinite(null));
    }

    public function testProduct(): void
    {
        $this->assertSame(24, Num::product([2, 3, 4]));
        $this->assertSame(1, Num::product([]));   // empty → 1 (int)
        $this->assertSame(0, Num::product([5, 0, 3]));
        $this->assertSame(7.5, Num::product([3, 2.5]));
    }

    public function testProductWidensToNumber(): void
    {
        $result = Num::product([new Number('2'), 3]);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('6', (string) $result);
    }

    public function testToBase(): void
    {
        $this->assertSame('ff', Num::toBase(255, 16));
        $this->assertSame('-ff', Num::toBase(-255, 16));
        $this->assertSame('0', Num::toBase(0, 2));
        $this->assertSame('1010', Num::toBase(10, 2));
        $this->assertSame('z', Num::toBase(35, 36));
        $this->assertSame('777', Num::toBase(511, 8));
        $this->assertSame('-9223372036854775808', Num::toBase(PHP_INT_MIN, 10));
    }

    public function testToBaseRoundTripsWithParseInt(): void
    {
        foreach ([0, 1, 42, 255, 1000, -1, -255, PHP_INT_MAX] as $value) {
            foreach ([2, 8, 10, 16, 36] as $base) {
                $this->assertSame($value, Num::parseInt(Num::toBase($value, $base), $base));
            }
        }
    }

    public function testToBaseThrowsForBadBase(): void
    {
        $this->expectException(RuntimeException::class);
        Num::toBase(10, 37);
    }

    public function testIsPositive(): void
    {
        $this->assertTrue(Num::isPositive(5));
        $this->assertTrue(Num::isPositive(0.001));
        $this->assertTrue(Num::isPositive(new Number('0.5')));
        $this->assertFalse(Num::isPositive(0));
        $this->assertFalse(Num::isPositive(-1));
        $this->assertFalse(Num::isPositive(-2.5));
        $this->assertFalse(Num::isPositive(new Number('0')));
    }

    public function testIsNegative(): void
    {
        $this->assertTrue(Num::isNegative(-5));
        $this->assertTrue(Num::isNegative(-0.001));
        $this->assertTrue(Num::isNegative(new Number('-0.5')));
        $this->assertFalse(Num::isNegative(0));
        $this->assertFalse(Num::isNegative(1));
        $this->assertFalse(Num::isNegative(new Number('0')));
    }
}
