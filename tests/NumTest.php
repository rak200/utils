<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Num;
use RoundingMode;
use RuntimeException;

final class NumTest extends TestCase {
    public function testTypeChecks(): void {
        $this->assertTrue(Num::isInteger(5));
        $this->assertFalse(Num::isInteger(5.0));
        $this->assertFalse(Num::isInteger('5'));
        $this->assertTrue(Num::isFloat(5.0));
        $this->assertFalse(Num::isFloat(5));
        $this->assertTrue(Num::isNumeric(5));
        $this->assertTrue(Num::isNumeric(5.5));
        $this->assertTrue(Num::isNumeric('5.5'));
        $this->assertFalse(Num::isNumeric('abc'));
    }

    public function testParseInt(): void {
        $this->assertSame(42, Num::parseInt('42'));
        $this->assertSame(-42, Num::parseInt('-42'));
        $this->assertSame(42, Num::parseInt('+42'));
        $this->assertSame(255, Num::parseInt('ff', 16));
        $this->assertSame(10, Num::parseInt('1010', 2));
    }

    public function testParseIntThrowsOnInvalid(): void {
        $this->expectException(RuntimeException::class);
        Num::parseInt('abc');
    }

    public function testParseIntOrNullReturnsNullOnInvalid(): void {
        $this->assertNull(Num::parseIntOrNull('abc'));
        $this->assertNull(Num::parseIntOrNull(''));
        $this->assertNull(Num::parseIntOrNull('12.5'));
        $this->assertNull(Num::parseIntOrNull('2', 2));
    }

    public function testParseIntRejectsInvalidBase(): void {
        $this->expectException(RuntimeException::class);
        Num::parseIntOrNull('1', 37);
    }

    public function testParseFloat(): void {
        $this->assertSame(3.14, Num::parseFloat('3.14'));
        $this->assertSame(-3.14, Num::parseFloat('-3.14'));
        $this->assertSame(1.5e3, Num::parseFloat('1.5e3'));
    }

    public function testParseFloatThrowsOnInvalid(): void {
        $this->expectException(RuntimeException::class);
        Num::parseFloat('abc');
    }

    public function testParseFloatOrNullReturnsNullOnInvalid(): void {
        $this->assertNull(Num::parseFloatOrNull('abc'));
        $this->assertNull(Num::parseFloatOrNull(''));
    }

    public function testClamp(): void {
        $this->assertSame(5, Num::clamp(5, 0, 10));
        $this->assertSame(0, Num::clamp(-3, 0, 10));
        $this->assertSame(10, Num::clamp(15, 0, 10));
    }

    public function testClampRejectsMinGreaterThanMax(): void {
        $this->expectException(RuntimeException::class);
        Num::clamp(5, 10, 0);
    }

    public function testInRange(): void {
        $this->assertTrue(Num::inRange(5, 0, 10));
        $this->assertTrue(Num::inRange(0, 0, 10));
        $this->assertTrue(Num::inRange(10, 0, 10));
        $this->assertFalse(Num::inRange(-1, 0, 10));
        $this->assertFalse(Num::inRange(11, 0, 10));
    }

    public function testRound(): void {
        $this->assertSame(3.0, Num::round(2.5));
        $this->assertSame(2.5, Num::round(2.5, 1));
        $this->assertSame(2.0, Num::round(2.5, 0, RoundingMode::HalfTowardsZero));
    }

    public function testFormat(): void {
        $this->assertSame('1,234.50', Num::format(1234.5));
        $this->assertSame('1.234,50', Num::format(1234.5, 2, ',', '.'));
        $this->assertSame('1,234', Num::format(1234, 0));
    }

    public function testSumAvgMinMax(): void {
        $this->assertSame(10, Num::sum([1, 2, 3, 4]));
        $this->assertSame(10.5, Num::sum([1, 2, 3, 4.5]));
        $this->assertSame(2.5, Num::avg([1, 2, 3, 4]));
        $this->assertSame(1, Num::min([3, 1, 2]));
        $this->assertSame(3, Num::max([3, 1, 2]));
    }

    public function testAvgThrowsOnEmpty(): void {
        $this->expectException(RuntimeException::class);
        Num::avg([]);
    }

    public function testMinThrowsOnEmpty(): void {
        $this->expectException(RuntimeException::class);
        Num::min([]);
    }

    public function testMaxThrowsOnEmpty(): void {
        $this->expectException(RuntimeException::class);
        Num::max([]);
    }

    public function testAbsSign(): void {
        $this->assertSame(5, Num::abs(-5));
        $this->assertSame(5.5, Num::abs(-5.5));
        $this->assertSame(1, Num::sign(5));
        $this->assertSame(-1, Num::sign(-5));
        $this->assertSame(0, Num::sign(0));
    }

