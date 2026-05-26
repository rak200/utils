# Num

[← Reference](README.md)

Numeric helpers for parsing, formatting, arithmetic and aggregation.

```php
use BcMath\Number;
use Rak200\Utils\Num;
use RoundingMode;
```

Aggregation and per-element methods (`sum`/`avg`/`min`/`max`/`abs`/`sign`/`clamp`/`inRange`/`pow`/`sqrt`/`floor`/`ceil`/`mod`/`round`/`format`) accept `int|float|BcMath\Number` and propagate `Number` when any input is one — no silent narrowing to `float`.

## Contents

- [`isInteger` / `isFloat` / `isNumeric`](#isinteger--isfloat--isnumeric)
- [`isPositiveInt` / `isNegativeInt` / `isNonNegativeInt`](#ispositiveint--isnegativeint--isnonnegativeint)
- [`parseInt` / `parseIntOrNull`](#parseint--parseintornull)
- [`parseFloat` / `parseFloatOrNull`](#parsefloat--parsefloatornull)
- [`parseNumber` / `parseNumberOrNull`](#parsenumber--parsenumberornull)
- [`clamp`](#clamp)
- [`inRange`](#inrange)
- [`round`](#round)
- [`format`](#format)
- [`sum`](#sum)
- [`avg`](#avg)
- [`min` / `max`](#min--max)
- [`abs`](#abs)
- [`sign`](#sign)
- [`pow`](#pow)
- [`sqrt`](#sqrt)
- [`floor` / `ceil`](#floor--ceil)
- [`mod`](#mod)

---

## `isInteger` / `isFloat` / `isNumeric`

`isInteger`/`isFloat` are strict type checks; `isNumeric` also accepts numeric strings and `BcMath\Number` instances.

```php
Num::isInteger(42);                       // true
Num::isInteger(42.0);                     // false
Num::isFloat(42.0);                       // true
Num::isFloat(42);                         // false
Num::isNumeric('1.5');                    // true
Num::isNumeric('hello');                  // false
Num::isNumeric(new Number('1.5'));        // true
```

[↑ Back to top](#num)

---

## `isPositiveInt` / `isNegativeInt` / `isNonNegativeInt`

Strict — the value must be a native `int` (floats and numeric strings are rejected). Accept `mixed` so they can be used as guards.

```php
Num::isPositiveInt(1);          // true
Num::isPositiveInt(0);          // false
Num::isPositiveInt('1');        // false
Num::isPositiveInt(1.5);        // false

Num::isNegativeInt(-1);         // true
Num::isNegativeInt(0);          // false

Num::isNonNegativeInt(0);       // true
Num::isNonNegativeInt(7);       // true
Num::isNonNegativeInt(-1);      // false
```

[↑ Back to top](#num)

---

## `parseInt` / `parseIntOrNull`

Bases 2-36. Strict — rejects any character outside the base alphabet (including a decimal point). Scalar-only — use [`parseNumber`](#parsenumber--parsenumberornull) for big integers that don't fit in `PHP_INT_MAX`.

```php
Num::parseInt('42');               // 42
Num::parseInt('-17');              // -17
Num::parseInt('ff', 16);           // 255
Num::parseInt('1010', 2);          // 10
Num::parseIntOrNull('hello');      // null
Num::parseIntOrNull('12.5');       // null
```

[↑ Back to top](#num)

---

## `parseFloat` / `parseFloatOrNull`

```php
Num::parseFloat('3.14');           // 3.14
Num::parseFloat('-1.5e3');         // -1500.0
Num::parseFloatOrNull('hello');    // null
```

[↑ Back to top](#num)

---

## `parseNumber` / `parseNumberOrNull`

Arbitrary-precision parse. Decimal notation only — scientific notation (`1e10`) is rejected.

```php
Num::parseNumber('123456789012345678901234567890.5');
// BcMath\Number('123456789012345678901234567890.5')

Num::parseNumber('-0.0001');                  // BcMath\Number('-0.0001')
Num::parseNumberOrNull('1e10');               // null  (scientific notation rejected)
Num::parseNumberOrNull('abc');                // null
```

[↑ Back to top](#num)

---

## `clamp`

Constrains to the closed interval `[$min, $max]`. Propagates `BcMath\Number` when any operand is one.

```php
Num::clamp(15, 0, 10);                                    // 10
Num::clamp(-5, 0, 10);                                    // 0
Num::clamp(5, 0, 10);                                     // 5
Num::clamp(new Number('15'), new Number('0'), new Number('10'));
// BcMath\Number('10')
```

[↑ Back to top](#num)

---

## `inRange`

Closed interval.

```php
Num::inRange(5, 0, 10);                                   // true
Num::inRange(10, 0, 10);                                  // true
Num::inRange(11, 0, 10);                                  // false
Num::inRange(new Number('5.5'), new Number('5'), new Number('6'));
// true
```

[↑ Back to top](#num)

---

## `round`

Defaults to `RoundingMode::HalfAwayFromZero`. For `BcMath\Number` input, returns a `Number` at the requested precision (no float-precision loss).

```php
Num::round(2.5);                                          // 3.0
Num::round(2.4);                                          // 2.0
Num::round(3.14159, 2);                                   // 3.14
Num::round(2.5, 0, RoundingMode::HalfTowardsZero);        // 2.0
Num::round(new Number('1.2345'), 2);                      // BcMath\Number('1.23')
```

[↑ Back to top](#num)

---

## `format`

For `BcMath\Number` input, the value is rounded to `$decimals` (half away from zero) and formatted from its canonical decimal string — no float-precision loss.

```php
Num::format(1234.5678);                                   // '1,234.57'
Num::format(1234.5, 2, ',', '.');                         // '1.234,50'
Num::format(new Number('12345678901234567890.5'), 2);
// '12,345,678,901,234,567,890.50'
Num::format(new Number('1234.567'), 2, ',', '.');         // '1.234,57'
```

[↑ Back to top](#num)

---

## `sum`

Returns `0` (int) for an empty input. Widens to `BcMath\Number` when any element is one.

```php
Num::sum([1, 2, 3, 4]);                                   // 10
Num::sum([1.5, 2.5, 3.0]);                                // 7.0
Num::sum([1, 2, new Number('0.5')]);                      // BcMath\Number('3.5')
```

[↑ Back to top](#num)

---

## `avg`

```php
Num::avg([2, 4, 6, 8]);                                   // 5.0
Num::avg([1.0, 2.0, 3.0]);                                // 2.0
Num::avg([new Number('1'), new Number('2'), new Number('3')]);
// BcMath\Number('2')
```

[↑ Back to top](#num)

---

## `min` / `max`

```php
Num::min([3, 1, 4, 1, 5, 9]);                             // 1
Num::max([3, 1, 4, 1, 5, 9]);                             // 9
Num::max([1, new Number('2.5'), 2]);                      // BcMath\Number('2.5')
```

[↑ Back to top](#num)

---

## `abs`

```php
Num::abs(-5);                                             // 5
Num::abs(-3.14);                                          // 3.14
Num::abs(new Number('-5'));                               // BcMath\Number('5')
```

[↑ Back to top](#num)

---

## `sign`

Returns `-1`, `0`, or `1`.

```php
Num::sign(42);                                            // 1
Num::sign(-7);                                            // -1
Num::sign(0);                                             // 0
Num::sign(new Number('-3.2'));                            // -1
```

[↑ Back to top](#num)

---

## `pow`

Exponentiation. Widens to `BcMath\Number` when either operand is one.

```php
Num::pow(2, 10);                                          // 1024
Num::pow(2, -2);                                          // 0.25
Num::pow(new Number('2'), 100);                           // BcMath\Number('1267650600228229401496703205376')
```

[↑ Back to top](#num)

---

## `sqrt`

Throws when the input is negative. For `BcMath\Number`, the result is a `Number` (default BCMath scale).

```php
Num::sqrt(16);                                            // 4.0
Num::sqrt(2);                                             // 1.4142135623730951
Num::sqrt(new Number('2'));                               // BcMath\Number('1.4142135623')
```

[↑ Back to top](#num)

---

## `floor` / `ceil`

Rounds down/up to `$precision` decimal places (default `0`).

```php
Num::floor(2.9);                                          // 2.0
Num::ceil(2.1);                                           // 3.0
Num::floor(2.49, 1);                                      // 2.4
Num::ceil(2.41, 1);                                       // 2.5
Num::floor(new Number('2.49'), 1);                        // BcMath\Number('2.4')
```

[↑ Back to top](#num)

---

## `mod`

Truncated modulo — the sign of the result follows the dividend, matching PHP's `%`. Throws when the divisor is zero.

```php
Num::mod(7, 3);                                           // 1
Num::mod(-7, 3);                                          // -1   (sign follows -7)
Num::mod(2.5, 1.0);                                       // 0.5
Num::mod(new Number('-7'), new Number('3'));              // BcMath\Number('-1')
```

[↑ Back to top](#num)
