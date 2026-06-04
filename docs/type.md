# Type

[← Reference](README.md)

Type-checking predicates. Every method accepts `mixed`, so calls work as guards on values whose shape is not yet known. Each predicate carries `@phpstan-assert` PHPDoc, so PHPStan narrows the value's type inside the guarded branch exactly as it would for the native `is_*` functions.

```php
use Rak200\Utils\Type;
```

Every domain class also exposes a bare **`is`** as its canonical "is a member of this domain?" predicate; the predicates on `Type` that mirror a domain (`isStr`, `isInt`, `isFloat`, `isArray`, `isEnum`, `isNumeric`) are aliases. Use whichever location reads better at the call site.

- **String** — [`Str::is`](str.md#is) (=`Type::isStr`), plus shape predicates [`Str::isBlank`](str.md#isblank--isnotblank--isempty), [`Str::isNotBlank`](str.md#isblank--isnotblank--isempty).
- **Array** — [`Arr::is`](arr.md#is) (=`Type::isArray`), plus shape predicates [`Arr::isList`](arr.md#islist--isassoc), [`Arr::isAssoc`](arr.md#islist--isassoc).
- **Number** — [`Num::is`](num.md#is) (=`Type::isNumeric`), [`Num::isInt`](num.md#isint--isfloat) (=`Type::isInt`), [`Num::isFloat`](num.md#isint--isfloat) (=`Type::isFloat`), plus sign predicates [`Num::isPositive`](num.md#ispositive--isnegative), [`Num::isNegative`](num.md#ispositive--isnegative).
- **Enum** — [`Enum::is`](enum.md#is) (=`Type::isEnum`).
- **Date/time** — [`Dt::is`](dt.md#is).
- **JSON / Base64 / Regex / URL** — [`Json::is`](json.md#is), [`Base64::is`](base64.md#is), [`Regex::is`](regex.md#is), [`Url::is`](url.md#is).

## Contents

- [`of`](#of)
- [`isStr` / `isBool` / `isInt` / `isFloat` / `isArray` / `isObject` / `isCallable` / `isIterable` / `isNull` / `isScalar` / `isResource`](#basic-type-checks)
- [`isEnum`](#isenum)
- [`isNumeric`](#isnumeric)
- [`isNumericStr` / `isIntLike`](#isnumericstr--isintlike)
- [`isInstance` / `isA` / `isSubclass`](#isinstance--isa--issubclass)
- [`isClassName` / `isInterfaceName`](#isclassname--isinterfacename)
- [`usesTrait`](#usestrait)

---

## `of`

Returns the resolved type name of a value (wraps [`get_debug_type()`](https://www.php.net/manual/en/function.get-debug-type.php)): scalar/`null`/`array` keywords, the class name for objects, or a `resource (...)` label.

```php
Type::of(42);                       // 'int'
Type::of(1.5);                      // 'float'
Type::of('hello');                  // 'string'
Type::of(true);                     // 'bool'
Type::of(null);                     // 'null'
Type::of([1, 2, 3]);                // 'array'
Type::of(new stdClass());           // 'stdClass'
Type::of(new ArrayIterator([]));    // 'ArrayIterator'
Type::of(static fn() => 1);         // 'Closure'
Type::of(fopen('php://memory', 'rb'));   // 'resource (stream)'
```

[↑ Back to top](#type)

---

## Basic type checks

Thin, uniform wrappers around PHP's `is_*()` family. The domain-mirroring predicates (`isStr`, `isInt`, `isFloat`, `isArray`) are aliases of the canonical method on each domain class: [`Str::is`](str.md#is), [`Num::isInt`](num.md#isint--isfloat), [`Num::isFloat`](num.md#isint--isfloat), [`Arr::is`](arr.md#is). Behaviour and narrowing are identical — pick whichever location matches the call site's intent.

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

## `isEnum`

Alias of [`Enum::is`](enum.md#is). True when the value is an enum case — an instance of [`UnitEnum`](https://www.php.net/manual/en/class.unitenum.php), which every pure and backed enum implements. Enum class-name strings are not accepted.

```php
enum Suit { case Hearts; case Spades; }
enum Status: string { case Active = 'active'; }

Type::isEnum(Suit::Hearts);      // true
Type::isEnum(Status::Active);    // true   (backed enum case)
Type::isEnum(Suit::class);       // false  (string, not a case)
Type::isEnum(new stdClass());    // false
Type::isEnum(42);                // false
```

[↑ Back to top](#type)

---

## `isNumeric`

Alias of [`Num::is`](num.md#is). True for ints, floats, strict numeric strings (no surrounding whitespace), and `BcMath\Number` instances.

```php
Type::isNumeric(42);                     // true
Type::isNumeric('1.5');                  // true
Type::isNumeric('-1e3');                 // true
Type::isNumeric(new Number('123.45'));   // true
Type::isNumeric('hello');                // false
Type::isNumeric(true);                   // false
Type::isNumeric(' 42 ');                 // false  (surrounding whitespace rejected)
```

[↑ Back to top](#type)

---

## `isNumericStr` / `isIntLike`

`isNumericStr` is true for strings in numeric format — decimals, exponent, leading sign — with no surrounding whitespace. `isIntLike` is stricter still: an int, or a string parseable as one (sign + digits only — no decimal point, exponent, or whitespace).

```php
Type::isNumericStr('42');      // true
Type::isNumericStr('-1.5');    // true
Type::isNumericStr('1e3');     // true
Type::isNumericStr(' 42 ');    // false   (surrounding whitespace rejected)
Type::isNumericStr(42);        // false   (not a string)

Type::isIntLike(42);           // true
Type::isIntLike('42');         // true
Type::isIntLike('-7');         // true
Type::isIntLike('1.0');        // false
Type::isIntLike('1e3');        // false
Type::isIntLike(' 42 ');       // false
```

> Both are `@deprecated` since 2.1.0 and will be removed in 3.0.0. Replace `isNumericStr($v)` with `Type::isStr($v) && Type::isNumeric($v)`, and `isIntLike($v)` with `Type::isInt($v) || (Type::isStr($v) && Regex::matches('/^[+-]?\d+$/', $v))`.

[↑ Back to top](#type)

---

## `isInstance` / `isA` / `isSubclass`

`isInstance` is strict: $value must be an object. `isA` is a shortcut for [`is_a()`](https://www.php.net/manual/en/function.is-a.php) with `$allow_string = true` — it also accepts a class-name string and checks the class hierarchy (autoload-aware). `isSubclass` is a shortcut for [`is_subclass_of()`](https://www.php.net/manual/en/function.is-subclass-of.php) (also `$allow_string = true`): like `isA`, but the exact same class returns `false` — only proper subclasses and interface implementations match.

```php
Type::isInstance(new ArrayIterator([]), Countable::class);   // true
Type::isInstance(ArrayIterator::class, Countable::class);    // false  (string, not object)

Type::isA(new ArrayIterator([]), Countable::class);          // true
Type::isA(ArrayIterator::class, Countable::class);           // true
Type::isA('NotAClass_xyz', stdClass::class);                 // false
Type::isA(42, stdClass::class);                              // false

Type::isSubclass(ArrayIterator::class, Countable::class);    // true   (implements it)
Type::isSubclass(ArrayIterator::class, ArrayIterator::class);// false  (same class, not a subclass)
Type::isA(ArrayIterator::class, ArrayIterator::class);       // true   (isA accepts the same class)
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

---

## `usesTrait`

True when the value (an object or class-name string) uses `$trait`. By default only traits applied **directly** on the class count; pass `recursive: true` to also match traits inherited from parent classes and traits used by other traits (nested). A string naming no existing class or trait returns `false`.

```php
trait GreetTrait {}
trait NestedTrait {}
trait ComposedTrait { use NestedTrait; }

class UsesGreet { use GreetTrait; }
class ChildUsesGreet extends UsesGreet {}
class UsesComposed { use ComposedTrait; }

Type::usesTrait(new UsesGreet(), GreetTrait::class);     // true
Type::usesTrait(UsesGreet::class, GreetTrait::class);    // true   (class-name string)

// Inherited from a parent — only matched recursively:
Type::usesTrait(new ChildUsesGreet(), GreetTrait::class);                   // false
Type::usesTrait(new ChildUsesGreet(), GreetTrait::class, recursive: true);  // true

// Nested (trait used by a trait) — only matched recursively:
Type::usesTrait(new UsesComposed(), NestedTrait::class);                    // false
Type::usesTrait(new UsesComposed(), NestedTrait::class, recursive: true);   // true

Type::usesTrait(new stdClass(), GreetTrait::class);      // false
Type::usesTrait('NotAClass_xyz', GreetTrait::class);     // false
```

[↑ Back to top](#type)
