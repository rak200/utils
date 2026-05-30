<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Enum;
use RuntimeException;
use UnitEnum;

final class EnumTest extends TestCase {
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable {
        yield 'pure case'    => [EnumSuit::Hearts, true];
        yield 'backed case'  => [EnumStatus::Active, true];
        yield 'class-string' => [EnumSuit::class, false];
        yield 'string'       => ['Hearts', false];
        yield 'null'         => [null, false];
        yield 'int'          => [0, false];
        yield 'object'       => [new \stdClass(), false];
    }

    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void {
        $this->assertSame($expected, Enum::is($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isBackedProvider(): iterable {
        yield 'int-backed'    => [EnumPriority::Low, true];
        yield 'string-backed' => [EnumStatus::Active, true];
        yield 'pure case'     => [EnumSuit::Hearts, false];
        yield 'class-string'  => [EnumStatus::class, false];
        yield 'null'          => [null, false];
    }

    #[DataProvider('isBackedProvider')]
    public function testIsBacked(mixed $value, bool $expected): void {
        $this->assertSame($expected, Enum::isBacked($value));
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

    /**
     * @return iterable<string, array{UnitEnum, bool}>
     */
    public static function isBackedIntProvider(): iterable {
        yield 'int Low'       => [EnumPriority::Low, true];
        yield 'int High'      => [EnumPriority::High, true];
        yield 'string-backed' => [EnumStatus::Active, false];
        yield 'pure'          => [EnumSuit::Hearts, false];
    }

    #[DataProvider('isBackedIntProvider')]
    public function testIsBackedInt(UnitEnum $case, bool $expected): void {
        $this->assertSame($expected, Enum::isBackedInt($case));
    }

    /**
     * @return iterable<string, array{UnitEnum, bool}>
     */
    public static function isBackedStrProvider(): iterable {
        yield 'string Active'   => [EnumStatus::Active, true];
        yield 'string Inactive' => [EnumStatus::Inactive, true];
        yield 'int-backed'      => [EnumPriority::Low, false];
        yield 'pure'            => [EnumSuit::Hearts, false];
    }

    #[DataProvider('isBackedStrProvider')]
    public function testIsBackedStr(UnitEnum $case, bool $expected): void {
        $this->assertSame($expected, Enum::isBackedStr($case));
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
