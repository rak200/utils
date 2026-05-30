<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Enum;
use RuntimeException;

final class EnumTest extends TestCase {
    public function testIs(): void {
        $this->assertTrue(Enum::is(EnumSuit::Hearts));
        $this->assertTrue(Enum::is(EnumStatus::Active));
        $this->assertFalse(Enum::is(EnumSuit::class));
        $this->assertFalse(Enum::is('Hearts'));
        $this->assertFalse(Enum::is(null));
        $this->assertFalse(Enum::is(0));
        $this->assertFalse(Enum::is(new \stdClass()));
    }

    public function testNames(): void {
        $this->assertSame(['Hearts', 'Spades'], Enum::names(EnumSuit::class));
        $this->assertSame(['Active', 'Inactive'], Enum::names(EnumStatus::class));
    }

    public function testValues(): void {
        $this->assertSame(['active', 'inactive'], Enum::values(EnumStatus::class));
    }

    public function testValuesThrowsOnPureEnum(): void {
        $this->expectException(RuntimeException::class);
        Enum::values(EnumSuit::class);
    }

    public function testFromName(): void {
        $this->assertSame(EnumSuit::Hearts, Enum::fromName(EnumSuit::class, 'Hearts'));
        $this->assertSame(EnumStatus::Active, Enum::fromName(EnumStatus::class, 'Active'));
    }

    public function testFromNameThrowsOnMiss(): void {
        $this->expectException(RuntimeException::class);
        Enum::fromName(EnumSuit::class, 'Clubs');
    }

    public function testTryFromName(): void {
        $this->assertSame(EnumSuit::Spades, Enum::tryFromName(EnumSuit::class, 'Spades'));
        $this->assertNull(Enum::tryFromName(EnumSuit::class, 'Clubs'));
        $this->assertNull(Enum::tryFromName(EnumStatus::class, 'Unknown'));
    }

    public function testRandomReturnsAValidCase(): void {
        $cases = EnumSuit::cases();
        for ($i = 0; $i < 10; $i++) {
            $this->assertContains(Enum::random(EnumSuit::class), $cases);
        }
    }

    public function testToArrayBackedReturnsNameToValueMap(): void {
        $this->assertSame(
            ['Active' => 'active', 'Inactive' => 'inactive'],
            Enum::toArray(EnumStatus::class),
        );
    }

    public function testToArrayPureReturnsNameToNameMap(): void {
        $this->assertSame(
            ['Hearts' => 'Hearts', 'Spades' => 'Spades'],
            Enum::toArray(EnumSuit::class),
        );
    }

    public function testScalarReturnsNameForPureCase(): void {
        $this->assertSame('Hearts', Enum::scalar(EnumSuit::Hearts));
        $this->assertSame('Spades', Enum::scalar(EnumSuit::Spades));
    }

    public function testScalarReturnsValueForStringBackedCase(): void {
        $this->assertSame('active', Enum::scalar(EnumStatus::Active));
        $this->assertSame('inactive', Enum::scalar(EnumStatus::Inactive));
    }

    public function testScalarReturnsValueForIntBackedCase(): void {
        $this->assertSame(1, Enum::scalar(EnumPriority::Low));
        $this->assertSame(10, Enum::scalar(EnumPriority::High));
    }

    public function testIsBackedInt(): void {
        $this->assertTrue(Enum::isBackedInt(EnumPriority::Low));
        $this->assertTrue(Enum::isBackedInt(EnumPriority::High));
        $this->assertFalse(Enum::isBackedInt(EnumStatus::Active));
        $this->assertFalse(Enum::isBackedInt(EnumSuit::Hearts));
    }

    public function testIsBackedStr(): void {
        $this->assertTrue(Enum::isBackedStr(EnumStatus::Active));
        $this->assertTrue(Enum::isBackedStr(EnumStatus::Inactive));
        $this->assertFalse(Enum::isBackedStr(EnumPriority::Low));
        $this->assertFalse(Enum::isBackedStr(EnumSuit::Hearts));
    }
}

enum EnumSuit {
    case Hearts;
    case Spades;
}

enum EnumStatus: string {
    case Active = 'active';
    case Inactive = 'inactive';
}

enum EnumPriority: int {
    case Low = 1;
    case High = 10;
}
