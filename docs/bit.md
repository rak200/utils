# Bit

[← Reference](README.md)

Bit-manipulation helpers operating on the native PHP `int` (platform-sized — 32 or 64 bits depending on `PHP_INT_SIZE`).

```php
use Rak200\Utils\Bit;
```

## Contents

- [`set` / `unset` / `toggle` / `has`](#set--unset--toggle--has)
- [`count`](#count)
- [`leadingZeros` / `trailingZeros`](#leadingzeros--trailingzeros)
- [`rotateLeft` / `rotateRight`](#rotateleft--rotateright)
- [`toStr` / `fromStr`](#tostr--fromstr)

---

## `set` / `unset` / `toggle` / `has`

Single-bit operations. Bit index is zero-based, LSB first.

```php
Bit::set(0, 3);          // 8       (0000 → 1000)
Bit::set(5, 1);          // 7       (0101 → 0111)
Bit::unset(7, 1);        // 5       (0111 → 0101)
Bit::toggle(5, 0);       // 4       (0101 → 0100)
Bit::toggle(5, 2);       // 1       (0101 → 0001)
Bit::has(5, 0);          // true    (0101 has bit 0)
Bit::has(5, 1);          // false   (0101 lacks bit 1)
```

[↑ Back to top](#bit)

---

## `count`

Population count (number of bits set to 1).

```php
Bit::count(0);       // 0
Bit::count(7);       // 3      (binary: 111)
Bit::count(255);     // 8
```

[↑ Back to top](#bit)

---

## `leadingZeros` / `trailingZeros`

For `$value === 0` both return the full bit width (`PHP_INT_SIZE * 8`).

```php
Bit::leadingZeros(1);          // 63 on a 64-bit build
Bit::leadingZeros(0xff);       // 56 on a 64-bit build
Bit::leadingZeros(0);          // 64 on a 64-bit build

Bit::trailingZeros(1);         // 0
Bit::trailingZeros(8);         // 3       (1000 has 3 trailing zeros)
Bit::trailingZeros(0);         // 64 on a 64-bit build
```

[↑ Back to top](#bit)

---

## `rotateLeft` / `rotateRight`

Circular bit shift over the full `PHP_INT_SIZE * 8`-bit width — bits shifted off one end re-enter at the other. `$by` is taken modulo the bit width (so any integer, including negatives, is accepted), and the two are inverses.

```php
Bit::rotateLeft(1, 1);          // 2
Bit::rotateLeft(5, 2);          // 20      (0b101 → 0b10100)
Bit::rotateLeft(PHP_INT_MIN, 1);// 1       (top bit wraps to the bottom)
Bit::rotateRight(1, 1);         // PHP_INT_MIN   (bottom bit wraps to the top)
Bit::rotateRight(20, 2);        // 5

Bit::rotateLeft(42, PHP_INT_SIZE * 8);   // 42   (a full turn is a no-op)
Bit::rotateLeft(42, -1) === Bit::rotateRight(42, 1);   // true
```

[↑ Back to top](#bit)

---

## `toStr` / `fromStr`

Convert between an `int` and its base-2 string. `toStr` optionally left-pads to a fixed `$width`; negative values use the platform two's-complement form (`PHP_INT_SIZE * 8` bits). `fromStr` reads an **unsigned** binary string and throws on empty/invalid input or a value beyond `PHP_INT_MAX`.

```php
Bit::toStr(5);             // '101'
Bit::toStr(5, 8);          // '00000101'  (left-padded to 8)
Bit::toStr(-1);            // '111…1'     (64 ones on a 64-bit build)

Bit::fromStr('101');       // 5
Bit::fromStr('00000101');  // 5
Bit::fromStr('102');       // throws MalformedArgumentException (not binary)
Bit::fromStr('');          // throws MalformedArgumentException (empty)
```

[↑ Back to top](#bit)

### canarySign

Canary symbol for Rollout step 5, documented so the docs gate stays green and the mutation floor is the only thing that fires.
