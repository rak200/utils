# Rand

[← Reference](README.md)

Cryptographically-secure random helpers, plus UUID, ULID, and NanoID generators and UUID/ULID inspection.

```php
use Rak200\Utils\Rand;
```

## Contents

- [Alphabet constants](#alphabet-constants)
- [`int`](#int)
- [`float`](#float)
- [`bool`](#bool)
- [`bytes`](#bytes)
- [`string`](#string)
- [`masked`](#masked)
- [`choice`](#choice)
- [`shuffle`](#shuffle)
- [`uuidV4`](#uuidv4)
- [`uuidV7`](#uuidv7)
- [`ulid`](#ulid)
- [`nanoid`](#nanoid)
- [`isUuid` / `isUlid`](#isuuid--isulid)
- [`uuidV7Time` / `uuidV7TimeOrNull`](#uuidv7time--uuidv7timeornull)
- [`ulidTime` / `ulidTimeOrNull`](#ulidtime--ulidtimeornull)

---

## Alphabet constants

Predefined character sets for use with `string` and `masked`.

```php
Rand::NUM;     // '0123456789'
Rand::HEX;     // '0123456789abcdef'
Rand::ALPHA;   // 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
Rand::ALNUM;   // 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
```

[↑ Back to top](#rand)

---

## `int`

CSPRNG integer in the closed range `[$min, $max]`.

```php
Rand::int(1, 6);          // e.g. 4
Rand::int(-100, 100);     // e.g. -42
```

[↑ Back to top](#rand)

---

## `float`

```php
Rand::float(0.0, 1.0);        // e.g. 0.4271736...
Rand::float(-10.0, 10.0);     // e.g. -3.1842...
```

[↑ Back to top](#rand)

---

## `bool`

CSPRNG boolean.

```php
Rand::bool();     // true or false
```

[↑ Back to top](#rand)

---

## `bytes`

Raw binary bytes.

```php
Rand::bytes(8);                // e.g. "\xa3\x12\xf0\x4b\x9c\x07\xee\x5d"
bin2hex(Rand::bytes(8));       // e.g. 'a312f04b9c07ee5d'
```

[↑ Back to top](#rand)

---

## `string`

Random string from a given alphabet (default: `Rand::ALNUM`).

```php
Rand::string(8);                  // e.g. 'aB3xK9mQ'
Rand::string(6, Rand::HEX);       // e.g. 'a3f04b'
Rand::string(4, Rand::NUM);       // e.g. '0427'
```

[↑ Back to top](#rand)

---

## `masked`

Replaces every `#` in the pattern with a random character from `$alphabet`; everything else is emitted literally.

```php
Rand::masked('####-####', Rand::NUM);         // e.g. '4827-0193'
Rand::masked('ID-######');                     // e.g. 'ID-aB3xK9'
Rand::masked('LIC ## ##-##', Rand::ALPHA);    // e.g. 'LIC qZ Wm-Tk'
```

[↑ Back to top](#rand)

---

## `choice`

CSPRNG element from a non-empty array. Works with list and associative inputs (the value is returned, not the key).

```php
Rand::choice(['heads', 'tails']);              // e.g. 'tails'
Rand::choice(['a' => 1, 'b' => 2, 'c' => 3]); // e.g. 2
```

[↑ Back to top](#rand)

---

## `shuffle`

Fisher-Yates shuffle using `random_int` for entropy. Returns the values re-indexed as a 0-based list (keys are not preserved).

```php
Rand::shuffle([1, 2, 3, 4, 5]);     // e.g. [3, 1, 5, 2, 4]
Rand::shuffle([]);                  // []
```

[↑ Back to top](#rand)

---

## `uuidV4`

UUID v4 (RFC 4122), canonical hex form. Random across all 122 bits — pick this when you specifically need an unordered identifier; otherwise prefer `uuidV7` for time-ordered IDs that index better.

```php
Rand::uuidV4();    // e.g. '550e8400-e29b-41d4-a716-446655440000'
```

[↑ Back to top](#rand)

---

## `uuidV7`

UUID v7 (RFC 9562) — time-ordered, millisecond-precision prefix. Sequential generations sort lexicographically by time.

```php
Rand::uuidV7();    // e.g. '018f4d2a-bc31-7c84-9b67-1a3d8f0e2c4d'
```

[↑ Back to top](#rand)

---

## `ulid`

26-character Crockford Base32 ULID, time-ordered by millisecond.

```php
Rand::ulid();    // e.g. '01HXP2K8FJM5E7R3Q6Y2N0V1WB'
```

[↑ Back to top](#rand)

---

## `nanoid`

URL-safe NanoID using the standard 64-character alphabet (`A-Z a-z 0-9 _ -`). Default length is 21.

```php
Rand::nanoid();       // e.g. 'V1StGXR8_Z5jdHi6B-myT'
Rand::nanoid(10);     // e.g. 'aB3xK9mQzL'
```

[↑ Back to top](#rand)

---

## `isUuid` / `isUlid`

Validate identifier strings (case-insensitive). `isUuid` checks the canonical 8-4-4-4-12 hex form; pass `$version` (1-8) to also require that version nibble and an RFC 4122 variant. `isUlid` checks the 26-character Crockford Base32 form (first character `0`-`7`).

```php
Rand::isUuid(Rand::uuidV4());        // true
Rand::isUuid(Rand::uuidV7(), 7);     // true
Rand::isUuid(Rand::uuidV4(), 7);     // false  (wrong version)
Rand::isUuid('not-a-uuid');          // false
Rand::isUlid(Rand::ulid());          // true
Rand::isUlid('01HXP2K8FJM5E7R3Q6Y2N0V1WB'); // true
Rand::isUlid('nope');                // false
```

[↑ Back to top](#rand)

---

## `uuidV7Time` / `uuidV7TimeOrNull`

Extracts the millisecond timestamp embedded in a UUID v7 as a UTC `DateTimeImmutable`. The bare method throws when the input is not a valid UUID v7; `*OrNull` returns `null`.

```php
$id = Rand::uuidV7();
Rand::uuidV7Time($id);               // DateTimeImmutable @ the instant $id was generated
Rand::uuidV7TimeOrNull(Rand::uuidV4()); // null  (v4 has no embedded timestamp)
Rand::uuidV7Time('not-a-uuid');      // throws MalformedArgumentException
```

[↑ Back to top](#rand)

---

## `ulidTime` / `ulidTimeOrNull`

Extracts the millisecond timestamp embedded in a ULID as a UTC `DateTimeImmutable`. The bare method throws when the input is not a valid ULID; `*OrNull` returns `null`.

```php
$id = Rand::ulid();
Rand::ulidTime($id);                 // DateTimeImmutable @ the instant $id was generated
Rand::ulidTimeOrNull('nope');        // null
Rand::ulidTime('nope');              // throws MalformedArgumentException
```

[↑ Back to top](#rand)
