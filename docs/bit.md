# Bit

[← Reference](README.md)

Bit-manipulation helpers operating on the native PHP `int` (platform-sized — 32 or 64 bits depending on `PHP_INT_SIZE`).

```php
use Rak200\Utils\Bit;
```

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

---

## `count`

Population count (number of bits set to 1).

```php
Bit::count(0);       // 0
Bit::count(7);       // 3      (binary: 111)
Bit::count(255);     // 8
```

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
