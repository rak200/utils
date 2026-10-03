<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use BcMath\Number;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\EmptySourceException;
use Rak200\Utils\Exception\MalformedArgumentException;
use Rak200\Utils\Num;
use RoundingMode;

/**
 * @internal
 */
#[CoversClass(Num::class)]
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
        $this->expectException(MalformedArgumentException::class);
        Num::parseInt('abc');
    }

    public function testParseIntOrNullReturnsNullOnInvalid(): void
    {
        $this->assertNull(Num::parseIntOrNull('abc'));
        $this->assertNull(Num::parseIntOrNull(''));
        $this->assertNull(Num::parseIntOrNull('12.5'));
        $this->assertNull(Num::parseIntOrNull('2', 2));
    }

    public function testParseIntOrNullDefaultsToBaseTen(): void
    {
        // Every other default-base assertion here is a rejection, and 'abc', '' and
        // ' 42 ' are null in bases 9, 10 and 11 alike. A successful parse is what pins
        // the default: 42 reads as 38 in base 9 and as 46 in base 11.
        $this->assertSame(42, Num::parseIntOrNull('42'));
    }

    public function testParseIntOrNullRejectsSurroundingWhitespace(): void
    {
        $this->assertNull(Num::parseIntOrNull(' 42 '));
        $this->assertNull(Num::parseIntOrNull(' 42'));
        $this->assertNull(Num::parseIntOrNull("42\n"));
    }

    public function testParseIntRejectsInvalidBase(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Num::parseIntOrNull('1', 37);
    }

    #[DataProvider('intBoundaryProvider')]
    public function testParseIntOrNullReachesBothEndsOfTheIntRange(string $value, int $base, ?int $expected): void
    {
        // The ends are where an int stops being one. PHP_INT_MIN's magnitude has no positive
        // int, and one step past either end is a float — which the return type refused with a
        // TypeError, escaping every catch of this library's own exception.
        $this->assertSame($expected, Num::parseIntOrNull($value, $base));
    }

    /**
     * @return iterable<string, array{string, int, ?int}>
     */
    public static function intBoundaryProvider(): iterable
    {
        yield 'the largest int' => ['9223372036854775807', 10, PHP_INT_MAX];

        yield 'the largest int, signed' => ['+9223372036854775807', 10, PHP_INT_MAX];

        yield 'the smallest int' => ['-9223372036854775808', 10, PHP_INT_MIN];

        yield 'one past the largest' => ['9223372036854775808', 10, null];

        yield 'one past the smallest' => ['-9223372036854775809', 10, null];

        yield 'a digit past the largest' => ['92233720368547758070', 10, null];

        yield 'a digit past the smallest' => ['-92233720368547758080', 10, null];

        yield 'the largest int in hex' => ['7fffffffffffffff', 16, PHP_INT_MAX];

        yield 'the smallest int in hex' => ['-8000000000000000', 16, PHP_INT_MIN];

        yield 'one past the largest in hex' => ['8000000000000000', 16, null];

        yield 'minus zero' => ['-0', 10, 0];
    }

    public function testParseIntRejectsAValueOutsideTheIntRangeWithItsOwnException(): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "9223372036854775808" as integer in base 10.');
        Num::parseInt('9223372036854775808');
    }

    public function testParseIntInvertsToBaseAtBothEndsOfTheIntRange(): void
    {
        // toBase() names parseInt() as its inverse, and the ends of the range are where that
        // promise was broken.
        foreach ([2, 7, 10, 16, 36] as $base) {
            $this->assertSame(PHP_INT_MIN, Num::parseInt(Num::toBase(PHP_INT_MIN, $base), $base));
            $this->assertSame(PHP_INT_MAX, Num::parseInt(Num::toBase(PHP_INT_MAX, $base), $base));
        }
    }

    public function testParseFloat(): void
    {
        $this->assertSame(3.14, Num::parseFloat('3.14'));
        $this->assertSame(-3.14, Num::parseFloat('-3.14'));
        $this->assertSame(1.5e3, Num::parseFloat('1.5e3'));
    }

    public function testParseFloatThrowsOnInvalid(): void
    {
        $this->expectException(MalformedArgumentException::class);
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

    #[DataProvider('toStrRoundTripProvider')]
    public function testToStrRoundTripsEveryFiniteFloat(float $value): void
    {
        $this->assertSame($value, Num::parseFloat(Num::toStr($value)));
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function toStrRoundTripProvider(): iterable
    {
        yield 'one tenth' => [0.1];

        yield 'the classic 0.1 + 0.2' => [0.1 + 0.2];

        yield 'one third' => [1 / 3];

        yield 'epsilon' => [PHP_FLOAT_EPSILON];

        yield 'smallest normal' => [PHP_FLOAT_MIN];

        yield 'largest finite' => [PHP_FLOAT_MAX];

        yield 'integral float' => [1.0];

        yield 'negative zero' => [-0.0];

        yield 'large exponent' => [1e100];
    }

    public function testToStrKeepsTheSignOfNegativeZero(): void
    {
        // -0.0 === 0.0 is true, so the round-trip assertion above cannot see a
        // lost sign. Division is what exposes it: 1 / -0.0 is -INF.
        $this->assertTrue(fdiv(1, Num::parseFloat(Num::toStr(-0.0))) < 0);
    }

    public function testToStrIsExactWhereTheCastIsNot(): void
    {
        // The reason the method exists: the cast goes through `precision` (14
        // significant digits) and silently collapses distinct values.
        $this->assertSame('0.30000000000000004', Num::toStr(0.1 + 0.2));
        $this->assertSame('0.3', (string) (0.1 + 0.2));

        // An integral float keeps its marker, which the cast drops.
        $this->assertSame('1.0', Num::toStr(1.0));
        $this->assertSame('1', (string) 1.0);
    }

    public function testToStrPassesThroughIntAndNumber(): void
    {
        $this->assertSame('5', Num::toStr(5));
        $this->assertSame('-9223372036854775808', Num::toStr(PHP_INT_MIN));
        // A Number keeps its trailing zeros — its cast is already exact.
        $this->assertSame('1.500', Num::toStr(new Number('1.500')));
    }

    #[DataProvider('toStrNonFiniteProvider')]
    public function testToStrThrowsOnNonFiniteFloat(float $value, string $expectedMessage): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        Num::toStr($value);
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function toStrNonFiniteProvider(): iterable
    {
        // No string form of these reads back through parseFloat, so there is
        // no round-trippable answer to return.
        yield 'NAN' => [NAN, 'Cannot represent NAN as an exact string.'];

        yield 'INF' => [INF, 'Cannot represent INF as an exact string.'];

        yield 'negative INF' => [-INF, 'Cannot represent -INF as an exact string.'];
    }

    public function testClamp(): void
    {
        $this->assertSame(5, Num::clamp(5, 0, 10));
        $this->assertSame(0, Num::clamp(-3, 0, 10));
        $this->assertSame(10, Num::clamp(15, 0, 10));
    }

    public function testClampRejectsMinGreaterThanMax(): void
    {
        $this->expectException(MalformedArgumentException::class);
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

    #[DataProvider('inRangeAcrossFloatAndIntProvider')]
    public function testInRangeComparesAFloatAndAnIntAsTheValuesTheyAre(
        float|int|Number $value,
        float|int|Number $min,
        float|int|Number $max,
        bool $expected,
    ): void {
        // PHP turns the int into a float to compare the two, and past 2⁵³ that is a different
        // number: PHP_INT_MAX rounds up to 2⁶³, so a float that no int can hold read as inside
        // the int range, and the (int) cast the check guarded wrapped to PHP_INT_MIN.
        $this->assertSame($expected, Num::inRange($value, $min, $max));
    }

    /**
     * @return iterable<string, array{float|int|Number, float|int|Number, float|int|Number, bool}>
     */
    public static function inRangeAcrossFloatAndIntProvider(): iterable
    {
        yield '2⁶³, the first float past PHP_INT_MAX' => [2.0 ** 63, PHP_INT_MIN, PHP_INT_MAX, false];

        yield '-2⁶³, which is PHP_INT_MIN' => [-(2.0 ** 63), PHP_INT_MIN, PHP_INT_MAX, true];

        yield 'the first float below PHP_INT_MIN' => [-(2.0 ** 63) - 2048.0, PHP_INT_MIN, PHP_INT_MAX, false];

        yield 'the first float below PHP_INT_MIN, under an int bound' => [-(2.0 ** 63) - 2048.0, -(2.0 ** 64), 0, true];

        yield 'a float between float bounds' => [0.5, 0.0, 1.0, true];

        yield 'both ends of the int range, as ints' => [PHP_INT_MAX, PHP_INT_MIN, PHP_INT_MAX, true];

        yield 'PHP_INT_MAX under a 2⁶³ float bound' => [PHP_INT_MAX, 0, 2.0 ** 63, true];

        yield 'a float just below an int bound past 2⁵³' => [2.0 ** 62, 4611686018427387950, PHP_INT_MAX, false];

        yield 'an int just above a float bound past 2⁵³' => [4611686018427387950, 0, 2.0 ** 62, false];

        yield 'an int equal to a float bound past 2⁵³' => [4611686018427387904, 0, 2.0 ** 62, true];

        yield 'a fraction above an int bound' => [1.5, 0, 1, false];

        yield 'a fraction below a negative int bound' => [-1.5, -1, 0, false];

        yield 'a fraction between int bounds' => [0.5, 0, 1, true];

        yield 'NAN between float bounds' => [NAN, 0.0, 1.0, false];

        yield 'NAN between int bounds' => [NAN, PHP_INT_MIN, PHP_INT_MAX, false];

        yield 'a NAN lower bound' => [0.5, NAN, 1.0, false];

        yield 'a NAN upper bound' => [0.5, 0.0, NAN, false];

        yield 'an int against a NAN lower bound' => [1, NAN, 2, false];

        yield 'a Number between int bounds' => [new Number('5'), 1, 10, true];
    }

    #[DataProvider('inRangeAcrossNumberAndFloatProvider')]
    public function testInRangeReadsAFloatBesideANumberAsTheDecimalItStandsFor(
        float|int|Number $value,
        float|int|Number $min,
        float|int|Number $max,
        bool $expected,
    ): void {
        // BcMath converts a float as a parameter typed int|string would, so under strict types it
        // refuses one: the pair had no order, `<`, `==` and `>` all answered false, and `<=>` 1.
        $this->assertSame($expected, Num::inRange($value, $min, $max));
    }

    /**
     * @return iterable<string, array{float|int|Number, float|int|Number, float|int|Number, bool}>
     */
    public static function inRangeAcrossNumberAndFloatProvider(): iterable
    {
        yield 'a Number below a fractional float lower bound' => [new Number('0.5'), 0.9, new Number('2'), false];

        yield 'a Number under a fractional float upper bound' => [new Number('1.1'), 0.0, 1.2, true];

        yield 'a Number equal to the decimal a float reads as' => [new Number('0.1'), 0.1, 0.1, true];

        yield 'a Number under 0.1 + 0.2, which is not 0.3' => [new Number('0.30000000000000001'), 0.0, 0.1 + 0.2, true];

        yield 'a float between Number bounds' => [0.5, new Number('0'), new Number('1'), true];

        yield 'a float below a Number lower bound' => [0.5, new Number('0.9'), 2, false];

        yield 'a Number between infinite bounds' => [new Number('1'), -INF, INF, true];

        yield 'a Number against a NAN bound' => [new Number('1'), NAN, 2, false];
    }

    public function testClampHoldsAFloatPastAnIntBoundToThatBound(): void
    {
        $this->assertSame(PHP_INT_MAX, Num::clamp(2.0 ** 63, PHP_INT_MIN, PHP_INT_MAX));
        $this->assertSame(PHP_INT_MIN, Num::clamp(-(2.0 ** 63) - 2048.0, PHP_INT_MIN, 0));
        $this->assertSame(4611686018427387950, Num::clamp(2.0 ** 62, 4611686018427387950, PHP_INT_MAX));
        $this->assertSame(1, Num::clamp(1.5, 0, 1));
        $this->assertSame(-1, Num::clamp(-1.5, -1, 0));
        $this->assertNan(Num::clamp(NAN, 0, 1));
    }

    public function testClampRejectsAnIntMinAboveAFloatMaxPast2To53(): void
    {
        // 4611686018427387950 rounds to the float 2⁶², so the native comparison read the two
        // bounds as equal and the empty interval went through.
        $this->expectException(MalformedArgumentException::class);
        Num::clamp(0, 4611686018427387950, 2.0 ** 62);
    }

    public function testLerp(): void
    {
        $this->assertSame(5.0, Num::lerp(0, 10, 0.5));
        $this->assertSame(12.5, Num::lerp(10, 20, 0.25));
        $this->assertSame(15.0, Num::lerp(0, 10, 1.5));
        $this->assertSame(0, Num::lerp(0, 10, 0));
        $this->assertSame(10, Num::lerp(0, 10, 1));
    }

    public function testRemap(): void
    {
        $this->assertSame(50, Num::remap(5, 0, 10, 0, 100));
        $this->assertSame(0.0, Num::remap(0.5, 0, 1, -100, 100));
        $this->assertSame(1.2, Num::remap(120, 0, 100, 0, 1));
        $this->assertSame(-1, Num::remap(0, 50, 100, 0, 1));
    }

    public function testRemapRejectsEmptyInputRange(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Num::remap(5, 0, 0, 0, 100);
    }

    public function testAddSubMulDiv(): void
    {
        $this->assertSame(5, Num::add(2, 3));
        $this->assertSame(3, Num::sub(5, 2));
        $this->assertSame(10.0, Num::mul(4, 2.5));
        $this->assertSame(3.5, Num::div(7, 2));
        $this->assertSame(2, Num::div(6, 3));
    }

    public function testDivRejectsZero(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Num::div(1, 0);
    }

    public function testArithmeticWithNumber(): void
    {
        $this->assertInstanceOf(Number::class, Num::add(new Number('0.1'), new Number('0.2')));
        $this->assertEquals(new Number('0.3'), Num::add(new Number('0.1'), new Number('0.2')));
        $this->assertEquals(new Number('6'), Num::mul(new Number('2'), 3));
        $this->assertEquals(new Number('2.5'), Num::div(new Number('5'), 2));
    }

    public function testLerpRemapWithNumber(): void
    {
        $lerp = Num::lerp(new Number('0'), new Number('10'), new Number('0.5'));
        $this->assertInstanceOf(Number::class, $lerp);
        $this->assertEquals(new Number('5'), $lerp);

        $remap = Num::remap(new Number('5'), new Number('0'), new Number('10'), new Number('0'), new Number('100'));
        $this->assertInstanceOf(Number::class, $remap);
        $this->assertEquals(new Number('50'), $remap);
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
        $this->expectException(EmptySourceException::class);
        Num::avg([]);
    }

    public function testMinThrowsOnEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Num::min([]);
    }

    public function testMaxThrowsOnEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
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
        $this->expectException(MalformedArgumentException::class);
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
        $this->expectException(MalformedArgumentException::class);
        Num::mod(5, 0);
    }

    public function testParseNumberReturnsBigNumber(): void
    {
        $n = Num::parseNumber('123456789012345678901234567890.5');
        $this->assertInstanceOf(Number::class, $n);
        $this->assertSame('123456789012345678901234567890.5', (string) $n);
    }

    #[DataProvider('parseNumberFailureProvider')]
    public function testParseNumberThrowsNamingTheRejectedValue(float|string $value, string $expectedMessage): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        Num::parseNumber($value);
    }

    /**
     * A string is named as written, with no quotes added; a non-finite float as PHP
     * renders it. The quotes are what var_export would add, so a message that grows
     * them has taken the wrong branch.
     *
     * @return iterable<string, array{float|string, string}>
     */
    public static function parseNumberFailureProvider(): iterable
    {
        yield 'non-numeric string' => ['abc', 'Cannot parse "abc" as number.'];

        yield 'surrounding whitespace' => [' 42 ', 'Cannot parse " 42 " as number.'];

        yield 'INF' => [INF, 'Cannot parse "INF" as number.'];

        yield 'NAN' => [NAN, 'Cannot parse "NAN" as number.'];
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

    public function testParseNumberAcceptsInt(): void
    {
        $this->assertSame('42', (string) Num::parseNumber(42));
        $this->assertSame('-7', (string) Num::parseNumber(-7));
    }

    #[DataProvider('parseNumberFloatProvider')]
    public function testParseNumberReadsAFloatAsTheShortestDecimalThatReadsBack(float $value, string $expected): void
    {
        // The cast prints 14 significant digits, and for a float that needs more it prints a
        // different number: (string) (0.1 + 0.2) is '0.3'. A digit is never dropped now, and a
        // float the cast already prints exactly keeps the scale it had.
        $this->assertSame($expected, (string) Num::parseNumber($value));
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function parseNumberFloatProvider(): iterable
    {
        yield 'a float the cast rounds away' => [0.1 + 0.2, '0.30000000000000004'];

        yield 'one third' => [1 / 3, '0.3333333333333333'];

        yield 'fifteen digits before the point' => [123456789012345.67, '123456789012345.67'];

        yield 'a float the cast prints exactly' => [0.1, '0.1'];

        yield 'an integral float, with no scale' => [3.0, '3'];

        yield 'an integral float the cast rounds away' => [123456789012345.0, '123456789012345'];

        yield '2⁶³, as the shortest decimal that reads back as it' => [2.0 ** 63, '9223372036854776000'];
    }

    public function testParseNumberAcceptsFiniteFloat(): void
    {
        $this->assertSame('3.14', (string) Num::parseNumber(3.14));
        // (string) 0.0000001 is '1.0E-7' — the expansion keeps it exact.
        $this->assertSame('0.00000010', (string) Num::parseNumber(0.0000001));
    }

    public function testParseNumberReturnsNumberAsIs(): void
    {
        $n = new Number('1.23');
        $this->assertSame($n, Num::parseNumber($n));
    }

    public function testParseNumberOrNullRejectsNonFiniteFloats(): void
    {
        $this->assertNull(Num::parseNumberOrNull(NAN));
        $this->assertNull(Num::parseNumberOrNull(INF));
        $this->assertNull(Num::parseNumberOrNull(-INF));
    }

    public function testParseNumberThrowsOnNan(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Num::parseNumber(NAN);
    }

    public function testParseNumberThrowsOnInvalid(): void
    {
        $this->expectException(MalformedArgumentException::class);
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
        $this->expectException(MalformedArgumentException::class);
        Num::mod(new Number('5'), new Number('0'));
    }

    public function testSqrtRejectsNegativeNumber(): void
    {
        $this->expectException(MalformedArgumentException::class);
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
        $this->expectException(MalformedArgumentException::class);
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
        $this->expectException(MalformedArgumentException::class);
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

    public function testParseIntAcceptsHighestDigitOfBase(): void
    {
        $this->assertSame(35, Num::parseIntOrNull('z', 36));
        $this->assertSame(35, Num::parseIntOrNull('Z', 36));
    }

    public function testParseIntOrNullRejectsPunctuationBetweenDigitAndLetterRanges(): void
    {
        $this->assertNull(Num::parseIntOrNull('_', 16));
        $this->assertNull(Num::parseIntOrNull('5_0', 16));
    }

    public function testClampAllowsMinEqualToMax(): void
    {
        $this->assertSame(3, Num::clamp(5, 3, 3));
        $this->assertSame(3, Num::clamp(1, 3, 3));
    }

    public function testClampReturnsValueItselfOnBoundaryEquality(): void
    {
        $this->assertSame(3, Num::clamp(3, 3.0, 5));
        $this->assertSame(5, Num::clamp(5, 1, 5.0));
    }

    public function testRemapEmptyInputRangeMessage(): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Input range cannot be empty (inMin equals inMax).');
        Num::remap(5, 2, 2, 0, 10);
    }

    public function testRemapRefusesAnEmptyRangeBetweenANumberAndAFloat(): void
    {
        // The two read as unequal, and the empty range failed later, as a division by zero.
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Input range cannot be empty (inMin equals inMax).');
        Num::remap(1, new Number('1'), 1.0, 0, 1);
    }

    public function testMinMaxKeepFirstOfEqualElements(): void
    {
        $this->assertSame(3, Num::min([3, 3.0]));
        $this->assertSame(3, Num::max([3, 3.0]));
    }

    public function testMinMaxOrderMixedElementsAsTheValuesTheyAre(): void
    {
        // A Number against a float had no order under strict types, so the first element won; a
        // float against an int turned the int into a float, and PHP_INT_MAX into 2⁶³.
        $half = new Number('0.5');
        $this->assertSame(0.9, Num::max([$half, 0.9]));
        $this->assertSame($half, Num::min([0.9, $half]));
        $this->assertSame(2.0 ** 63, Num::max([PHP_INT_MAX, 2.0 ** 63]));
        $this->assertSame(PHP_INT_MAX, Num::min([2.0 ** 63, PHP_INT_MAX]));
    }

    public function testAbsReturnsZeroNumberAsIs(): void
    {
        $zero = new Number('0');
        $this->assertSame($zero, Num::abs($zero));
    }

    public function testArithmeticWidensAFloatWithoutLosingItsValue(): void
    {
        // The widening used `new Number((string) $float)`: 14 digits, and scientific notation
        // that the Number constructor refuses with a ValueError — which escapes every catch of
        // this library's own exception.
        $this->assertSame('10000000000000000000000001', (string) Num::add(new Number('1'), 1e25));
        $this->assertSame('-0.30000000000000004', (string) Num::sub(new Number('0'), 0.1 + 0.2));
        $this->assertSame('246913578024691.34', (string) Num::mul(new Number('2'), 123456789012345.67));
        $this->assertSame('2500000000000000000000000', (string) Num::div(1e25, new Number('4')));
        $this->assertSame('1', (string) Num::mod(1e25, new Number('3')));
        $this->assertSame('0.0900000000000000240000000000000016', (string) Num::pow(0.1 + 0.2, new Number('2')));
    }

    public function testArithmeticRefusesAFloatNoNumberCanHold(): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "INF" as number.');
        Num::add(new Number('1'), INF);
    }

    public function testClampHoldsANumberToAFractionalFloatBound(): void
    {
        $this->assertSame(0.9, Num::clamp(new Number('0.5'), 0.9, 2.0));
        $inside = new Number('1.1');
        $this->assertSame($inside, Num::clamp($inside, 0.0, 1.2));
    }

    public function testClampOrdersAnInfinityAgainstANumber(): void
    {
        // No Number holds an infinity, so it is ordered by its sign rather than widened: beyond
        // every Number on its side, whichever operand it is.
        $low = new Number('0');
        $high = new Number('1');
        $five = new Number('5');
        $this->assertSame($high, Num::clamp(INF, $low, $high));
        $this->assertSame($low, Num::clamp(-INF, $low, $high));
        $this->assertSame($five, Num::clamp($five, -INF, INF));
        $this->assertSame($five, Num::clamp($five, $low, INF));
        $this->assertSame(INF, Num::clamp($five, INF, INF));
        $this->assertSame(-INF, Num::clamp($five, -INF, -INF));
    }

    public function testArithmeticWidensFloatOperandToNumber(): void
    {
        $this->assertSame('2.5', (string) Num::add(new Number('2'), 0.5));
        $this->assertSame('2.5', (string) Num::add(0.5, new Number('2')));
        $this->assertSame('1.5', (string) Num::sub(new Number('2'), 0.5));
        $this->assertSame('-1.5', (string) Num::sub(0.5, new Number('2')));
        $this->assertSame('1.0', (string) Num::mul(new Number('2'), 0.5));
        $this->assertSame('1.0', (string) Num::mul(0.5, new Number('2')));
        $this->assertSame('4', (string) Num::div(new Number('2'), 0.5));
        $this->assertSame('0.25', (string) Num::div(0.5, new Number('2')));
        $this->assertSame('2.0', (string) Num::mod(new Number('7'), 2.5));
        $this->assertSame('0.0', (string) Num::mod(7.5, new Number('2.5')));
        $this->assertSame('8', (string) Num::pow(new Number('2'), 3.0));
        $this->assertSame('8', (string) Num::pow(2.0, new Number('3')));
    }

    public function testModByFloatZeroThrows(): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Cannot mod by zero.');
        Num::mod(5.0, 0.0);
    }

    public function testDivByFloatZeroThrows(): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Cannot divide by zero.');
        Num::div(5.0, 0.0);
    }

    public function testDivByNumberZeroThrows(): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Cannot divide by zero.');
        Num::div(new Number('5'), new Number('0'));
    }

    public function testModIntByFloatUsesFmod(): void
    {
        $this->assertSame(2.0, Num::mod(7, 2.5));
        $this->assertSame(1.5, Num::mod(7.5, 2));
    }

    public function testSqrtOfZero(): void
    {
        $this->assertSame(0.0, Num::sqrt(0));
        $this->assertSame('0', (string) Num::sqrt(new Number('0')));
    }

    public function testFloorCeilWithNegativePrecisionOnFloats(): void
    {
        $this->assertSame(10.0, Num::floor(15.72, -1));
        $this->assertSame(20.0, Num::ceil(15.12, -1));
    }

    public function testFloorCeilWithPrecisionTwo(): void
    {
        $this->assertSame(1.23, Num::floor(1.239, 2));
        $this->assertSame(1.24, Num::ceil(1.231, 2));
    }

    public function testFloorCeilWithNumberAndNegativePrecisionShiftBackOut(): void
    {
        $this->assertSame('10', (string) Num::floor(new Number('15'), -1));
        $this->assertSame('20', (string) Num::ceil(new Number('15'), -1));
    }

    public function testParseNumberNegativeScientificNotation(): void
    {
        $this->assertSame('-0.0015', (string) Num::parseNumber('-1.5e-3'));
        $this->assertSame('0.0015', (string) Num::parseNumber('+1.5e-3'));
    }

    public function testParseNumberOrNullAcceptsExponentAtDigitLimit(): void
    {
        $parsed = Num::parseNumberOrNull('1e65535');
        $this->assertNotNull($parsed);
        $this->assertSame(65536, strlen((string) $parsed));
    }
}
