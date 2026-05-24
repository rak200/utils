# Hash

Hashing helpers (digests, HMAC, password hashing & verification).

```php
use Rak200\Utils\Hash;
```

---

## `md5` / `sha1` / `sha256` / `sha512`

Lowercase hex digests. **`md5` and `sha1` are NOT suitable for security-sensitive uses** — they remain available for non-security work (checksums, cache keys).

```php
Hash::md5('hello');
// '5d41402abc4b2a76b9719d911017c592'

Hash::sha1('hello');
// 'aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d'

Hash::sha256('hello');
// '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824'

Hash::sha512('hello');
// '9b71d224bd62f3785d96d46ad3ea3d73319bfbc2890caadae2dff72519673ca72323c3d99ba5c11d7c7acc6e14b8c5da0c4663475c2e5c3adef46f73bcdec043'
```

---

## `crc32`

Unsigned 32-bit integer.

```php
Hash::crc32('hello');     // 907060870
```

---

## `hmac`

```php
Hash::hmac('sha256', 'message', 'secret-key');
// '287a3bd8a4fc7731a94c722079055323644d8798bd291bf9878abc9b8fd4b1d0'
```

---

## `equals`

Constant-time string comparison — use whenever you compare digests, tokens, or any value where a timing leak would matter.

```php
Hash::equals('abc', 'abc');     // true
Hash::equals('abc', 'abd');     // false
```

---

## `password` / `verifyPassword`

`password` uses PHP's current `PASSWORD_DEFAULT` (bcrypt at the time of writing).

```php
$hash = Hash::password('correct horse battery staple');
// e.g. '$2y$12$EXa...' (60 chars for bcrypt)

Hash::verifyPassword('correct horse battery staple', $hash);   // true
Hash::verifyPassword('wrong password', $hash);                 // false
```
