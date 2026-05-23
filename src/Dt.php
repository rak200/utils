<?php

declare(strict_types=1);

namespace Rak200\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use RuntimeException;

final class Dt {
    private function __construct() {}

    public static function now(?DateTimeZone $tz = null): DateTimeImmutable {
        return new DateTimeImmutable('now', $tz);
    }

    public static function today(?DateTimeZone $tz = null): DateTimeImmutable {
        return self::now($tz)->setTime(0, 0, 0);
    }

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

    public static function parse(string $value, ?string $format = null): DateTimeImmutable {
        $result = self::parseOrNull($value, $format);
        if ($result === null) {
            throw new RuntimeException(sprintf('Cannot parse "%s" as date/time.', $value));
        }
        return $result;
    }

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

    public static function fromEpoch(int $seconds, ?DateTimeZone $tz = null): DateTimeImmutable {
        $dt = new DateTimeImmutable('@' . $seconds);
        return $tz !== null ? $dt->setTimezone($tz) : $dt;
    }

    public static function fromEpochMs(int $milliseconds, ?DateTimeZone $tz = null): DateTimeImmutable {
        $seconds = intdiv($milliseconds, 1000);
        $remainder = $milliseconds % 1000;
        if ($remainder < 0) {
            $remainder += 1000;
            $seconds -= 1;
        }
        $dt = DateTimeImmutable::createFromFormat('U.u', sprintf('%d.%06d', $seconds, $remainder * 1000));
        if ($dt === false) {
            throw new RuntimeException(sprintf('Cannot create date/time from epoch ms %d.', $milliseconds));
        }
        return $tz !== null ? $dt->setTimezone($tz) : $dt;
    }

    public static function format(DateTimeInterface $dt, string $pattern): string {
        return $dt->format($pattern);
    }

    public static function iso(DateTimeInterface $dt): string {
        return $dt->format(DateTimeInterface::ATOM);
    }

    public static function sql(DateTimeInterface $dt): string {
        return $dt->format('Y-m-d H:i:s');
    }

    public static function date(DateTimeInterface $dt): string {
        return $dt->format('Y-m-d');
    }

    public static function time(DateTimeInterface $dt): string {
        return $dt->format('H:i:s');
    }

    public static function addDays(DateTimeInterface $dt, int $days): DateTimeImmutable {
        return self::immutable($dt)->modify(sprintf('%+d days', $days));
    }

    public static function addHours(DateTimeInterface $dt, int $hours): DateTimeImmutable {
        return self::immutable($dt)->modify(sprintf('%+d hours', $hours));
    }

    public static function addMinutes(DateTimeInterface $dt, int $minutes): DateTimeImmutable {
        return self::immutable($dt)->modify(sprintf('%+d minutes', $minutes));
    }

    public static function addSeconds(DateTimeInterface $dt, int $seconds): DateTimeImmutable {
        return self::immutable($dt)->modify(sprintf('%+d seconds', $seconds));
    }

    public static function addMonths(DateTimeInterface $dt, int $months): DateTimeImmutable {
        return self::immutable($dt)->modify(sprintf('%+d months', $months));
    }

    public static function addYears(DateTimeInterface $dt, int $years): DateTimeImmutable {
        return self::immutable($dt)->modify(sprintf('%+d years', $years));
    }

    public static function isBefore(DateTimeInterface $a, DateTimeInterface $b): bool {
        return $a < $b;
    }

    public static function isAfter(DateTimeInterface $a, DateTimeInterface $b): bool {
        return $a > $b;
    }

    public static function isEqual(DateTimeInterface $a, DateTimeInterface $b): bool {
        return $a == $b;
    }

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

    public static function startOfDay(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)->setTime(0, 0, 0);
    }

    public static function endOfDay(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)->setTime(23, 59, 59, 999999);
    }

    public static function startOfWeek(DateTimeInterface $dt): DateTimeImmutable {
        $dayOfWeek = (int) $dt->format('N');
        return self::startOfDay($dt)->modify(sprintf('-%d days', $dayOfWeek - 1));
    }

    public static function endOfWeek(DateTimeInterface $dt): DateTimeImmutable {
        $dayOfWeek = (int) $dt->format('N');
        return self::endOfDay($dt)->modify(sprintf('+%d days', 7 - $dayOfWeek));
    }

    public static function startOfMonth(DateTimeInterface $dt): DateTimeImmutable {
        return self::startOfDay($dt)->modify('first day of this month');
    }

    public static function endOfMonth(DateTimeInterface $dt): DateTimeImmutable {
        return self::endOfDay($dt)->modify('last day of this month');
    }

    public static function startOfYear(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)
            ->setDate((int) $dt->format('Y'), 1, 1)
            ->setTime(0, 0, 0);
    }

    public static function endOfYear(DateTimeInterface $dt): DateTimeImmutable {
        return self::immutable($dt)
            ->setDate((int) $dt->format('Y'), 12, 31)
            ->setTime(23, 59, 59, 999999);
    }

    public static function diffInDays(DateTimeInterface $a, DateTimeInterface $b): int {
        $diff = $a->diff($b);
        return ($diff->invert === 0 ? 1 : -1) * $diff->days;
    }

    public static function diffInSeconds(DateTimeInterface $a, DateTimeInterface $b): int {
        return $b->getTimestamp() - $a->getTimestamp();
    }

    public static function diffInMinutes(DateTimeInterface $a, DateTimeInterface $b): int {
        return intdiv(self::diffInSeconds($a, $b), 60);
    }

    public static function diffInHours(DateTimeInterface $a, DateTimeInterface $b): int {
        return intdiv(self::diffInSeconds($a, $b), 3600);
    }

    private static function immutable(DateTimeInterface $dt): DateTimeImmutable {
        return $dt instanceof DateTimeImmutable
            ? $dt
            : DateTimeImmutable::createFromInterface($dt);
    }
}
