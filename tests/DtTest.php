<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Dt;
use RuntimeException;

final class DtTest extends TestCase {
    public function testNowReturnsCurrentInstant(): void {
        $before = time();
        $now = Dt::now();
        $after = time();
        $this->assertGreaterThanOrEqual($before, $now->getTimestamp());
        $this->assertLessThanOrEqual($after, $now->getTimestamp());
    }

    public function testTodayHasZeroTime(): void {
        $today = Dt::today();
        $this->assertSame('00:00:00', $today->format('H:i:s'));
    }

    public function testOfConstructsExplicitDate(): void {
        $dt = Dt::of(2026, 5, 23, 14, 30, 45);
        $this->assertSame('2026-05-23 14:30:45', $dt->format('Y-m-d H:i:s'));
    }

    public function testOfDefaultsTimeToZero(): void {
        $dt = Dt::of(2026, 5, 23);
        $this->assertSame('2026-05-23 00:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testParseAcceptsIso(): void {
        $dt = Dt::parse('2026-05-23T14:30:45+00:00');
        $this->assertSame(1779546645, $dt->getTimestamp());
    }

    public function testParseWithFormat(): void {
        $dt = Dt::parse('23/05/2026', 'd/m/Y');
        $this->assertSame('2026-05-23', $dt->format('Y-m-d'));
    }

    public function testParseThrowsOnInvalid(): void {
        $this->expectException(RuntimeException::class);
        Dt::parse('not a date');
    }

    public function testParseOrNullReturnsNullOnInvalid(): void {
        $this->assertNull(Dt::parseOrNull('not a date'));
        $this->assertNull(Dt::parseOrNull('not a date', 'Y-m-d'));
    }

    public function testFromEpoch(): void {
        $dt = Dt::fromEpoch(0, new DateTimeZone('UTC'));
        $this->assertSame('1970-01-01 00:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testFromEpochMs(): void {
        $dt = Dt::fromEpochMs(1500, new DateTimeZone('UTC'));
        $this->assertSame('1970-01-01 00:00:01.500000', $dt->format('Y-m-d H:i:s.u'));
    }

    public function testFormatters(): void {
        $dt = new DateTimeImmutable('2026-05-23T14:30:45+00:00');
        $this->assertSame('2026-05-23 14:30:45', Dt::sql($dt));
        $this->assertSame('2026-05-23', Dt::date($dt));
        $this->assertSame('14:30:45', Dt::time($dt));
        $this->assertSame('2026-05-23T14:30:45+00:00', Dt::iso($dt));
        $this->assertSame('05/2026', Dt::format($dt, 'm/Y'));
    }

    public function testArithmetic(): void {
        $base = new DateTimeImmutable('2026-05-23 12:00:00');
        $this->assertSame('2026-05-25', Dt::addDays($base, 2)->format('Y-m-d'));
        $this->assertSame('2026-05-21', Dt::addDays($base, -2)->format('Y-m-d'));
        $this->assertSame('15:00:00', Dt::addHours($base, 3)->format('H:i:s'));
        $this->assertSame('12:30:00', Dt::addMinutes($base, 30)->format('H:i:s'));
        $this->assertSame('12:00:15', Dt::addSeconds($base, 15)->format('H:i:s'));
        $this->assertSame('2026-08-23', Dt::addMonths($base, 3)->format('Y-m-d'));
        $this->assertSame('2028-05-23', Dt::addYears($base, 2)->format('Y-m-d'));
    }

    public function testComparison(): void {
        $a = new DateTimeImmutable('2026-05-23');
        $b = new DateTimeImmutable('2026-05-24');
        $this->assertTrue(Dt::isBefore($a, $b));
        $this->assertFalse(Dt::isBefore($b, $a));
        $this->assertTrue(Dt::isAfter($b, $a));
        $this->assertFalse(Dt::isAfter($a, $b));
        $this->assertTrue(Dt::isEqual($a, new DateTimeImmutable('2026-05-23')));
        $this->assertFalse(Dt::isEqual($a, $b));
    }

    public function testMinMax(): void {
        $a = new DateTimeImmutable('2026-01-01');
        $b = new DateTimeImmutable('2026-06-01');
        $c = new DateTimeImmutable('2026-12-01');
        $this->assertEquals($a, Dt::min($b, $a, $c));
        $this->assertEquals($c, Dt::max($b, $a, $c));
    }

    public function testMinThrowsOnEmpty(): void {
        $this->expectException(RuntimeException::class);
        Dt::min();
    }

    public function testStartEndOfDay(): void {
        $dt = new DateTimeImmutable('2026-05-23 14:30:45');
        $this->assertSame('2026-05-23 00:00:00', Dt::startOfDay($dt)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-23 23:59:59', Dt::endOfDay($dt)->format('Y-m-d H:i:s'));
    }

    public function testStartEndOfWeek(): void {
        $saturday = new DateTimeImmutable('2026-05-23');
        $this->assertSame('2026-05-18', Dt::startOfWeek($saturday)->format('Y-m-d'));
        $this->assertSame('2026-05-24', Dt::endOfWeek($saturday)->format('Y-m-d'));
    }

    public function testStartEndOfMonth(): void {
        $dt = new DateTimeImmutable('2026-05-23 14:30:45');
        $this->assertSame('2026-05-01 00:00:00', Dt::startOfMonth($dt)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-31 23:59:59', Dt::endOfMonth($dt)->format('Y-m-d H:i:s'));
    }

    public function testStartEndOfYear(): void {
        $dt = new DateTimeImmutable('2026-05-23 14:30:45');
        $this->assertSame('2026-01-01 00:00:00', Dt::startOfYear($dt)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-31 23:59:59', Dt::endOfYear($dt)->format('Y-m-d H:i:s'));
    }

    public function testIsEqualIgnoresTimezone(): void {
        $a = new DateTimeImmutable('2026-05-23T12:00:00+00:00');
        $b = new DateTimeImmutable('2026-05-23T09:00:00-03:00');
        $this->assertTrue(Dt::isEqual($a, $b));
    }

    public function testWeekendWeekday(): void {
        $sat = new DateTimeImmutable('2026-05-23');
        $sun = new DateTimeImmutable('2026-05-24');
        $mon = new DateTimeImmutable('2026-05-25');
        $this->assertTrue(Dt::isWeekend($sat));
        $this->assertTrue(Dt::isWeekend($sun));
        $this->assertFalse(Dt::isWeekend($mon));
        $this->assertFalse(Dt::isWeekday($sat));
        $this->assertTrue(Dt::isWeekday($mon));
    }

    public function testDayOfWeekDayOfYearWeekOfYear(): void {
        $dt = new DateTimeImmutable('2026-05-23');
        $this->assertSame(6, Dt::dayOfWeek($dt));
        $this->assertSame(143, Dt::dayOfYear($dt));
        $this->assertSame(21, Dt::weekOfYear($dt));

        $jan1 = new DateTimeImmutable('2026-01-01');
        $this->assertSame(1, Dt::dayOfYear($jan1));
    }

    public function testDiffInUnits(): void {
        $a = new DateTimeImmutable('2026-05-23 12:00:00');
        $b = new DateTimeImmutable('2026-05-25 14:30:00');
        $this->assertSame(2, Dt::diffInDays($a, $b));
        $this->assertSame(-2, Dt::diffInDays($b, $a));
        $this->assertSame(50, Dt::diffInHours($a, $b));
        $this->assertSame(3030, Dt::diffInMinutes($a, $b));
        $this->assertSame(181800, Dt::diffInSeconds($a, $b));
    }
}
