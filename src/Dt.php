<?php

declare(strict_types=1);

namespace Rak200\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use RuntimeException;
use function checkdate, intdiv;

/**
 * Date/time helpers built on {@see DateTimeImmutable}. Inputs accept any
 * {@see DateTimeInterface}; returns are always immutable.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Dt {
    private function __construct() {}

    /**
     * Returns true if $value is a {@see DateTimeInterface} instance (either
     * {@see DateTimeImmutable} or the mutable {@see \DateTime}). Domain
     * predicate for {@see Dt}.
     *
     * @phpstan-assert-if-true DateTimeInterface $value
     * @phpstan-assert-if-false !DateTimeInterface $value
     */
    public static function is(mixed $value): bool {
        return $value instanceof DateTimeInterface;
    }

    /**
     * Returns true when $year-$month-$day is a valid Gregorian calendar date
     * (e.g. rejects 2025-02-29 but accepts 2024-02-29). Wraps {@see checkdate()}.
     */
    public static function isValid(int $year, int $month, int $day): bool {
        return checkdate($month, $day, $year);
    }

    /**
     * Returns the current instant, in $tz or the default timezone.
     */
    public static function now(?DateTimeZone $tz = null): DateTimeImmutable {
        return new DateTimeImmutable('now', $tz);
    }

    /**
     * Returns the current date at 00:00:00, in $tz or the default timezone.
     */
    public static function today(?DateTimeZone $tz = null): DateTimeImmutable {
        return self::now($tz)->setTime(0, 0, 0);
    }

    /**
     * Builds a date/time from individual components.
     */
    public static function of(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        ?DateTimeZone $tz = null,
    ): DateTimeImmutable {
        return (new DateTimeImmutable('now', $tz))
            ->setDate($year, $month, $day)
            ->setTime($hour, $minute, $second);
    }

    /**
     * Parses $value. When $format is null, any expression accepted by
     * {@see DateTimeImmutable::__construct()} is allowed.
     *
     * @throws RuntimeException When $value cannot be parsed.
     */
    public static function parse(string $value, ?string $format = null): DateTimeImmutable {
        $result = self::parseOrNull($value, $format);
        if ($result === null) {
            throw new RuntimeException("Cannot parse \"$value\" as date/time.");
        }
        return $result;
    }

    /**
     * Parses $value, returning null when it cannot be parsed.
     */
    public static function parseOrNull(string $value, ?string $format = null): ?DateTimeImmutable {
        if ($format !== null) {
            $result = DateTimeImmutable::createFromFormat($format, $value);
            return $result === false ? null : $result;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Builds a date/time from a Unix timestamp in seconds, optionally shifted
     * to $tz.
     */
    public static function fromEpoch(int $seconds, ?DateTimeZone $tz = null): DateTimeImmutable {
        $dt = new DateTimeImmutable("@$seconds");
        return $tz !== null ? $dt->setTimezone($tz) : $dt;
    }

    /**
     * Builds a date/time from a Unix timestamp in milliseconds, optionally
     * shifted to $tz.
     *
     * @throws RuntimeException When the timestamp cannot be represented.
     */
    public static function fromEpochMs(int $milliseconds, ?DateTimeZone $tz = null): DateTimeImmutable {
        $seconds = Num::intDiv($milliseconds, 1000);
        $remainder = $milliseconds % 1000;
        if ($remainder < 0) {
            $remainder += 1000;
            $seconds -= 1;
        }
        $micro = $remainder * 1000;
        $dt = DateTimeImmutable::createFromFormat('U.u', (string) $seconds . '.' . Str::padStart((string) $micro, 6, '0'));
        if ($dt === false) {
            throw new RuntimeException("Cannot create date/time from epoch ms $milliseconds.");
        }
        return $tz !== null ? $dt->setTimezone($tz) : $dt;
    }

    /**
     * Formats $dt with the given format pattern (see {@see date()} pattern).
     */
    public static function format(DateTimeInterface $dt, string $pattern): string {
        return $dt->format($pattern);
    }

    /**
     * Returns $dt formatted as ISO-8601 / RFC 3339 (e.g. "2026-05-23T10:15:30+00:00").
     */
    public static function iso(DateTimeInterface $dt): string {
        return $dt->format(DateTimeInterface::ATOM);
    }

    /**
     * Returns $dt formatted as the SQL datetime literal "Y-m-d H:i:s".
     */
    public static function sql(DateTimeInterface $dt): string {
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Returns the date component of $dt as "Y-m-d".
     */
    public static function date(DateTimeInterface $dt): string {
        return $dt->format('Y-m-d');
    }

    /**
     * Returns the time component of $dt as "H:i:s".
     */
    public static function time(DateTimeInterface $dt): string {
        return $dt->format('H:i:s');
    }

    /**
     * Returns $dt shifted by $days days (may be negative).
     */
    public static function addDays(DateTimeInterface $dt, int $days): DateTimeImmutable {
        $signed = $days >= 0 ? "+$days" : (string) $days;
        return self::immutable($dt)->modify("$signed days");
    }

    /**
     * Returns $dt shifted by $hours hours (may be negative).
     */
    public static function addHours(DateTimeInterface $dt, int $hours): DateTimeImmutable {
        $signed = $hours >= 0 ? "+$hours" : (string) $hours;
        return self::immutable($dt)->modify("$signed hours");
    }

    /**
     * Returns $dt shifted by $minutes minutes (may be negative).
     */
    public static function addMinutes(DateTimeInterface $dt, int $minutes): DateTimeImmutable {
        $signed = $minutes >= 0 ? "+$minutes" : (string) $minutes;
        return self::immutable($dt)->modify("$signed minutes");
    }

    /**
     * Returns $dt shifted by $seconds seconds (may be negative).
     */
    public static function addSeconds(DateTimeInterface $dt, int $seconds): DateTimeImmutable {
        $signed = $seconds >= 0 ? "+$seconds" : (string) $seconds;
        return self::immutable($dt)->modify("$signed seconds");
    }

    /**
     * Returns $dt shifted by $months months (may be negative).
     */
    public static function addMonths(DateTimeInterface $dt, int $months): DateTimeImmutable {
        $signed = $months >= 0 ? "+$months" : (string) $months;
        return self::immutable($dt)->modify("$signed months");
    }

    /**
     * Returns $dt shifted by $years years (may be negative).
     */
    public static function addYears(DateTimeInterface $dt, int $years): DateTimeImmutable {
        $signed = $years >= 0 ? "+$years" : (string) $years;
        return self::immutable($dt)->modify("$signed years");
    }

    /**
     * Returns true when $a is strictly earlier than $b.
     */
    public static function isBefore(DateTimeInterface $a, DateTimeInterface $b): bool {
        return $a < $b;
    }

    /**
     * Returns true when $a is strictly later than $b.
     */
    public static function isAfter(DateTimeInterface $a, DateTimeInterface $b): bool {
        return $a > $b;
    }

    /**
     * Returns true when $a and $b represent the same UTC instant (microsecond
     * precision). Timezone differences are ignored — two times in different
     * zones that point at the same instant compare equal.
     */
    public static function isEqual(DateTimeInterface $a, DateTimeInterface $b): bool {
        return $a->format('U.u') === $b->format('U.u');
    }

    /**
     * Returns the earliest of the given date/times.
     *
     * @throws RuntimeException When no arguments are given.
     */
    public static function min(DateTimeInterface ...$dts): DateTimeImmutable {
        if ($dts === []) {
            throw new RuntimeException('Cannot compute min of empty input.');
        }
        $min = $dts[0];
        foreach ($dts as $dt) {
            if ($dt < $min) {
                $min = $dt;
            }
        }
        return self::immutable($min);
    }

    /**
     * Returns the latest of the given date/times.
     *
     * @throws RuntimeException When no arguments are given.
     */
    public static function max(DateTimeInterface ...$dts): DateTimeImmutable {
        if ($dts === []) {
            throw new RuntimeException('Cannot compute max of empty input.');
        }
        $max = $dts[0];
        foreach ($dts as $dt) {
            if ($dt > $max) {
                $max = $dt;
            }
        }
        return self::immutable($max);
    }

    /**
     * Returns $dt at 00:00:00 on the same calendar day.
     */
    public static function startOfDay(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)->setTime(0, 0, 0);
    }

    /**
     * Returns $dt at 23:59:59.999999 on the same calendar day.
     */
    public static function endOfDay(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)->setTime(23, 59, 59, 999999);
    }

    /**
     * Returns the Monday of $dt's week at 00:00:00 (ISO-8601 week, Monday-first).
     */
    public static function startOfWeek(DateTimeInterface $dt): DateTimeImmutable {
        $dayOfWeek = (int) $dt->format('N');
        $offset = $dayOfWeek - 1;
        return self::startOfDay($dt)->modify("-{$offset} days");
    }

    /**
     * Returns the Sunday of $dt's week at 23:59:59.999999 (ISO-8601 week,
     * Monday-first).
     */
    public static function endOfWeek(DateTimeInterface $dt): DateTimeImmutable {
        $dayOfWeek = (int) $dt->format('N');
        $offset = 7 - $dayOfWeek;
        return self::endOfDay($dt)->modify("+{$offset} days");
    }

    /**
     * Returns the first day of $dt's month at 00:00:00.
     */
    public static function startOfMonth(DateTimeInterface $dt): DateTimeImmutable {
        return self::startOfDay($dt)->modify('first day of this month');
    }

    /**
     * Returns the last day of $dt's month at 23:59:59.999999.
     */
    public static function endOfMonth(DateTimeInterface $dt): DateTimeImmutable {
        return self::endOfDay($dt)->modify('last day of this month');
    }

    /**
     * Returns January 1 of $dt's year at 00:00:00.
     */
    public static function startOfYear(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)
            ->setDate((int) $dt->format('Y'), 1, 1)
            ->setTime(0, 0, 0);
    }

    /**
     * Returns December 31 of $dt's year at 23:59:59.999999.
     */
    public static function endOfYear(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)
            ->setDate((int) $dt->format('Y'), 12, 31)
            ->setTime(23, 59, 59, 999999);
    }

    /**
     * Returns true when $dt falls on a Saturday or Sunday.
     */
    public static function isWeekend(DateTimeInterface $dt): bool {
        $dow = (int) $dt->format('N');
        return $dow === 6 || $dow === 7;
    }

    /**
     * Returns true when $dt falls on a Monday through Friday.
     */
    public static function isWeekday(DateTimeInterface $dt): bool {
        return !self::isWeekend($dt);
    }

    /**
     * Returns the ISO-8601 day of week of $dt: 1 (Monday) through 7 (Sunday).
     */
    public static function dayOfWeek(DateTimeInterface $dt): int {
        return (int) $dt->format('N');
    }

    /**
     * Returns the 1-based day of year of $dt (1 through 365 or 366).
     */
    public static function dayOfYear(DateTimeInterface $dt): int {
        return (int) $dt->format('z') + 1;
    }

    /**
     * Returns the ISO-8601 week number of $dt (1 through 53).
     */
    public static function weekOfYear(DateTimeInterface $dt): int {
        return (int) $dt->format('W');
    }

    /**
     * Returns the signed difference (b − a) in whole days.
     */
    public static function diffInDays(DateTimeInterface $a, DateTimeInterface $b): int {
        $diff = $a->diff($b);
        return ($diff->invert === 0 ? 1 : -1) * $diff->days;
    }

    /**
     * Returns the signed difference (b − a) in whole seconds.
     */
    public static function diffInSeconds(DateTimeInterface $a, DateTimeInterface $b): int {
        return $b->getTimestamp() - $a->getTimestamp();
    }

    /**
     * Returns the signed difference (b − a) in whole minutes (truncated toward zero).
     */
    public static function diffInMinutes(DateTimeInterface $a, DateTimeInterface $b): int {
        return intdiv(self::diffInSeconds($a, $b), 60);
    }

    /**
     * Returns the signed difference (b − a) in whole hours (truncated toward zero).
     */
    public static function diffInHours(DateTimeInterface $a, DateTimeInterface $b): int {
        return intdiv(self::diffInSeconds($a, $b), 3600);
    }

    /**
     * Normalises any {@see DateTimeInterface} to {@see DateTimeImmutable} —
     * returns $dt as-is when already immutable, otherwise converts mutable
     * inputs without mutating them.
     */
    private static function immutable(DateTimeInterface $dt): DateTimeImmutable {
        return $dt instanceof DateTimeImmutable
            ? $dt
            : DateTimeImmutable::createFromInterface($dt);
    }
}
