<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use BackedEnum;
use InvalidArgumentException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Enum;
use stdClass;
use UnderflowException;
use UnitEnum;

/**
 * @internal
 *
 * @coversNothing
 */
final class EnumTest extends TestCase
{
    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Enum::is($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable
    {
        yield 'pure case' => [EnumSuit::Hearts, true];

        yield 'backed case' => [EnumStatus::Active, true];

        yield 'class-string' => [EnumSuit::class, false];

        yield 'string' => ['Hearts', false];

        yield 'null' => [null, false];

        yield 'int' => [0, false];

        yield 'object' => [new stdClass(), false];
    }

    #[DataProvider('isBackedProvider')]
    public function testIsBacked(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Enum::isBacked($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isBackedProvider(): iterable
    {
        yield 'int-backed' => [EnumPriority::Low, true];

        yield 'string-backed' => [EnumStatus::Active, true];

        yield 'pure case' => [EnumSuit::Hearts, false];

        yield 'class-string' => [EnumStatus::class, false];

        yield 'null' => [null, false];
    }

    public function testNames(): void
    {
        $this->assertSame(['Hearts', 'Spades'], Enum::names(EnumSuit::class));
        $this->assertSame(['Active', 'Inactive'], Enum::names(EnumStatus::class));
    }

    public function testValues(): void
    {
        $this->assertSame(['active', 'inactive'], Enum::values(EnumStatus::class));
    }

    public function testValuesThrowsOnPureEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Enum::values(EnumSuit::class);
    }

    public function testFromName(): void
    {
        $this->assertSame(EnumSuit::Hearts, Enum::fromName(EnumSuit::class, 'Hearts'));
        $this->assertSame(EnumStatus::Active, Enum::fromName(EnumStatus::class, 'Active'));
    }

    public function testFromNameThrowsOnMiss(): void
    {
        $this->expectException(OutOfBoundsException::class);
        Enum::fromName(EnumSuit::class, 'Clubs');
    }

    public function testTryFromName(): void
    {
        $this->assertSame(EnumSuit::Spades, Enum::tryFromName(EnumSuit::class, 'Spades'));
        $this->assertNull(Enum::tryFromName(EnumSuit::class, 'Clubs'));
        $this->assertNull(Enum::tryFromName(EnumStatus::class, 'Unknown'));
    }

    public function testFromValue(): void
    {
        $this->assertSame(EnumPriority::Low, Enum::fromValue(EnumPriority::class, 1));
        $this->assertSame(EnumPriority::High, Enum::fromValue(EnumPriority::class, '10'));
        $this->assertSame(EnumStatus::Active, Enum::fromValue(EnumStatus::class, 'active'));
    }

    public function testFromValueThrowsOnPureEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs(EnumSuit::class . ' is not a backed enum.');
        Enum::fromValue(EnumSuit::class, 'Hearts');
    }

    public function testFromValueThrowsOnMiss(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageIs(EnumPriority::class . ' has no case with value "7".');
        Enum::fromValue(EnumPriority::class, 7);
    }

    public function testFromValueThrowsOnAnUnrenderableValue(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageIs(EnumPriority::class . ' has no case with value "array".');
        Enum::fromValue(EnumPriority::class, []);
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     */
    #[DataProvider('tryFromValueProvider')]
    public function testTryFromValue(string $enumClass, mixed $value, ?UnitEnum $expected): void
    {
        $this->assertSame($expected, Enum::tryFromValue($enumClass, $value));
    }

    /**
     * @return iterable<string, array{class-string<UnitEnum>, mixed, null|UnitEnum}>
     */
    public static function tryFromValueProvider(): iterable
    {
        yield 'int against int-backed' => [EnumPriority::class, 1, EnumPriority::Low];

        yield 'numeric string against int-backed' => [EnumPriority::class, '10', EnumPriority::High];

        yield 'signed numeric string against int-backed' => [EnumPriority::class, '+1', EnumPriority::Low];

        yield 'string against string-backed' => [EnumStatus::class, 'active', EnumStatus::Active];

        yield 'int against string-backed' => [EnumNumericStr::class, 2, EnumNumericStr::Two];

        yield 'numeric string against string-backed' => [EnumNumericStr::class, '2', EnumNumericStr::Two];

        yield 'unknown int' => [EnumPriority::class, 7, null];

        yield 'unknown string' => [EnumStatus::class, 'archived', null];

        yield 'non-numeric string against int-backed' => [EnumPriority::class, 'Low', null];

        yield 'whitespace-padded numeric string' => [EnumPriority::class, ' 1 ', null];

        yield 'decimal string against int-backed' => [EnumPriority::class, '1.0', null];

        yield 'case name, not value' => [EnumStatus::class, 'Active', null];

        yield 'pure enum' => [EnumSuit::class, 'Hearts', null];

        yield 'backed enum with no cases' => [EnumEmptyBacked::class, 1, null];

        // the backing type is read off the first case, so an enum with exactly
        // one case pins that index
        yield 'single-case backed enum' => [EnumSolo::class, 1, EnumSolo::Only];

        // a non-scalar against a string-backed enum must miss, not be cast:
        // (string) 2.0 would match EnumNumericStr::Two
        yield 'float against string-backed' => [EnumNumericStr::class, 2.0, null];

        yield 'float' => [EnumPriority::class, 1.0, null];

        yield 'bool' => [EnumPriority::class, true, null];

        yield 'null' => [EnumPriority::class, null, null];

        yield 'array' => [EnumPriority::class, [1], null];

        yield 'enum case itself' => [EnumPriority::class, EnumPriority::Low, null];
    }

    public function testRandomReturnsAValidCase(): void
    {
        $cases = EnumSuit::cases();
        for ($i = 0; $i < 10; ++$i) {
            $this->assertContains(Enum::random(EnumSuit::class), $cases);
        }
    }

    public function testRandomThrowsOnEmptyEnum(): void
    {
        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessage(EnumEmpty::class . ' has no cases.');
        Enum::random(EnumEmpty::class);
    }

    public function testToArrayBackedReturnsNameToValueMap(): void
    {
        $this->assertSame(
            ['Active' => 'active', 'Inactive' => 'inactive'],
            Enum::toArray(EnumStatus::class),
        );
    }

    public function testToArrayPureReturnsNameToNameMap(): void
    {
        $this->assertSame(
            ['Hearts' => 'Hearts', 'Spades' => 'Spades'],
            Enum::toArray(EnumSuit::class),
        );
    }

    public function testScalarReturnsNameForPureCase(): void
    {
        $this->assertSame('Hearts', Enum::scalar(EnumSuit::Hearts));
        $this->assertSame('Spades', Enum::scalar(EnumSuit::Spades));
    }

    public function testScalarReturnsValueForStringBackedCase(): void
    {
        $this->assertSame('active', Enum::scalar(EnumStatus::Active));
        $this->assertSame('inactive', Enum::scalar(EnumStatus::Inactive));
    }

    public function testScalarReturnsValueForIntBackedCase(): void
    {
        $this->assertSame(1, Enum::scalar(EnumPriority::Low));
        $this->assertSame(10, Enum::scalar(EnumPriority::High));
    }

    #[DataProvider('isBackedIntProvider')]
    public function testIsBackedInt(UnitEnum $case, bool $expected): void
    {
        $this->assertSame($expected, Enum::isBackedInt($case));
    }

    /**
     * @return iterable<string, array{UnitEnum, bool}>
     */
    public static function isBackedIntProvider(): iterable
    {
        yield 'int Low' => [EnumPriority::Low, true];

        yield 'int High' => [EnumPriority::High, true];

        yield 'string-backed' => [EnumStatus::Active, false];

        yield 'pure' => [EnumSuit::Hearts, false];
    }

    #[DataProvider('isBackedStrProvider')]
    public function testIsBackedStr(UnitEnum $case, bool $expected): void
    {
        $this->assertSame($expected, Enum::isBackedStr($case));
    }

    /**
     * @return iterable<string, array{UnitEnum, bool}>
     */
    public static function isBackedStrProvider(): iterable
    {
        yield 'string Active' => [EnumStatus::Active, true];

        yield 'string Inactive' => [EnumStatus::Inactive, true];

        yield 'int-backed' => [EnumPriority::Low, false];

        yield 'pure' => [EnumSuit::Hearts, false];
    }

    #[DataProvider('isIntProvider')]
    public function testIsInt(BackedEnum $case, bool $expected): void
    {
        $this->assertSame($expected, Enum::isInt($case));
    }

    /**
     * @return iterable<string, array{BackedEnum, bool}>
     */
    public static function isIntProvider(): iterable
    {
        yield 'int Low' => [EnumPriority::Low, true];

        yield 'int High' => [EnumPriority::High, true];

        yield 'string-backed' => [EnumStatus::Active, false];
    }

    #[DataProvider('isStrProvider')]
    public function testIsStr(BackedEnum $case, bool $expected): void
    {
        $this->assertSame($expected, Enum::isStr($case));
    }

    /**
     * @return iterable<string, array{BackedEnum, bool}>
     */
    public static function isStrProvider(): iterable
    {
        yield 'string Active' => [EnumStatus::Active, true];

        yield 'string Inactive' => [EnumStatus::Inactive, true];

        yield 'int-backed' => [EnumPriority::Low, false];
    }

    #[DataProvider('intOrNullProvider')]
    public function testIntOrNull(UnitEnum $case, ?int $expected): void
    {
        $this->assertSame($expected, Enum::intOrNull($case));
    }

    /**
     * @return iterable<string, array{UnitEnum, null|int}>
     */
    public static function intOrNullProvider(): iterable
    {
        yield 'int Low' => [EnumPriority::Low, 1];

        yield 'int High' => [EnumPriority::High, 10];

        yield 'string-backed' => [EnumStatus::Active, null];

        yield 'pure' => [EnumSuit::Hearts, null];
    }

    #[DataProvider('strOrNullProvider')]
    public function testStrOrNull(UnitEnum $case, ?string $expected): void
    {
        $this->assertSame($expected, Enum::strOrNull($case));
    }

    /**
     * @return iterable<string, array{UnitEnum, null|string}>
     */
    public static function strOrNullProvider(): iterable
    {
        yield 'string Active' => [EnumStatus::Active, 'active'];

        yield 'string Inactive' => [EnumStatus::Inactive, 'inactive'];

        yield 'int-backed' => [EnumPriority::Low, null];

        yield 'pure' => [EnumSuit::Hearts, null];
    }
}

enum EnumSuit
{
    case Hearts;
    case Spades;
}

enum EnumStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum EnumPriority: int
{
    case Low = 1;
    case High = 10;
}

enum EnumEmpty {}

enum EnumEmptyBacked: int {}

enum EnumNumericStr: string
{
    case Two = '2';
    case Three = '3';
}

enum EnumSolo: int
{
    case Only = 1;
}
