<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Dt;
use Rak200\Utils\Exception\EmptySourceException;
use Rak200\Utils\Exception\MalformedArgumentException;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class DtTest extends TestCase
{
    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Dt::is($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable
    {
        yield 'immutable' => [new DateTimeImmutable(), true];

        yield 'mutable' => [new DateTime(), true];

        yield 'string' => ['2026-05-23', false];

        yield 'null' => [null, false];

        yield 'int' => [0, false];

        yield 'object' => [new stdClass(), false];
    }

    public function testNowReturnsCurrentInstant(): void
    {
        $before = time();
        $now = Dt::now();
        $after = time();
        $this->assertGreaterThanOrEqual($before, $now->getTimestamp());
        $this->assertLessThanOrEqual($after, $now->getTimestamp());
    }

    public function testTodayHasZeroTime(): void
    {
        $today = Dt::today();
        $this->assertSame('00:00:00', $today->format('H:i:s'));
    }

    public function testOfConstructsExplicitDate(): void
    {
        $dt = Dt::of(2026, 5, 23, 14, 30, 45);
        $this->assertSame('2026-05-23 14:30:45', $dt->format('Y-m-d H:i:s'));
    }

    public function testOfDefaultsTimeToZero(): void
    {
        $dt = Dt::of(2026, 5, 23);
        $this->assertSame('2026-05-23 00:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testParseAcceptsIso(): void
    {
        $dt = Dt::parse('2026-05-23T14:30:45+00:00');
        $this->assertSame(1779546645, $dt->getTimestamp());
    }

    public function testParseWithFormat(): void
    {
        $dt = Dt::parse('23/05/2026', 'd/m/Y');
        $this->assertSame('2026-05-23', $dt->format('Y-m-d'));
    }

    public function testParseThrowsOnInvalid(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Dt::parse('not a date');
    }

    public function testParseOrNullReturnsNullOnInvalid(): void
    {
        $this->assertNull(Dt::parseOrNull('not a date'));
        $this->assertNull(Dt::parseOrNull('not a date', 'Y-m-d'));
    }

    public function testFromEpoch(): void
    {
        $dt = Dt::fromEpoch(0, new DateTimeZone('UTC'));
        $this->assertSame('1970-01-01 00:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testFromEpochMs(): void
    {
        $dt = Dt::fromEpochMs(1500, new DateTimeZone('UTC'));
        $this->assertSame('1970-01-01 00:00:01.500000', $dt->format('Y-m-d H:i:s.u'));
    }

    public function testFromEpochMsHandlesNegativeMilliseconds(): void
    {
        $dt = Dt::fromEpochMs(-500, new DateTimeZone('UTC'));   // 500 ms before the epoch
        $this->assertSame('1969-12-31 23:59:59.500000', $dt->format('Y-m-d H:i:s.u'));
    }

    public function testFromInterfaceConvertsMutable(): void
    {
        $mutable = new DateTime('2026-05-23 14:30:45', new DateTimeZone('UTC'));
        $dt = Dt::fromInterface($mutable);
        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertSame('2026-05-23 14:30:45', $dt->format('Y-m-d H:i:s'));
    }

    public function testFromInterfaceReturnsAnImmutableAsIs(): void
    {
        $immutable = new DateTimeImmutable('2026-05-23 14:30:45');
        $this->assertSame($immutable, Dt::fromInterface($immutable));
    }

    public function testFromInterfaceDoesNotAliasTheMutableSource(): void
    {
        $mutable = new DateTime('2026-05-23 14:30:45', new DateTimeZone('UTC'));
        $dt = Dt::fromInterface($mutable);
        $mutable->modify('+1 day');
        $this->assertSame('2026-05-23 14:30:45', $dt->format('Y-m-d H:i:s'));
    }

    public function testToEpoch(): void
    {
        $this->assertSame(0, Dt::toEpoch(new DateTimeImmutable('@0')));
        $this->assertSame(1_750_000_000, Dt::toEpoch(new DateTimeImmutable('@1750000000')));
        $this->assertSame(-1, Dt::toEpoch(new DateTimeImmutable('@-1')));
    }

    public function testToEpochRoundTripsWithFromEpoch(): void
    {
        $this->assertSame(1_750_000_000, Dt::toEpoch(Dt::fromEpoch(1_750_000_000)));
    }

    public function testToEpochDropsTheSubSecondComponent(): void
    {
        $this->assertSame(1, Dt::toEpoch(Dt::fromEpochMs(1500, new DateTimeZone('UTC'))));
    }

    public function testToEpochFloatKeepsMicroseconds(): void
    {
        $dt = Dt::fromEpochMs(1500, new DateTimeZone('UTC'));
        $this->assertSame(1.5, Dt::toEpochFloat($dt));
    }

    public function testToEpochFloatIsExactOnAWholeSecond(): void
    {
        $this->assertSame(2.0, Dt::toEpochFloat(Dt::fromEpoch(2)));
    }

    /**
     * The reason this helper exists: `format('U.u')` would render this instant
     * as `-1.500000` — the negative seconds glued to the positive microsecond
     * fraction — instead of the true -0.5s.
     */
    public function testToEpochFloatIsCorrectForAPreEpochInstant(): void
    {
        $dt = Dt::fromEpochMs(-500, new DateTimeZone('UTC'));   // 500 ms before the epoch
        $this->assertSame(-0.5, Dt::toEpochFloat($dt));
        $this->assertSame(-1.5, (float) $dt->format('U.u'));   // the trap, pinned
    }

    public function testFormatters(): void
    {
        $dt = new DateTimeImmutable('2026-05-23T14:30:45+00:00');
        $this->assertSame('2026-05-23 14:30:45', Dt::sql($dt));
        $this->assertSame('2026-05-23', Dt::date($dt));
        $this->assertSame('14:30:45', Dt::time($dt));
        $this->assertSame('2026-05-23T14:30:45+00:00', Dt::iso($dt));
        $this->assertSame('05/2026', Dt::format($dt, 'm/Y'));
    }

    public function testArithmetic(): void
    {
        $base = new DateTimeImmutable('2026-05-23 12:00:00');
        $this->assertSame('2026-05-25', Dt::addDays($base, 2)->format('Y-m-d'));
        $this->assertSame('2026-05-21', Dt::addDays($base, -2)->format('Y-m-d'));
        $this->assertSame('15:00:00', Dt::addHours($base, 3)->format('H:i:s'));
        $this->assertSame('12:30:00', Dt::addMinutes($base, 30)->format('H:i:s'));
        $this->assertSame('12:00:15', Dt::addSeconds($base, 15)->format('H:i:s'));
        $this->assertSame('2026-08-23', Dt::addMonths($base, 3)->format('Y-m-d'));
        $this->assertSame('2028-05-23', Dt::addYears($base, 2)->format('Y-m-d'));
    }

    public function testComparison(): void
    {
        $a = new DateTimeImmutable('2026-05-23');
        $b = new DateTimeImmutable('2026-05-24');
        $this->assertTrue(Dt::isBefore($a, $b));
        $this->assertFalse(Dt::isBefore($b, $a));
        $this->assertTrue(Dt::isAfter($b, $a));
        $this->assertFalse(Dt::isAfter($a, $b));
        $this->assertTrue(Dt::isEqual($a, new DateTimeImmutable('2026-05-23')));
        $this->assertFalse(Dt::isEqual($a, $b));
    }

    public function testMinMax(): void
    {
        $a = new DateTimeImmutable('2026-01-01');
        $b = new DateTimeImmutable('2026-06-01');
        $c = new DateTimeImmutable('2026-12-01');
        $this->assertEquals($a, Dt::min($b, $a, $c));
        $this->assertEquals($c, Dt::max($b, $a, $c));
    }

    public function testMaxThrowsOnEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Dt::max();
    }

    public function testMinThrowsOnEmpty(): void
    {
        $this->expectException(EmptySourceException::class);
        Dt::min();
    }

    public function testStartEndOfDay(): void
    {
        $dt = new DateTimeImmutable('2026-05-23 14:30:45');
        $this->assertSame('2026-05-23 00:00:00', Dt::startOfDay($dt)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-23 23:59:59', Dt::endOfDay($dt)->format('Y-m-d H:i:s'));
    }

    public function testStartEndOfWeek(): void
    {
        $saturday = new DateTimeImmutable('2026-05-23');
        $this->assertSame('2026-05-18', Dt::startOfWeek($saturday)->format('Y-m-d'));
        $this->assertSame('2026-05-24', Dt::endOfWeek($saturday)->format('Y-m-d'));
    }

    public function testStartEndOfMonth(): void
    {
        $dt = new DateTimeImmutable('2026-05-23 14:30:45');
        $this->assertSame('2026-05-01 00:00:00', Dt::startOfMonth($dt)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-31 23:59:59', Dt::endOfMonth($dt)->format('Y-m-d H:i:s'));
    }

    public function testStartEndOfYear(): void
    {
        $dt = new DateTimeImmutable('2026-05-23 14:30:45');
        $this->assertSame('2026-01-01 00:00:00', Dt::startOfYear($dt)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-31 23:59:59', Dt::endOfYear($dt)->format('Y-m-d H:i:s'));
    }

    public function testIsEqualIgnoresTimezone(): void
    {
        $a = new DateTimeImmutable('2026-05-23T12:00:00+00:00');
        $b = new DateTimeImmutable('2026-05-23T09:00:00-03:00');
        $this->assertTrue(Dt::isEqual($a, $b));
    }

    public function testWeekendWeekday(): void
    {
        $sat = new DateTimeImmutable('2026-05-23');
        $sun = new DateTimeImmutable('2026-05-24');
        $mon = new DateTimeImmutable('2026-05-25');
        $this->assertTrue(Dt::isWeekend($sat));
        $this->assertTrue(Dt::isWeekend($sun));
        $this->assertFalse(Dt::isWeekend($mon));
        $this->assertFalse(Dt::isWeekday($sat));
        $this->assertTrue(Dt::isWeekday($mon));
    }

    public function testIsPastIsFuture(): void
    {
        $past = new DateTimeImmutable('2000-01-01');
        $future = new DateTimeImmutable('2099-01-01');
        $this->assertTrue(Dt::isPast($past));
        $this->assertFalse(Dt::isFuture($past));
        $this->assertTrue(Dt::isFuture($future));
        $this->assertFalse(Dt::isPast($future));
    }

    public function testPeriod(): void
    {
        $days = array_map(
            static fn (DateTimeImmutable $d): string => $d->format('Y-m-d'),
            iterator_to_array(Dt::period(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-04'))),
        );
        $this->assertSame(['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'], $days);
    }

    public function testPeriodExclusive(): void
    {
        $days = array_map(
            static fn (DateTimeImmutable $d): string => $d->format('Y-m-d'),
            iterator_to_array(Dt::period(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-04'), inclusive: false)),
        );
        $this->assertSame(['2026-01-01', '2026-01-02', '2026-01-03'], $days);
    }

    public function testPeriodWithMonthInterval(): void
    {
        $months = array_map(
            static fn (DateTimeImmutable $d): string => $d->format('Y-m-d'),
            iterator_to_array(Dt::period(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-04-01'), new DateInterval('P1M'))),
        );
        $this->assertSame(['2026-01-01', '2026-02-01', '2026-03-01', '2026-04-01'], $months);
    }

    public function testPeriodIsEmptyWhenStartAfterEnd(): void
    {
        $this->assertSame(
            [],
            iterator_to_array(Dt::period(new DateTimeImmutable('2026-01-04'), new DateTimeImmutable('2026-01-01'))),
        );
    }

    public function testPeriodRejectsNonAdvancingStep(): void
    {
        $this->expectException(MalformedArgumentException::class);
        iterator_to_array(Dt::period(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-04'), new DateInterval('PT0S')));
    }

    public function testDayOfWeekDayOfYearWeekOfYear(): void
    {
        $dt = new DateTimeImmutable('2026-05-23');
        $this->assertSame(6, Dt::dayOfWeek($dt));
        $this->assertSame(143, Dt::dayOfYear($dt));
        $this->assertSame(21, Dt::weekOfYear($dt));

        $jan1 = new DateTimeImmutable('2026-01-01');
        $this->assertSame(1, Dt::dayOfYear($jan1));
    }

    public function testIsValid(): void
    {
        $this->assertTrue(Dt::isValid(2026, 5, 31));
        $this->assertTrue(Dt::isValid(2024, 2, 29));   // leap year
        $this->assertFalse(Dt::isValid(2025, 2, 29));  // non-leap year
        $this->assertFalse(Dt::isValid(2026, 13, 1));  // month out of range
        $this->assertFalse(Dt::isValid(2026, 4, 31));  // April has 30 days
        $this->assertFalse(Dt::isValid(2026, 0, 10));  // month zero
    }

    public function testDiffInUnits(): void
    {
        $a = new DateTimeImmutable('2026-05-23 12:00:00');
        $b = new DateTimeImmutable('2026-05-25 14:30:00');
        $this->assertSame(2, Dt::diffDays($a, $b));
        $this->assertSame(-2, Dt::diffDays($b, $a));
        $this->assertSame(50, Dt::diffHours($a, $b));
        $this->assertSame(3030, Dt::diffMinutes($a, $b));
        $this->assertSame(181800, Dt::diffSeconds($a, $b));
    }

    public function testFromEpochAppliesTimezone(): void
    {
        $tz = new DateTimeZone('America/Sao_Paulo');
        $dt = Dt::fromEpoch(0, $tz);
        $this->assertSame('America/Sao_Paulo', $dt->getTimezone()->getName());
        $this->assertSame('1969-12-31 21:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testFromEpochMsWholeSecond(): void
    {
        $this->assertSame('1970-01-01 00:00:02.000000', Dt::fromEpochMs(2000)->format('Y-m-d H:i:s.u'));
    }

    public function testIsBeforeIsAfterAreStrict(): void
    {
        $dt = new DateTimeImmutable('2026-05-23 12:00:00');
        $same = new DateTimeImmutable('2026-05-23 12:00:00');
        $this->assertFalse(Dt::isBefore($dt, $same));
        $this->assertFalse(Dt::isAfter($dt, $same));
    }

    public function testMinMaxSingleElementAndTies(): void
    {
        $a = new DateTimeImmutable('2026-05-23 12:00:00');
        $b = new DateTimeImmutable('2026-05-23 12:00:00');
        $this->assertSame($a, Dt::min($a));
        $this->assertSame($a, Dt::max($a));
        $this->assertSame($a, Dt::min($a, $b)); // ties keep the first
        $this->assertSame($a, Dt::max($a, $b));
    }

    public function testEndOfDayAndYearMicroseconds(): void
    {
        $dt = new DateTimeImmutable('2026-05-23 12:34:56.123456');
        $this->assertSame('23:59:59.999999', Dt::endOfDay($dt)->format('H:i:s.u'));
        $this->assertSame('2026-12-31 23:59:59.999999', Dt::endOfYear($dt)->format('Y-m-d H:i:s.u'));
    }

    public function testDiffHoursTruncatesToWholeHours(): void
    {
        $a = new DateTimeImmutable('2026-05-23 12:00:00');
        $this->assertSame(0, Dt::diffHours($a, $a->modify('+3599 seconds')));
        $this->assertSame(2, Dt::diffHours($a, $a->modify('+7200 seconds')));
    }

    public function testAcceptsMutableDateTimeInput(): void
    {
        $mutable = new DateTime('2026-05-23 12:00:00');
        $result = Dt::addDays($mutable, 1);
        $this->assertSame('2026-05-24 12:00:00', $result->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-23 12:00:00', $mutable->format('Y-m-d H:i:s')); // input untouched
    }
}