    public function testPow(): void {
        $this->assertSame(8, Num::pow(2, 3));
        $this->assertSame(0.25, Num::pow(2, -2));
    }

    public function testSqrt(): void {
        $this->assertSame(4.0, Num::sqrt(16));
        $this->assertSame(1.5, Num::sqrt(2.25));
    }

    public function testSqrtRejectsNegative(): void {
        $this->expectException(RuntimeException::class);
        Num::sqrt(-1);
    }

    public function testFloorAndCeil(): void {
        $this->assertSame(2.0, Num::floor(2.9));
        $this->assertSame(-3.0, Num::floor(-2.1));
        $this->assertSame(3.0, Num::ceil(2.1));
        $this->assertSame(-2.0, Num::ceil(-2.9));
        $this->assertSame(2.4, Num::floor(2.49, 1));
        $this->assertSame(2.5, Num::ceil(2.41, 1));
    }

    public function testModFollowsDividendSign(): void {
        $this->assertSame(1, Num::mod(7, 3));
        $this->assertSame(-1, Num::mod(-7, 3));
        $this->assertSame(0.5, Num::mod(2.5, 1.0));
    }

    public function testModRejectsZeroDivisor(): void {
        $this->expectException(RuntimeException::class);
        Num::mod(5, 0);
    }

    public function testParseNumberReturnsBigNumber(): void {
        $n = Num::parseNumber('123456789012345678901234567890.5');
        $this->assertInstanceOf(Number::class, $n);
        $this->assertSame('123456789012345678901234567890.5', (string) $n);
    }

    public function testParseNumberOrNullRejectsNonNumeric(): void {
        $this->assertNull(Num::parseNumberOrNull('abc'));
        $this->assertNull(Num::parseNumberOrNull(''));
        $this->assertNull(Num::parseNumberOrNull('1e10'));
    }

    public function testParseNumberThrowsOnInvalid(): void {
        $this->expectException(RuntimeException::class);
        Num::parseNumber('xyz');
    }

    public function testIsNumericRecognisesBcMathNumber(): void {
        $this->assertTrue(Num::isNumeric(new Number('1.5')));
    }

    public function testSumWidensToNumber(): void {
        $result = Num::sum([1, 2, new Number('0.5')]);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('3.5', (string) $result);
    }

    public function testAvgWidensToNumber(): void {
        $result = Num::avg([new Number('1'), new Number('2'), new Number('3')]);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('2', (string) $result);
    }

    public function testMinMaxPropagateNumber(): void {
        $a = new Number('1.5');
        $b = new Number('2.5');
        $this->assertSame($a, Num::min([$b, $a]));
        $this->assertSame($b, Num::max([$a, $b]));
    }

    public function testAbsAndSignWithNumber(): void {
        $this->assertSame('5', (string) Num::abs(new Number('-5')));
        $this->assertSame(-1, Num::sign(new Number('-3.2')));
        $this->assertSame(1, Num::sign(new Number('3.2')));
        $this->assertSame(0, Num::sign(new Number('0')));
    }

    public function testClampInRangeWithNumber(): void {
        $this->assertSame('5', (string) Num::clamp(new Number('5'), new Number('0'), new Number('10')));
        $this->assertEquals(new Number('0'), Num::clamp(new Number('-3'), new Number('0'), new Number('10')));
        $this->assertTrue(Num::inRange(new Number('5'), new Number('0'), new Number('10')));
        $this->assertFalse(Num::inRange(new Number('-1'), new Number('0'), new Number('10')));
    }

    public function testPowSqrtFloorCeilWithNumber(): void {
        $this->assertInstanceOf(Number::class, Num::pow(new Number('2'), new Number('10')));
        $this->assertSame('1024', (string) Num::pow(new Number('2'), 10));
        $this->assertSame('1.4142135623', (string) Num::sqrt(new Number('2')));
        $this->assertEquals(new Number('2'), Num::floor(new Number('2.9')));
        $this->assertEquals(new Number('3'), Num::ceil(new Number('2.1')));
        $this->assertEquals(new Number('2.4'), Num::floor(new Number('2.49'), 1));
        $this->assertEquals(new Number('2.5'), Num::ceil(new Number('2.41'), 1));
    }

    public function testModWithNumber(): void {
        $this->assertEquals(new Number('1'), Num::mod(new Number('7'), new Number('3')));
        $this->assertEquals(new Number('-1'), Num::mod(new Number('-7'), new Number('3')));
    }

    public function testRoundPreservesNumber(): void {
        $result = Num::round(new Number('1.2345'), 2);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame('1.23', (string) $result);
    }

    public function testFormatWithNumberPreservesPrecision(): void {
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
}
