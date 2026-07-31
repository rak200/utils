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

- [`is`](#is)
- [`isInt` / `isFloat`](#isint--isfloat)
- [`isPositive` / `isNegative`](#ispositive--isnegative)
- [`isFinite`](#isfinite)
- [`isNan` / `isInfinite`](#isnan--isinfinite)
- [`parseInt` / `parseIntOrNull`](#parseint--parseintornull)
- [`toBase`](#tobase)
- [`parseFloat` / `parseFloatOrNull`](#parsefloat--parsefloatornull)
- [`toStr`](#tostr)
- [`parseNumber` / `parseNumberOrNull`](#parsenumber--parsenumberornull)
- [`clamp`](#clamp)
- [`inRange`](#inrange)
- [`lerp` / `remap`](#lerp--remap)
- [`round`](#round)
- [`format`](#format)
- [`sum`](#sum)
- [`product`](#product)
- [`avg`](#avg)
- [`min` / `max`](#min--max)
- [`abs`](#abs)
- [`sign`](#sign)
- [`pow`](#pow)
- [`sqrt`](#sqrt)
- [`floor` / `ceil`](#floor--ceil)
- [`mod`](#mod)
- [`intDiv`](#intdiv)
- [`add` / `sub` / `mul` / `div`](#add--sub--mul--div)

---

## `is`

Domain predicate — true when `$value` is any flavour of number: native `int`, native `float`, a strict numeric string (no surrounding whitespace), or a `BcMath\Number` instance. Accepts `mixed`. [`Type::isNumeric`](type.md#isnumeric) is an alias.

```php
Num::is(42);                       // true
Num::is(1.5);                      // true
Num::is('1.5');                    // true
Num::is('-1e3');                   // true
Num::is(new Number('1.5'));        // true
Num::is(' 42 ');                   // false  (surrounding whitespace rejected)
Num::is('hello');                  // false
Num::is(null);                     // false
```

[↑ Back to top](#num)

---

## `isInt` / `isFloat`

Strict native-type checks. For the umbrella "is a number" predicate (int/float/numeric-string/Number), see [`is`](#is).

```php
Num::isInt(42);                           // true
Num::isInt(42.0);                         // false
Num::isInt('42');                         // false
Num::isFloat(42.0);                       // true
Num::isFloat(42);                         // false
```

[↑ Back to top](#num)

---

## `isPositive` / `isNegative`

Sign predicates for any number — `int`, `float`, or `BcMath\Number`. `isPositive` is `> 0`, `isNegative` is `< 0`; zero is neither.

```php
Num::isPositive(5);                 // true
Num::isPositive(0.001);             // true
Num::isPositive(new Number('0.5')); // true
Num::isPositive(0);                 // false
Num::isPositive(-1);                // false

Num::isNegative(-5);                // true
Num::isNegative(new Number('-0.5'));// true
Num::isNegative(0);                 // false
```

[↑ Back to top](#num)

---

## `isFinite`

True for a finite number — an int, a `BcMath\Number`, a finite float, or a numeric string whose float value is finite. `INF`, `-INF`, `NAN`, overflowing numeric strings, and non-numeric values are false. Accepts `mixed`, so it works as a guard.

```php
Num::isFinite(42);          // true
Num::isFinite(3.14);        // true
Num::isFinite('2.5');       // true
Num::isFinite(INF);         // false
Num::isFinite(NAN);         // false
Num::isFinite('1e400');     // false  (overflows to INF)
Num::isFinite('abc');       // false
```

[↑ Back to top](#num)

---

## `isNan` / `isInfinite`

The complements of [`isFinite`](#isfinite). `isNan` is true only for the float `NAN`; `isInfinite` is true for `INF`/`-INF` and for numeric strings that overflow to infinity. Both accept `mixed` and return false for ints, `Number`s, and non-numeric values.

```php
Num::isNan(NAN);            // true
Num::isNan(1.0);            // false
Num::isNan('abc');          // false

Num::isInfinite(INF);       // true
Num::isInfinite(-INF);      // true
Num::isInfinite('1e400');   // true   (overflows to INF)
Num::isInfinite(1.0);       // false
Num::isInfinite(NAN);       // false
```

[↑ Back to top](#num)

---

## `parseInt` / `parseIntOrNull`

Bases 2-36. Strict — rejects any character outside the base alphabet (including a decimal point and surrounding whitespace). Scalar-only — use [`parseNumber`](#parsenumber--parsenumberornull) for big integers that don't fit in `PHP_INT_MAX`.

```php
Num::parseInt('42');               // 42
Num::parseInt('-17');              // -17
Num::parseInt('ff', 16);           // 255
Num::parseInt('1010', 2);          // 10
Num::parseIntOrNull('hello');      // null
Num::parseIntOrNull('12.5');       // null
Num::parseIntOrNull(' 42 ');       // null  (surrounding whitespace rejected)
```

[↑ Back to top](#num)

---

## `toBase`

The inverse of [`parseInt`](#parseint--parseintornull): renders an `int` as a base-2-to-36 string using digits `0-9a-z` (lowercase), with a leading `-` for negatives. `Num::parseInt(Num::toBase($n, $b), $b) === $n`.

```php
Num::toBase(255, 16);       // 'ff'
Num::toBase(10, 2);         // '1010'
Num::toBase(511, 8);        // '777'
Num::toBase(35, 36);        // 'z'
Num::toBase(-255, 16);      // '-ff'
Num::toBase(0, 2);          // '0'
```

[↑ Back to top](#num)

---

## `parseFloat` / `parseFloatOrNull`

Strict — rejects non-numeric strings and surrounding whitespace.

```php
Num::parseFloat('3.14');           // 3.14
Num::parseFloat('-1.5e3');         // -1500.0
Num::parseFloatOrNull('hello');    // null
Num::parseFloatOrNull(' 3.14 ');   // null  (surrounding whitespace rejected)
```

[↑ Back to top](#num)

---

## `toStr`

The exact string form of a number — the one that reads back as the same value. The inverse of [`parseFloat`](#parsefloat--parsefloatornull): `Num::parseFloat(Num::toStr($f)) === $f` holds for **every finite float**.

The `(string)` cast does not: it goes through `precision` (14 significant digits) and silently collapses distinct values.

```php
Num::toStr(0.1 + 0.2);       // '0.30000000000000004'
(string) (0.1 + 0.2);        // '0.3'                 ← distinct values collapse

Num::toStr(1 / 3);           // '0.3333333333333333'
(string) (1 / 3);            // '0.33333333333333'

Num::toStr(1.0);             // '1.0'   ← keeps the float marker
(string) 1.0;                // '1'
```

`-0.0` keeps its sign. Ints and [`Number`](#parsenumber--parsenumberornull)s are already exact under a cast and pass straight through, a `Number` keeping its trailing zeros:

```php
Num::toStr(-0.0);                    // '-0.0'
Num::toStr(PHP_INT_MIN);             // '-9223372036854775808'
Num::toStr(new Number('1.500'));     // '1.500'
```

Non-finite floats throw `InvalidArgumentException`: no string form of them reads back through `parseFloat`, so there is no round-trippable answer to give. Guard with [`isFinite`](#isfinite) when the input may be one.

```php
Num::toStr(NAN);   // InvalidArgumentException: Cannot represent NAN as an exact string.
Num::toStr(INF);   // InvalidArgumentException: Cannot represent INF as an exact string.
```

> Not to be confused with [`Filter::toStr`](filter.md#tostr), which is the lenient `mixed` → `?string` coercer for untrusted input. This one is the precision-preserving formatter; the shared name is the only thing they have in common.

[↑ Back to top](#num)

---

## `parseNumber` / `parseNumberOrNull`

Arbitrary-precision parse. Accepts exactly the strings [`is`](#is) reports as numeric: decimal and scientific notation. Scientific input is expanded to its exact decimal form (no precision lost). Surrounding whitespace is rejected.

```php
Num::parseNumber('123456789012345678901234567890.5');
// BcMath\Number('123456789012345678901234567890.5')

Num::parseNumber('-0.0001');                  // BcMath\Number('-0.0001')
Num::parseNumber('1.5e3');                    // BcMath\Number('1500')
Num::parseNumber('1.5e-3');                   // BcMath\Number('0.0015')
Num::parseNumberOrNull(' 42 ');               // null  (surrounding whitespace rejected)
Num::parseNumberOrNull('abc');                // null
Num::parseNumberOrNull('1e999999999');        // null  (decimal form impractical)
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

## `lerp` / `remap`

`lerp` interpolates linearly between `$a` and `$b` by `$t` (`a + (b - a) * t`); `$t` in `[0, 1]` interpolates, outside it extrapolates. `remap` maps a value from one range to another linearly. Neither clamps — compose with [`clamp`](#clamp) to bound. `remap` throws when `$inMin` equals `$inMax`. Both widen to `BcMath\Number` when any operand is one.

```php
Num::lerp(0, 10, 0.5);                          // 5.0
Num::lerp(10, 20, 0.25);                        // 12.5
Num::lerp(0, 10, 1.5);                          // 15.0  (extrapolates)
Num::remap(5, 0, 10, 0, 100);                   // 50    (int — all-int, evenly divisible)
Num::remap(0.5, 0, 1, -100, 100);               // 0.0
Num::remap(120, 0, 100, 0, 1);                  // 1.2   (no clamp)
Num::lerp(new Number('0'), new Number('10'), new Number('0.5')); // BcMath\Number('5')
Num::remap(5, 0, 0, 0, 100);                    // throws InvalidArgumentException
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

**An all-int input keeps its `int` type.** `sum` declares `($values is iterable<int> ? int : float|int|Number)`, so feeding it a `list<int>` (or any `iterable<int>` — the key type is irrelevant, and a generator qualifies) gives a plain `int`, which can be returned straight from an `int`-typed method. One non-int element takes the wide branch:

```php
/** @param list<int> $counts */
public function total(array $counts): int
{
    return Num::sum($counts);      // int — no cast, no PHPStan complaint
}
```

**The caveat the type cannot express:** an int sum that passes `PHP_INT_MAX` promotes to `float` at runtime. `sum` still declares `int` there, exactly as PHP's own `array_sum` does under PHPStan. If a sum can plausibly reach that magnitude, feed `BcMath\Number` values and take the wide branch deliberately. [`min` / `max`](#min--max) carry no such caveat.

[↑ Back to top](#num)

---

## `product`

The companion to [`sum`](#sum). Returns `1` (int) for an empty input. Widens to `BcMath\Number` when any element is one.

```php
Num::product([2, 3, 4]);                                  // 24
Num::product([]);                                         // 1
Num::product([5, 0, 3]);                                  // 0
Num::product([3, new Number('2.5')]);                     // BcMath\Number('7.5')
```

Preserves `int` through an all-int input just as [`sum`](#sum) does, with the same overflow caveat — reached far sooner here, since a product grows multiplicatively.

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

Both preserve `int` through an all-int input, like [`sum`](#sum) — and here the guarantee is exact, with no overflow caveat: the result is one of the elements, so there is no arithmetic that could widen it.

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

---

## `intDiv`

Integer division, truncated toward zero (matching PHP's `intdiv()`). The companion to [`mod`](#mod). Throws when the divisor is zero.

```php
Num::intDiv(7, 2);       // 3
Num::intDiv(-7, 2);      // -3   (truncates toward zero)
Num::intDiv(7, -2);      // -3
Num::intDiv(1, 0);       // throws InvalidArgumentException
```

[↑ Back to top](#num)

---

## `add` / `sub` / `mul` / `div`

The four basic operations over `int|float|BcMath\Number`, widening to `Number` when any operand is one — so mixed-type arithmetic needs no manual `instanceof Number` branching. `div` follows PHP's `/` (an int when both operands are ints and evenly divisible, a float otherwise) and throws on a zero divisor.

```php
Num::add(2, 3);        // 5
Num::sub(5, 2);        // 3
Num::mul(4, 2.5);      // 10.0
Num::div(7, 2);        // 3.5
Num::div(6, 3);        // 2     (int, evenly divisible)
Num::div(1, 0);        // throws InvalidArgumentException
Num::add(new Number('0.1'), new Number('0.2'));   // BcMath\Number('0.3')
Num::mul(new Number('2'), 3);                      // BcMath\Number('6')
```

[↑ Back to top](#num)
