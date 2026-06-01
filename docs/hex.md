# Hex

[← Reference](README.md)

Hexadecimal encoding of binary strings — the byte-string ↔ hex-string counterpart to [`Base64`](base64.md). Operates on raw bytes, not characters.

```php
use Rak200\Utils\Hex;
```

## Contents

- [`is`](#is)
- [`encode` / `decode`](#encode--decode)
- [`toBytes` / `fromBytes`](#tobytes--frombytes)

---

## `is`

Domain predicate — true when `$value` is decodable by [`decode`](#encode--decode): an even number of hexadecimal digits (`0-9`, `a-f`, `A-F`). The empty string is a valid encoding (decodes to ``).

```php
Hex::is('deadbeef');    // true
Hex::is('DEADBEEF');    // true   (uppercase accepted)
Hex::is('');            // true
Hex::is('abc');         // false  (odd length)
Hex::is('gg');          // false  (non-hex character)
```

[↑ Back to top](#hex)

---

## `encode` / `decode`

Convert between a binary string and its hexadecimal form: `encode` produces lowercase, two digits per byte (never fails); `decode` is the inverse and accepts upper- and lowercase digits. `decode` throws `RuntimeException` on invalid hex (odd length or a non-hex character).

```php
Hex::encode('hello');         // '68656c6c6f'
Hex::encode("\x00\x01\x02");  // '000102'
Hex::decode('68656c6c6f');    // 'hello'
Hex::decode('DEADBEEF');      // "\xde\xad\xbe\xef"
```

[↑ Back to top](#hex)

---

## `toBytes` / `fromBytes`

Bridge hex and a list of byte values (`int`, `0`–`255`): `toBytes` decodes a hex string to its bytes (accepts either case; `''` → `[]`); `fromBytes` is the inverse, producing lowercase hex. `toBytes` throws on invalid hex; `fromBytes` throws when a value falls outside `0`–`255`.

```php
Hex::toBytes('deadbeef');                 // [222, 173, 190, 239]
Hex::toBytes('00ff');                      // [0, 255]
Hex::fromBytes([222, 173, 190, 239]);      // 'deadbeef'
Hex::fromBytes([15, 255]);                 // '0fff'
```

[↑ Back to top](#hex)
