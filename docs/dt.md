# Dt

[← Reference](README.md)

Date/time helpers built on `DateTimeImmutable`. Inputs accept any `DateTimeInterface`; returns are always immutable.

```php
use Rak200\Utils\Dt;
use DateTimeZone;
```

## Contents

- [`is`](#is)
- [`isValid`](#isvalid)
- [`now` / `today`](#now--today)
- [`of`](#of)
- [`parse` / `parseOrNull`](#parse--parseornull)
- [`fromEpoch` / `fromEpochMs`](#fromepoch--fromepochms)
- [`fromInterface`](#frominterface)
- [`toEpoch` / `toEpochFloat`](#toepoch--toepochfloat)
- [`format`](#format)
- [`iso`](#iso)
- [`sql`](#sql)
- [`date` / `time`](#date--time)
- [`addDays` / `addHours` / `addMinutes` / `addSeconds` / `addMonths` / `addYears`](#adddays--addhours--addminutes--addseconds--addmonths--addyears)
- [`isBefore` / `isAfter` / `isEqual`](#isbefore--isafter--isequal)
- [`isWeekend` / `isWeekday`](#isweekend--isweekday)
- [`isPast` / `isFuture`](#ispast--isfuture)
- [`dayOfWeek` / `dayOfYear` / `weekOfYear`](#dayofweek--dayofyear--weekofyear)
- [`min` / `max`](#min--max)
- [`period`](#period)
- [`startOfDay` / `endOfDay`](#startofday--endofday)
- [`startOfWeek` / `endOfWeek`](#startofweek--endofweek)
- [`startOfMonth` / `endOfMonth`](#startofmonth--endofmonth)
- [`startOfYear` / `endOfYear`](#startofyear--endofyear)
- [`diffDays` / `diffSeconds` / `diffMinutes` / `diffHours`](#diffdays--diffseconds--diffminutes--diffhours)

---

## `is`

Domain predicate — true when `$value` is a `DateTimeInterface` instance (either `DateTimeImmutable` or the mutable `DateTime`). Accepts `mixed`.

```php
Dt::is(new DateTimeImmutable());   // true
Dt::is(new DateTime());            // true
Dt::is('2026-05-23');              // false   (a date string, not a date object)
Dt::is(null);                      // false
```

[↑ Back to top](#dt)

---

## `isValid`

True when `$year`-`$month`-`$day` is a valid Gregorian calendar date. Wraps `checkdate` — handy for validating components before building a date with [`of`](#of).

```php
Dt::isValid(2024, 2, 29);   // true    (leap year)
Dt::isValid(2025, 2, 29);   // false   (not a leap year)
Dt::isValid(2026, 4, 31);   // false   (April has 30 days)
Dt::isValid(2026, 13, 1);   // false   (month out of range)
```

[↑ Back to top](#dt)

---

## `now` / `today`

`today` is `now` zeroed to 00:00:00.

```php
Dt::now();                                       // DateTimeImmutable @ "now"
Dt::now(new DateTimeZone('Europe/Lisbon'));      // DateTimeImmutable @ "now" in Lisbon
Dt::today();                                     // DateTimeImmutable @ today 00:00:00
```

[↑ Back to top](#dt)

---

## `of`

Build from explicit components.

```php
Dt::of(2026, 5, 23);                                              // 2026-05-23 00:00:00
Dt::of(2026, 5, 23, 10, 15, 30);                                  // 2026-05-23 10:15:30
Dt::of(2026, 1, 1, 0, 0, 0, new DateTimeZone('UTC'));             // 2026-01-01 00:00:00 UTC
```

[↑ Back to top](#dt)

---

## `parse` / `parseOrNull`

`$format` is optional. When `null`, any expression `DateTimeImmutable::__construct` accepts is allowed.

```php
Dt::parse('2026-05-23');                  // 2026-05-23 00:00:00
Dt::parse('2026-05-23 10:15:30');         // 2026-05-23 10:15:30
Dt::parse('23/05/2026', 'd/m/Y');         // 2026-05-23 00:00:00
Dt::parseOrNull('not a date');            // null
```

[↑ Back to top](#dt)

---

## `fromEpoch` / `fromEpochMs`

```php
Dt::fromEpoch(0);                  // 1970-01-01 00:00:00 +00:00
Dt::fromEpoch(1700000000);         // 2023-11-14 22:13:20 +00:00
Dt::fromEpochMs(1700000000123);    // 2023-11-14 22:13:20.123000 +00:00
Dt::fromEpoch(1700000000, new DateTimeZone('Europe/Lisbon'));
// 2023-11-14 22:13:20 +00:00 (Lisbon is on WET in November)
```

[↑ Back to top](#dt)

---

## `fromInterface`

Normalises any `DateTimeInterface` to `DateTimeImmutable` — the entry point for a date-time arriving from outside, a mutable `DateTime` included. Dates in this library are always immutable, so this is where foreign instances become canonical.

An instance that is already immutable is returned **as-is**; the native `DateTimeImmutable::createFromInterface()` always allocates a new object.

```php
$mutable = new DateTime('2026-05-23 14:30:45');
$immutable = new DateTimeImmutable('2026-05-23 14:30:45');

Dt::fromInterface($mutable);                    // DateTimeImmutable — a snapshot, decoupled from $mutable
Dt::fromInterface($immutable) === $immutable;   // true — same instance, no allocation
```

[↑ Back to top](#dt)

---

## `toEpoch` / `toEpochFloat`

The inverse of [`fromEpoch`](#fromepoch--fromepochms): `toEpoch` returns whole seconds and drops any sub-second component; `toEpochFloat` keeps the microsecond fraction.

`toEpochFloat` is built from the whole seconds plus the microseconds read separately, never from `format('U.u')` — that pattern glues a negative seconds part to the always-positive microsecond fraction, so a **pre-epoch** instant comes out skewed by up to a second. That trap is the reason this helper exists.

```php
Dt::toEpoch(Dt::fromEpoch(1700000000));         // 1700000000
Dt::toEpoch(Dt::fromEpochMs(1500));             // 1     (0.5s dropped)
Dt::toEpochFloat(Dt::fromEpochMs(1500));        // 1.5

$preEpoch = Dt::fromEpochMs(-500);              // 500 ms before the epoch
Dt::toEpochFloat($preEpoch);                    // -0.5
(float) $preEpoch->format('U.u');               // -1.5   ← the native trap
```

[↑ Back to top](#dt)

---

## `format`

```php
$dt = Dt::of(2026, 5, 23, 10, 15, 30);
Dt::format($dt, 'd/m/Y');           // '23/05/2026'
Dt::format($dt, 'D, M j Y g:i a');  // 'Sat, May 23 2026 10:15 am'
```

[↑ Back to top](#dt)

---

## `iso`

ISO-8601 / RFC 3339. The offset depends on the date/time's timezone.

```php
Dt::iso(Dt::of(2026, 5, 23, 10, 15, 30, new DateTimeZone('UTC')));
// '2026-05-23T10:15:30+00:00'
```

[↑ Back to top](#dt)

---

## `sql`

```php
Dt::sql(Dt::of(2026, 5, 23, 10, 15, 30));     // '2026-05-23 10:15:30'
```

[↑ Back to top](#dt)

---

## `date` / `time`

```php
$dt = Dt::of(2026, 5, 23, 10, 15, 30);
Dt::date($dt);     // '2026-05-23'
Dt::time($dt);     // '10:15:30'
```

[↑ Back to top](#dt)

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

[↑ Back to top](#dt)

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

[↑ Back to top](#dt)

---

## `isWeekend` / `isWeekday`

`isWeekend` is true on Saturday and Sunday.

```php
Dt::isWeekend(Dt::of(2026, 5, 23));    // true   (Saturday)
Dt::isWeekend(Dt::of(2026, 5, 25));    // false  (Monday)
Dt::isWeekday(Dt::of(2026, 5, 25));    // true
```

[↑ Back to top](#dt)

---

## `isPast` / `isFuture`

Compared against the current instant (`now`), so the timezone of the argument does not matter. Strict — exactly "now" is neither.

```php
Dt::isPast(Dt::of(2000, 1, 1));      // true
Dt::isFuture(Dt::of(2099, 1, 1));    // true
Dt::isPast(Dt::of(2099, 1, 1));      // false
```

[↑ Back to top](#dt)

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

[↑ Back to top](#dt)

---

## `min` / `max`

```php
$a = Dt::of(2026, 1, 1);
$b = Dt::of(2026, 6, 1);
$c = Dt::of(2026, 3, 15);
Dt::min($a, $b, $c);     // 2026-01-01
Dt::max($a, $b, $c);     // 2026-06-01
```

[↑ Back to top](#dt)

---

## `period`

Lazily yields `DateTimeImmutable` instants from `$start` to `$end`, advancing by `$step` (default one day, a `DateInterval`). Ascending only — `$end` is included unless `$inclusive: false`; nothing is yielded when `$start` is after `$end`. Throws if `$step` does not move the date forward (a zero or inverted interval would loop forever).

```php
use DateInterval;

foreach (Dt::period(Dt::of(2026, 1, 1), Dt::of(2026, 1, 4)) as $d) {
    echo Dt::date($d), ' ';            // 2026-01-01 2026-01-02 2026-01-03 2026-01-04
}

iterator_to_array(Dt::period(Dt::of(2026, 1, 1), Dt::of(2026, 1, 4), inclusive: false));
// [2026-01-01, 2026-01-02, 2026-01-03]

Dt::period(Dt::of(2026, 1, 1), Dt::of(2026, 4, 1), new DateInterval('P1M'));
// yields 2026-01-01, 2026-02-01, 2026-03-01, 2026-04-01

iterator_to_array(Dt::period(Dt::of(2026, 1, 4), Dt::of(2026, 1, 1)));   // [] (start after end)
```

[↑ Back to top](#dt)

---

## `startOfDay` / `endOfDay`

```php
$dt = Dt::of(2026, 5, 23, 10, 15, 30);
Dt::startOfDay($dt);     // 2026-05-23 00:00:00.000000
Dt::endOfDay($dt);       // 2026-05-23 23:59:59.999999
```

[↑ Back to top](#dt)

---

## `startOfWeek` / `endOfWeek`

ISO-8601 week (Monday-first).

```php
$sat = Dt::of(2026, 5, 23);     // a Saturday
Dt::startOfWeek($sat);          // 2026-05-18 00:00:00            (Monday)
Dt::endOfWeek($sat);            // 2026-05-24 23:59:59.999999     (Sunday)
```

[↑ Back to top](#dt)

---

## `startOfMonth` / `endOfMonth`

```php
$dt = Dt::of(2026, 5, 23, 10, 15);
Dt::startOfMonth($dt);     // 2026-05-01 00:00:00
Dt::endOfMonth($dt);       // 2026-05-31 23:59:59.999999
```

[↑ Back to top](#dt)

---

## `startOfYear` / `endOfYear`

```php
$dt = Dt::of(2026, 5, 23);
Dt::startOfYear($dt);      // 2026-01-01 00:00:00
Dt::endOfYear($dt);        // 2026-12-31 23:59:59.999999
```

[↑ Back to top](#dt)

---

## `diffDays` / `diffSeconds` / `diffMinutes` / `diffHours`

Signed difference `b − a`. Minute/hour variants truncate toward zero.

```php
$a = Dt::of(2026, 5, 23, 10, 0, 0);
$b = Dt::of(2026, 5, 30, 12, 30, 0);
Dt::diffDays($a, $b);        // 7
Dt::diffHours($a, $b);       // 170      (7 × 24 + 2)
Dt::diffMinutes($a, $b);     // 10230    (170 × 60 + 30)
Dt::diffSeconds($a, $b);     // 613800
Dt::diffDays($b, $a);        // -7       (signed)
```

[↑ Back to top](#dt)
