<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Enum;
use RuntimeException;

final class EnumTest extends TestCase {
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
}

enum EnumSuit {
    case Hearts;
    case Spades;
}

enum EnumStatus: string {
    case Active = 'active';
    case Inactive = 'inactive';
}
