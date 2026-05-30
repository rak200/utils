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
Enum::values(Suit::class);    // RuntimeException (pure enum)
```

[↑ Back to top](#enum)

---

## `fromName` / `tryFromName`

Looks up a case by its **name** — the gap PHP leaves open (the native `from()` / `tryFrom()` look up by *value* and only work on backed enums). `fromName` throws on a miss; `tryFromName` returns `null`. The PHPDoc `@template T of UnitEnum` narrows the return type to the concrete enum class for static analysis.

```php
Enum::fromName(Suit::class, 'Hearts');       // Suit::Hearts
Enum::fromName(Suit::class, 'Clubs');        // RuntimeException

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

Predicates for the *kind* of backing on an enum case: `isBackedInt` is true for int-backed cases, `isBackedStr` is true for string-backed cases, both are false for pure (unbacked) cases. The `@phpstan-assert-if-true BackedEnum` PHPDoc narrows the case to `BackedEnum` inside the guarded branch.

```php
Enum::isBackedInt(Priority::Low);    // true
Enum::isBackedInt(Status::Active);   // false (string-backed)
Enum::isBackedInt(Suit::Hearts);     // false (pure)

Enum::isBackedStr(Status::Active);   // true
Enum::isBackedStr(Priority::Low);    // false (int-backed)
Enum::isBackedStr(Suit::Hearts);     // false (pure)
```

[↑ Back to top](#enum)
