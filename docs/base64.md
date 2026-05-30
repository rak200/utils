# Base64

[← Reference](README.md)

Base64 helpers, including the URL-safe variant (RFC 4648 §5) without padding.

```php
use Rak200\Utils\Base64;
```

## Contents

- [`is`](#is)
- [`encode` / `decode`](#encode--decode)
- [`encodeUrl` / `decodeUrl`](#encodeurl--decodeurl)

---

## `is`

Domain predicate — true when `$value` is decodable by either [`decode`](#encode--decode) (standard alphabet, strict) or [`decodeUrl`](#encodeurl--decodeurl) (URL-safe alphabet, missing padding restored). The empty string is a valid encoding (decodes to ``).

```php
Base64::is('aGVsbG8=');           // true   (standard)
Base64::is('aGVsbG8_d29ybGQ');    // true   (URL-safe, unpadded)
Base64::is('');                   // true
Base64::is('!!!');                // false
Base64::is('Zm9v#');              // false
```

[↑ Back to top](#base64)

---

## `encode` / `decode`

Standard Base64. `decode` is strict — it rejects any non-alphabet character.

```php
Base64::encode('hello');             // 'aGVsbG8='
Base64::encode('A long string');     // 'QSBsb25nIHN0cmluZw=='
Base64::decode('aGVsbG8=');          // 'hello'
```

[↑ Back to top](#base64)

---

## `encodeUrl` / `decodeUrl`

URL-safe variant: `+/` become `-_` and the trailing `=` padding is stripped. Missing padding is restored on decode.

```php
Base64::encodeUrl('hello?world');         // 'aGVsbG8_d29ybGQ'
Base64::decodeUrl('aGVsbG8_d29ybGQ');     // 'hello?world'
```

[↑ Back to top](#base64)
