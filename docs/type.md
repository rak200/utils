# Type

[← Reference](README.md)

Type-checking predicates. Every method accepts `mixed`, so calls work as guards on values whose shape is not yet known.

```php
use Rak200\Utils\Type;
```

Topic-specific predicates live with their tier-1 class:

- **String shape** — [`Str::isBlank`](str.md#isblank--isnotblank--isempty--isnonemptystr), [`Str::isNonEmptyStr`](str.md#isblank--isnotblank--isempty--isnonemptystr).
- **Array shape** — [`Arr::isList`](arr.md#islist--isassoc--isnonemptyarray), [`Arr::isAssoc`](arr.md#islist--isassoc--isnonemptyarray), [`Arr::isNonEmptyArray`](arr.md#islist--isassoc--isnonemptyarray).
- **Integer sign** — [`Num::isPositiveInt`](num.md#ispositiveint--isnegativeint--isnonnegativeint), [`Num::isNegativeInt`](num.md#ispositiveint--isnegativeint--isnonnegativeint), [`Num::isNonNegativeInt`](num.md#ispositiveint--isnegativeint--isnonnegativeint).

## Contents

- [`isStr` / `isBool` / `isInt` / `isFloat` / `isArray` / `isObject` / `isCallable` / `isIterable` / `isNull` / `isScalar` / `isResource`](#basic-type-checks)
- [`isNumeric`](#isnumeric)
- [`isNumericStr` / `isIntLike`](#isnumericstr--isintlike)
- [`isInstanceOf` / `isA`](#isinstanceof--isa)
- [`isClassName` / `isInterfaceName`](#isclassname--isinterfacename)

---

## Basic type checks

Thin, uniform wrappers around PHP's `is_*()` family.

```php
Type::isStr('hi');                       // true
Type::isStr(42);                         // false

Type::isBool(true);                      // true
Type::isBool(1);                         // false

Type::isInt(0);                          // true
Type::isInt('0');                        // false

Type::isFloat(1.5);                      // true
Type::isFloat(1);                        // false

Type::isArray([]);                       // true
Type::isArray(new ArrayIterator([]));    // false

Type::isObject(new stdClass());          // true
Type::isObject('stdClass');              // false

Type::isCallable(strlen(...));           // true
Type::isCallable('does_not_exist_xyz');  // false

Type::isIterable([1, 2, 3]);             // true
Type::isIterable(new ArrayIterator([])); // true
Type::isIterable(new stdClass());        // false

Type::isNull(null);                      // true
Type::isNull(false);                     // false

Type::isScalar(1.5);                     // true
Type::isScalar([]);                      // false

Type::isResource(fopen('php://memory', 'rb'));   // true
```

[↑ Back to top](#type)

---

## `isNumeric`

True for ints, floats, numeric strings, and `BcMath\Number` instances (mirrors `Num::isNumeric`).

```php
Type::isNumeric(42);                     // true
Type::isNumeric('1.5');                  // true
Type::isNumeric('-1e3');                 // true
Type::isNumeric(new Number('123.45'));   // true
Type::isNumeric('hello');                // false
Type::isNumeric(true);                   // false
```

[↑ Back to top](#type)

---

## `isNumericStr` / `isIntLike`

`isNumericStr` is true for strings PHP accepts as numeric (decimals, exponent, leading sign, surrounding whitespace). `isIntLike` is stricter: an int, or a string parseable as one (sign + digits only — no decimal point, exponent, or whitespace).

```php
Type::isNumericStr('42');      // true
Type::isNumericStr('-1.5');    // true
Type::isNumericStr('1e3');     // true
Type::isNumericStr(42);        // false   (not a string)

Type::isIntLike(42);           // true
Type::isIntLike('42');         // true
Type::isIntLike('-7');         // true
Type::isIntLike('1.0');        // false
Type::isIntLike('1e3');        // false
Type::isIntLike(' 42 ');       // false
```

[↑ Back to top](#type)

---

## `isInstanceOf` / `isA`

`isInstanceOf` is strict: $value must be an object. `isA` is a shortcut for [`is_a()`](https://www.php.net/manual/en/function.is-a.php) with `$allow_string = true` — it also accepts a class-name string and checks the class hierarchy (autoload-aware).

```php
Type::isInstanceOf(new ArrayIterator([]), Countable::class);   // true
Type::isInstanceOf(ArrayIterator::class, Countable::class);    // false  (string, not object)

Type::isA(new ArrayIterator([]), Countable::class);            // true
Type::isA(ArrayIterator::class, Countable::class);             // true
Type::isA('NotAClass_xyz', stdClass::class);                   // false
Type::isA(42, stdClass::class);                                // false
```

[↑ Back to top](#type)

---

## `isClassName` / `isInterfaceName`

True when $value is a string naming an existing class / interface (autoload is triggered).

```php
Type::isClassName(stdClass::class);          // true
Type::isClassName(Countable::class);         // false  (interface, not class)
Type::isClassName('NotAClass_xyz');          // false

Type::isInterfaceName(Countable::class);     // true
Type::isInterfaceName(Stringable::class);    // true
Type::isInterfaceName(stdClass::class);      // false
```

[↑ Back to top](#type)
