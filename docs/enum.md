# Enum

[← Reference](README.md)

Class-level operations on enums. PHP ships `cases()`, `from()`, `tryFrom()` directly on the enum; this class fills the gaps — listing names/values, looking up cases by **name** (no native `fromName()`), random pick, and form-friendly `[name => value]` maps. The instance-side predicate (*is this value an enum case?*) is [`Enum::is`](#is); [`Type::isEnum`](type.md#isenum) is its alias.

```php
use Rak200\Utils\Enum;
```

## Contents

- [`is`](#is)
- [`isBacked`](#isbacked)
- [`names`](#names)
- [`values`](#values)
- [`fromName` / `tryFromName`](#fromname--tryfromname)
- [`random`](#random)
- [`toArray`](#toarray)
- [`scalar`](#scalar)
- [`isBackedInt` / `isBackedStr`](#isbackedint--isbackedstr)
- [`isInt` / `isStr`](#isint--isstr)
- [`intOrNull` / `strOrNull`](#intornull--strornull)

---

## `is`

Domain predicate — true when `$value` is an enum case (instance of `UnitEnum`, which every pure and backed enum implements). Accepts `mixed`. Enum class-name strings are not accepted — use [`Type::isA`](type.md#isinstanceof--isa--issubclassof) with `UnitEnum` for that. [`Type::isEnum`](type.md#isenum) is an alias.

```php
enum Suit { case Hearts; case Spades; }
enum Status: string { case Active = 'active'; }

Enum::is(Suit::Hearts);     // true
Enum::is(Status::Active);   // true
Enum::is(Suit::class);      // false   (class-name string, not an instance)
Enum::is('Hearts');         // false
Enum::is(null);             // false
```

[↑ Back to top](#enum)

---

## `isBacked`

Domain predicate — true when `$value` is a *backed* enum case (instance of `BackedEnum`), i.e. an enum declared with a `: int` or `: string` backing type. Pure (unbacked) cases and non-enum values return false. Accepts `mixed`. For the specific kind of backing, see [`isBackedInt` / `isBackedStr`](#isbackedint--isbackedstr).

```php
enum Suit { case Hearts; }
enum Status: string { case Active = 'active'; }
enum Priority: int { case Low = 1; }

Enum::isBacked(Status::Active);   // true   (string-backed)
Enum::isBacked(Priority::Low);    // true   (int-backed)
Enum::isBacked(Suit::Hearts);     // false  (pure enum)
Enum::isBacked(Status::class);    // false  (class-name string)
Enum::isBacked(null);             // false
```

[↑ Back to top](#enum)

---

## `names`

Returns the names of every case, in declaration order. Works on pure and backed enums.

```php
enum Suit { case Hearts; case Spades; }
enum Status: string { case Active = 'active'; case Inactive = 'inactive'; }

Enum::names(Suit::class);     // ['Hearts', 'Spades']
Enum::names(Status::class);   // ['Active', 'Inactive']
```

[↑ Back to top](#enum)

---

## `values`

Returns the backed values of every case, in declaration order. Throws when the class is not a backed enum.

```php
Enum::values(Status::class);  // ['active', 'inactive']
Enum::values(Suit::class);    // InvalidArgumentException (pure enum)
```

[↑ Back to top](#enum)

---

## `fromName` / `tryFromName`

Looks up a case by its **name** — the gap PHP leaves open (the native `from()` / `tryFrom()` look up by *value* and only work on backed enums). `fromName` throws on a miss; `tryFromName` returns `null`. The PHPDoc `@template T of UnitEnum` narrows the return type to the concrete enum class for static analysis.

```php
Enum::fromName(Suit::class, 'Hearts');       // Suit::Hearts
Enum::fromName(Suit::class, 'Clubs');        // OutOfBoundsException

Enum::tryFromName(Status::class, 'Active');  // Status::Active
Enum::tryFromName(Status::class, 'Unknown'); // null
```

[↑ Back to top](#enum)

---

## `random`

Picks a case at random with a cryptographically-secure RNG (delegates to [`Rand::choice`](rand.md#choice)). Throws on a case-less enum.

```php
Enum::random(Suit::class);   // Suit::Hearts (shape — random)
Enum::random(Status::class); // Status::Inactive (shape — random)
```

[↑ Back to top](#enum)

---

## `toArray`

Returns a `name => value` map for a backed enum, or a `name => name` map for a pure enum. Useful as a one-liner for form/select option lists.

```php
Enum::toArray(Status::class);
// ['Active' => 'active', 'Inactive' => 'inactive']

Enum::toArray(Suit::class);
// ['Hearts' => 'Hearts', 'Spades' => 'Spades']
```

[↑ Back to top](#enum)

---

## `scalar`

Returns a single scalar representation of an enum case — the backed value for a backed enum case, or the name for a pure enum case. Useful when you need one canonical scalar for any kind of enum (logging, serialisation, cache keys) without branching on whether the enum is backed.

```php
enum Suit { case Hearts; case Spades; }
enum Status: string { case Active = 'active'; case Inactive = 'inactive'; }
enum Priority: int { case Low = 1; case High = 10; }

Enum::scalar(Suit::Hearts);    // 'Hearts'
Enum::scalar(Status::Active);  // 'active'
Enum::scalar(Priority::High);  // 10
```

[↑ Back to top](#enum)

---

## `isBackedInt` / `isBackedStr`

Predicates for the *kind* of backing on an enum case: `isBackedInt` is true for int-backed cases, `isBackedStr` is true for string-backed cases, both are false for pure (unbacked) cases. The `@phpstan-assert-if-true BackedEnum` PHPDoc narrows the case to `BackedEnum` inside the guarded branch, making `$case->value` readable there as `int|string`. For the exact `int` / `string` narrowing of `$case->value`, use [`isInt` / `isStr`](#isint--isstr) on a case already known to be backed.

```php
Enum::isBackedInt(Priority::Low);    // true
Enum::isBackedInt(Status::Active);   // false (string-backed)
Enum::isBackedInt(Suit::Hearts);     // false (pure)

Enum::isBackedStr(Status::Active);   // true
Enum::isBackedStr(Priority::Low);    // false (int-backed)
Enum::isBackedStr(Suit::Hearts);     // false (pure)
```

[↑ Back to top](#enum)

---

## `isInt` / `isStr`

Predicates for the value type of a case **already known to be backed** — the `BackedEnum`-typed counterparts of [`isBackedInt` / `isBackedStr`](#isbackedint--isbackedstr). Because the parameter is `BackedEnum`, the PHPDoc asserts narrow `$case->value` exactly, in **both** branches: after `isInt`, the value is `int` in the true branch and `string` in the false branch (`isStr` mirrors this). Starting from a plain `UnitEnum`, guard with [`isBacked`](#isbacked) first.

```php
Enum::isInt(Priority::Low);      // true
Enum::isInt(Status::Active);     // false (string-backed)

if (Enum::isInt($case)) {
    $case->value;                // int
} else {
    $case->value;                // string
}

// from a UnitEnum, narrow in two steps:
if (Enum::isBacked($e) && Enum::isInt($e)) {
    $e->value;                   // int
}
```

[↑ Back to top](#enum)

---

## `intOrNull` / `strOrNull`

Value-extracting complements of [`isInt` / `isStr`](#isint--isstr): `intOrNull` returns the case's backing when it is an `int`, `strOrNull` when it is a `string`, and both return `null` otherwise. Unlike the predicates — which take a `BackedEnum` and only *narrow* — these take the broad `UnitEnum` and *read* the value directly, so a pure-enum case or a wrong-typed backing collapses to `null` with **no** prior [`isBacked`](#isbacked) guard. A total, throw-free read; the return type is `?int` / `?string`.

```php
Enum::intOrNull(Priority::Low);     // 1
Enum::intOrNull(Status::Active);    // null (string-backed)
Enum::intOrNull(Suit::Hearts);      // null (pure)

Enum::strOrNull(Status::Active);    // 'active'
Enum::strOrNull(Priority::Low);     // null (int-backed)
Enum::strOrNull(Suit::Hearts);      // null (pure)

// direct read, no isBacked/isInt dance:
$timeout = Enum::intOrNull($case) ?? 30;
```

[↑ Back to top](#enum)
