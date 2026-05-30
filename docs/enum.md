# Enum

[← Reference](README.md)

Class-level operations on enums. PHP ships `cases()`, `from()`, `tryFrom()` directly on the enum; this class fills the gaps — listing names/values, looking up cases by **name** (no native `fromName()`), random pick, and form-friendly `[name => value]` maps. The instance-side predicate (*is this value an enum case?*) lives at [`Type::isEnum`](type.md#isenum).

```php
use Rak200\Utils\Enum;
```

## Contents

- [`names`](#names)
- [`values`](#values)
- [`fromName` / `tryFromName`](#fromname--tryfromname)
- [`random`](#random)
- [`toArray`](#toarray)

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
