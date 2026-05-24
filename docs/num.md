# Num

Numeric helpers for parsing, formatting, and aggregation.

```php
use Rak200\Utils\Num;
use RoundingMode;
```

## Contents

- [`isInteger` / `isFloat` / `isNumeric`](#isinteger--isfloat--isnumeric)
- [`parseInt` / `parseIntOrNull`](#parseint--parseintornull)
- [`parseFloat` / `parseFloatOrNull`](#parsefloat--parsefloatornull)
- [`clamp`](#clamp)
- [`inRange`](#inrange)
- [`round`](#round)
- [`format`](#format)
- [`sum`](#sum)
- [`avg`](#avg)
- [`min` / `max`](#min--max)
- [`abs`](#abs)
- [`sign`](#sign)

---

## `isInteger` / `isFloat` / `isNumeric`

`isInteger`/`isFloat` are strict type checks; `isNumeric` also accepts numeric strings.

```php
Num::isInteger(42);        // true
Num::isInteger(42.0);      // false
Num::isFloat(42.0);        // true
Num::isFloat(42);          // false
Num::isNumeric('1.5');     // true
Num::isNumeric('hello');   // false
```

---

## `parseInt` / `parseIntOrNull`

Bases 2-36. Strict — rejects any character outside the base alphabet (including a decimal point).

```php
Num::parseInt('42');               // 42
Num::parseInt('-17');              // -17
Num::parseInt('ff', 16);           // 255
Num::parseInt('1010', 2);          // 10
Num::parseIntOrNull('hello');      // null
Num::parseIntOrNull('12.5');       // null
```

---

## `parseFloat` / `parseFloatOrNull`

```php
Num::parseFloat('3.14');           // 3.14
Num::parseFloat('-1.5e3');         // -1500.0
Num::parseFloatOrNull('hello');    // null
```

---

## `clamp`

Constrains to the closed interval `[$min, $max]`.

```php
Num::clamp(15, 0, 10);     // 10
Num::clamp(-5, 0, 10);     // 0
Num::clamp(5, 0, 10);      // 5
```

---

## `inRange`

Closed interval.

```php
Num::inRange(5, 0, 10);    // true
Num::inRange(10, 0, 10);   // true
Num::inRange(11, 0, 10);   // false
```

---

## `round`

Defaults to `RoundingMode::HalfAwayFromZero`.

```php
Num::round(2.5);                                          // 3.0
Num::round(2.4);                                          // 2.0
Num::round(3.14159, 2);                                   // 3.14
Num::round(2.5, 0, RoundingMode::HalfTowardsZero);        // 2.0
```

---

## `format`

```php
Num::format(1234.5678);              // '1,234.57'
Num::format(1234.5, 2, ',', '.');    // '1.234,50'
```

---

## `sum`

Returns `0` (int) for an empty input.

```php
Num::sum([1, 2, 3, 4]);         // 10
Num::sum([1.5, 2.5, 3.0]);      // 7.0
```

---

## `avg`

```php
Num::avg([2, 4, 6, 8]);         // 5.0
Num::avg([1.0, 2.0, 3.0]);      // 2.0
```

---

## `min` / `max`

```php
Num::min([3, 1, 4, 1, 5, 9]);   // 1
Num::max([3, 1, 4, 1, 5, 9]);   // 9
```

---

## `abs`

```php
Num::abs(-5);        // 5
Num::abs(-3.14);     // 3.14
```

---

## `sign`

Returns `-1`, `0`, or `1`.

```php
Num::sign(42);       // 1
Num::sign(-7);       // -1
Num::sign(0);        // 0
```
