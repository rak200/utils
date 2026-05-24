# Dt

[← Reference](README.md)

Date/time helpers built on `DateTimeImmutable`. Inputs accept any `DateTimeInterface`; returns are always immutable.

```php
use Rak200\Utils\Dt;
use DateTimeZone;
```

## Contents

- [`now` / `today`](#now--today)
- [`of`](#of)
- [`parse` / `parseOrNull`](#parse--parseornull)
- [`fromEpoch` / `fromEpochMs`](#fromepoch--fromepochms)
- [`format`](#format)
- [`iso`](#iso)
- [`sql`](#sql)
- [`date` / `time`](#date--time)
- [`addDays` / `addHours` / `addMinutes` / `addSeconds` / `addMonths` / `addYears`](#adddays--addhours--addminutes--addseconds--addmonths--addyears)
- [`isBefore` / `isAfter` / `isEqual`](#isbefore--isafter--isequal)
- [`isWeekend` / `isWeekday`](#isweekend--isweekday)
- [`dayOfWeek` / `dayOfYear` / `weekOfYear`](#dayofweek--dayofyear--weekofyear)
- [`min` / `max`](#min--max)
- [`startOfDay` / `endOfDay`](#startofday--endofday)
- [`startOfWeek` / `endOfWeek`](#startofweek--endofweek)
- [`startOfMonth` / `endOfMonth`](#startofmonth--endofmonth)
- [`startOfYear` / `endOfYear`](#startofyear--endofyear)
- [`diffInDays` / `diffInSeconds` / `diffInMinutes` / `diffInHours`](#diffindays--diffinseconds--diffinminutes--diffinhours)

---

## `now` / `today`

`today` is `now` zeroed to 00:00:00.

```php
Dt::now();                                       // DateTimeImmutable @ "now"
Dt::now(new DateTimeZone('Europe/Lisbon'));      // DateTimeImmutable @ "now" in Lisbon
Dt::today();                                     // DateTimeImmutable @ today 00:00:00
```

---

## `of`

Build from explicit components.

```php
Dt::of(2026, 5, 23);                                              // 2026-05-23 00:00:00
Dt::of(2026, 5, 23, 10, 15, 30);                                  // 2026-05-23 10:15:30
Dt::of(2026, 1, 1, 0, 0, 0, new DateTimeZone('UTC'));             // 2026-01-01 00:00:00 UTC
```

---

## `parse` / `parseOrNull`

`$format` is optional. When `null`, any expression `DateTimeImmutable::__construct` accepts is allowed.

```php
Dt::parse('2026-05-23');                  // 2026-05-23 00:00:00
Dt::parse('2026-05-23 10:15:30');         // 2026-05-23 10:15:30
Dt::parse('23/05/2026', 'd/m/Y');         // 2026-05-23 00:00:00
Dt::parseOrNull('not a date');            // null
```

---

## `fromEpoch` / `fromEpochMs`

```php
Dt::fromEpoch(0);                  // 1970-01-01 00:00:00 +00:00
Dt::fromEpoch(1700000000);         // 2023-11-14 22:13:20 +00:00
Dt::fromEpochMs(1700000000123);    // 2023-11-14 22:13:20.123000 +00:00
Dt::fromEpoch(1700000000, new DateTimeZone('Europe/Lisbon'));
// 2023-11-14 22:13:20 +00:00 (Lisbon is on WET in November)
```

---

## `format`

```php
$dt = Dt::of(2026, 5, 23, 10, 15, 30);
Dt::format($dt, 'd/m/Y');           // '23/05/2026'
Dt::format($dt, 'D, M j Y g:i a');  // 'Sat, May 23 2026 10:15 am'
```

---

## `iso`

ISO-8601 / RFC 3339. The offset depends on the date/time's timezone.

```php
Dt::iso(Dt::of(2026, 5, 23, 10, 15, 30, new DateTimeZone('UTC')));
// '2026-05-23T10:15:30+00:00'
```

---

## `sql`

```php
Dt::sql(Dt::of(2026, 5, 23, 10, 15, 30));     // '2026-05-23 10:15:30'
```

---

## `date` / `time`

```php
$dt = Dt::of(2026, 5, 23, 10, 15, 30);
Dt::date($dt);     // '2026-05-23'
Dt::time($dt);     // '10:15:30'
```

---

## `addDays` / `addHours` / `addMinutes` / `addSeconds` / `addMonths` / `addYears`

All accept negative values.

```php
$dt = Dt::of(2026, 5, 23, 10, 0, 0);
Dt::addDays($dt, 7);         // 2026-05-30 10:00:00
Dt::addHours($dt, -2);       // 2026-05-23 08:00:00
Dt::addMinutes($dt, 45);     // 2026-05-23 10:45:00
Dt::addSeconds($dt, 30);     // 2026-05-23 10:00:30
Dt::addMonths($dt, 1);       // 2026-06-23 10:00:00
Dt::addYears($dt, -1);       // 2025-05-23 10:00:00
```

---

## `isBefore` / `isAfter` / `isEqual`

`isEqual` compares the absolute UTC instant (microsecond precision) — two times in different timezones that point at the same instant compare equal.

```php
$a = Dt::of(2026, 1, 1);
$b = Dt::of(2026, 6, 1);
Dt::isBefore($a, $b);    // true
Dt::isAfter($a, $b);     // false
Dt::isEqual($a, $a);     // true

Dt::isEqual(
    new DateTimeImmutable('2026-05-23T12:00:00+00:00'),
    new DateTimeImmutable('2026-05-23T09:00:00-03:00'),
);                       // true   (same UTC instant)
```

---

## `isWeekend` / `isWeekday`

`isWeekend` is true on Saturday and Sunday.

```php
Dt::isWeekend(Dt::of(2026, 5, 23));    // true   (Saturday)
Dt::isWeekend(Dt::of(2026, 5, 25));    // false  (Monday)
Dt::isWeekday(Dt::of(2026, 5, 25));    // true
```

---

## `dayOfWeek` / `dayOfYear` / `weekOfYear`

ISO-8601 numbering. `dayOfWeek` returns 1 (Monday) through 7 (Sunday); `dayOfYear` is 1-based.

```php
$dt = Dt::of(2026, 5, 23);     // a Saturday
Dt::dayOfWeek($dt);            // 6
Dt::dayOfYear($dt);            // 143
Dt::weekOfYear($dt);           // 21
Dt::dayOfYear(Dt::of(2026, 1, 1));    // 1
```

---

## `min` / `max`

```php
$a = Dt::of(2026, 1, 1);
$b = Dt::of(2026, 6, 1);
$c = Dt::of(2026, 3, 15);
Dt::min($a, $b, $c);     // 2026-01-01
Dt::max($a, $b, $c);     // 2026-06-01
```

---

## `startOfDay` / `endOfDay`

```php
$dt = Dt::of(2026, 5, 23, 10, 15, 30);
Dt::startOfDay($dt);     // 2026-05-23 00:00:00.000000
Dt::endOfDay($dt);       // 2026-05-23 23:59:59.999999
```

---

## `startOfWeek` / `endOfWeek`

ISO-8601 week (Monday-first).

```php
$sat = Dt::of(2026, 5, 23);     // a Saturday
Dt::startOfWeek($sat);          // 2026-05-18 00:00:00            (Monday)
Dt::endOfWeek($sat);            // 2026-05-24 23:59:59.999999     (Sunday)
```

---

## `startOfMonth` / `endOfMonth`

```php
$dt = Dt::of(2026, 5, 23, 10, 15);
Dt::startOfMonth($dt);     // 2026-05-01 00:00:00
Dt::endOfMonth($dt);       // 2026-05-31 23:59:59.999999
```

---

## `startOfYear` / `endOfYear`

```php
$dt = Dt::of(2026, 5, 23);
Dt::startOfYear($dt);      // 2026-01-01 00:00:00
Dt::endOfYear($dt);        // 2026-12-31 23:59:59.999999
```

---

## `diffInDays` / `diffInSeconds` / `diffInMinutes` / `diffInHours`

Signed difference `b − a`. Minute/hour variants truncate toward zero.

```php
$a = Dt::of(2026, 5, 23, 10, 0, 0);
$b = Dt::of(2026, 5, 30, 12, 30, 0);
Dt::diffInDays($a, $b);        // 7
Dt::diffInHours($a, $b);       // 170      (7 × 24 + 2)
Dt::diffInMinutes($a, $b);     // 10230    (170 × 60 + 30)
Dt::diffInSeconds($a, $b);     // 613800
Dt::diffInDays($b, $a);        // -7       (signed)
```
