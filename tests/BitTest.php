<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Bit;

/**
 * @internal
 *
 * @coversNothing
 */
final class BitTest extends TestCase
{
    public function testSet(): void
    {
        $this->assertSame(0b0001, Bit::set(0, 0));
        $this->assertSame(0b0100, Bit::set(0, 2));
        $this->assertSame(0b0111, Bit::set(0b0011, 2));
    }

    public function testUnset(): void
    {
        $this->assertSame(0b0010, Bit::unset(0b0011, 0));
        $this->assertSame(0b0000, Bit::unset(0b0001, 0));
    }

    public function testToggle(): void
    {
        $this->assertSame(0b0001, Bit::toggle(0b0000, 0));
        $this->assertSame(0b0000, Bit::toggle(0b0001, 0));
    }

    public function testHas(): void
    {
        $this->assertTrue(Bit::has(0b0010, 1));
        $this->assertFalse(Bit::has(0b0010, 0));
    }

    public function testCount(): void
    {
        $this->assertSame(0, Bit::count(0));
        $this->assertSame(1, Bit::count(1));
        $this->assertSame(2, Bit::count(0b0101));
        $this->assertSame(4, Bit::count(0b1111));
    }

    public function testLeadingZeros(): void
    {
        $this->assertSame(PHP_INT_SIZE * 8, Bit::leadingZeros(0));
        $this->assertSame(PHP_INT_SIZE * 8 - 1, Bit::leadingZeros(1));
        $this->assertSame(PHP_INT_SIZE * 8 - 3, Bit::leadingZeros(0b100));
    }

    public function testTrailingZeros(): void
    {
        $this->assertSame(PHP_INT_SIZE * 8, Bit::trailingZeros(0));
        $this->assertSame(0, Bit::trailingZeros(1));
        $this->assertSame(2, Bit::trailingZeros(0b100));
        $this->assertSame(3, Bit::trailingZeros(0b1000));
    }

    public function testRejectsNegativeBitIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bit::set(0, -1);
    }

    public function testRejectsBitIndexOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bit::set(0, PHP_INT_SIZE * 8);
    }

    public function testToStr(): void
    {
        $this->assertSame('0', Bit::toStr(0));
        $this->assertSame('101', Bit::toStr(5));
        $this->assertSame('00000101', Bit::toStr(5, 8));
        $this->assertSame('101', Bit::toStr(5, 2));
    }

    public function testToStrNegativeIsTwosComplement(): void
    {
        $this->assertSame(PHP_INT_SIZE * 8, strlen(Bit::toStr(-1)));
        $this->assertSame(str_repeat('1', PHP_INT_SIZE * 8), Bit::toStr(-1));
    }

    public function testFromStr(): void
    {
        $this->assertSame(0, Bit::fromStr('0'));
        $this->assertSame(5, Bit::fromStr('101'));
        $this->assertSame(5, Bit::fromStr('00000101'));
    }

    public function testFromStrRejectsInvalidChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bit::fromStr('102');
    }

    public function testFromStrRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bit::fromStr('');
    }

    public function testFromStrRejectsValueExceedingIntRange(): void
    {
        // one more '1' bit than the platform int width → bindec overflows to a float
        $this->expectException(InvalidArgumentException::class);
        Bit::fromStr(str_repeat('1', PHP_INT_SIZE * 8 + 1));
    }

    public function testToFromStrRoundTrip(): void
    {
        foreach ([0, 1, 5, 255, 1024, PHP_INT_MAX] as $value) {
            $this->assertSame($value, Bit::fromStr(Bit::toStr($value)));
        }
    }

    public function testRotateLeft(): void
    {
        $this->assertSame(2, Bit::rotateLeft(1, 1));
        $this->assertSame(20, Bit::rotateLeft(5, 2));        // 0b101 → 0b10100, no wrap
        $this->assertSame(0, Bit::rotateLeft(0, 5));
        $this->assertSame(42, Bit::rotateLeft(42, 0));       // no-op
        // the top bit wraps around to the bottom
        $this->assertSame(1, Bit::rotateLeft(PHP_INT_MIN, 1));
    }

    public function testRotateRight(): void
    {
        $this->assertSame(1, Bit::rotateRight(2, 1));
        $this->assertSame(5, Bit::rotateRight(20, 2));
        $this->assertSame(0, Bit::rotateRight(0, 5));
        $this->assertSame(42, Bit::rotateRight(42, 0));
        // the bottom bit wraps around to the top
        $this->assertSame(PHP_INT_MIN, Bit::rotateRight(1, 1));
    }

    public function testRotateIsModuloBitWidth(): void
    {
        $bits = PHP_INT_SIZE * 8;
        foreach ([0xABCD, 1, PHP_INT_MIN, PHP_INT_MAX, -1] as $value) {
            $this->assertSame($value, Bit::rotateLeft($value, $bits));      // full turn
            $this->assertSame($value, Bit::rotateRight($value, $bits));
            $this->assertSame(Bit::rotateLeft($value, 3), Bit::rotateLeft($value, $bits + 3));
            $this->assertSame(Bit::rotateLeft($value, 1), Bit::rotateRight($value, -1)); // negative
        }
    }

    public function testRotateLeftRightAreInverse(): void
    {
        foreach ([0xABCD, 1, 255, PHP_INT_MIN, PHP_INT_MAX, -1] as $value) {
            foreach ([1, 7, 13, 31] as $by) {
                $this->assertSame($value, Bit::rotateRight(Bit::rotateLeft($value, $by), $by));
                $this->assertSame(Bit::rotateLeft($value, $by), Bit::rotateRight($value, PHP_INT_SIZE * 8 - $by));
            }
        }
    }
}
