<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Bit;
use RuntimeException;

final class BitTest extends TestCase {
    public function testSet(): void {
        $this->assertSame(0b0001, Bit::set(0, 0));
        $this->assertSame(0b0100, Bit::set(0, 2));
        $this->assertSame(0b0111, Bit::set(0b0011, 2));
    }

    public function testUnset(): void {
        $this->assertSame(0b0010, Bit::unset(0b0011, 0));
        $this->assertSame(0b0000, Bit::unset(0b0001, 0));
    }

    public function testToggle(): void {
        $this->assertSame(0b0001, Bit::toggle(0b0000, 0));
        $this->assertSame(0b0000, Bit::toggle(0b0001, 0));
    }

    public function testHas(): void {
        $this->assertTrue(Bit::has(0b0010, 1));
        $this->assertFalse(Bit::has(0b0010, 0));
    }

    public function testCount(): void {
        $this->assertSame(0, Bit::count(0));
        $this->assertSame(1, Bit::count(1));
        $this->assertSame(2, Bit::count(0b0101));
        $this->assertSame(4, Bit::count(0b1111));
    }

    public function testLeadingZeros(): void {
        $this->assertSame(PHP_INT_SIZE * 8, Bit::leadingZeros(0));
        $this->assertSame(PHP_INT_SIZE * 8 - 1, Bit::leadingZeros(1));
        $this->assertSame(PHP_INT_SIZE * 8 - 3, Bit::leadingZeros(0b100));
    }

    public function testTrailingZeros(): void {
        $this->assertSame(PHP_INT_SIZE * 8, Bit::trailingZeros(0));
        $this->assertSame(0, Bit::trailingZeros(1));
        $this->assertSame(2, Bit::trailingZeros(0b100));
        $this->assertSame(3, Bit::trailingZeros(0b1000));
    }

    public function testRejectsNegativeBitIndex(): void {
        $this->expectException(RuntimeException::class);
        Bit::set(0, -1);
    }

    public function testRejectsBitIndexOutOfRange(): void {
        $this->expectException(RuntimeException::class);
        Bit::set(0, PHP_INT_SIZE * 8);
    }
}
