# Regex

[← Reference](README.md)

PCRE wrappers that throw on invalid patterns instead of surfacing the silent `false` / `null` returns of the underlying `preg_*` functions.

```php
use Rak200\Utils\Regex;
```

## Contents

- [`matches`](#matches)
- [`match` / `matchOrNull`](#match--matchornull)
- [`matchAll`](#matchall)
- [`replace`](#replace)
- [`replaceCallback`](#replacecallback)
- [`split`](#split)
- [`quote`](#quote)

---

## `matches`

True/false test.

```php
Regex::matches('/^\d+$/', '42');      // true
Regex::matches('/^\d+$/', '42a');     // false
```

[↑ Back to top](#regex)

---

## `match` / `matchOrNull`

First match. Capture groups indexed by position and (when named) by name. `*OrNull` returns `null` when there is no match.

```php
Regex::match('/(\w+)@(\w+)/', 'rak@example');
// [0 => 'rak@example', 1 => 'rak', 2 => 'example']

Regex::match('/(?<user>\w+)@(?<host>\w+)/', 'rak@example');
// [0 => 'rak@example', 'user' => 'rak', 1 => 'rak', 'host' => 'example', 2 => 'example']

Regex::matchOrNull('/\d+/', 'no digits here');    // null
```

[↑ Back to top](#regex)

---

## `matchAll`

Every match. Outer keys are group index/name, inner values are the list of captures for that group.

```php
Regex::matchAll('/\d+/', 'a1 b22 c333');
// [0 => ['1', '22', '333']]

Regex::matchAll('/(\w)(\d+)/', 'a1 b22 c333');
// [0 => ['a1', 'b22', 'c333'], 1 => ['a', 'b', 'c'], 2 => ['1', '22', '333']]
```

[↑ Back to top](#regex)

---

## `replace`

`$replacement` may reference capture groups (e.g. `$1`, `${name}`).

```php
Regex::replace('/\s+/', '-', 'hello   world');
// 'hello-world'

Regex::replace('/(\w+)@(\w+)/', '$2/$1', 'rak@example');
// 'example/rak'
```

[↑ Back to top](#regex)

---

## `replaceCallback`

```php
Regex::replaceCallback(
    '/\d+/',
    fn(array $m) => (string) ((int) $m[0] * 2),
    'a1 b2 c3',
);    // 'a2 b4 c6'
```

[↑ Back to top](#regex)

---

## `split`

```php
Regex::split('/\s*,\s*/', 'a, b ,c  ,  d');     // ['a', 'b', 'c', 'd']
```

[↑ Back to top](#regex)

---

## `quote`

Escapes regex meta-characters so the result can be embedded literally inside a pattern. Pass the delimiter to also escape it.

```php
Regex::quote('1.5+2.0');               // '1\.5\+2\.0'
Regex::quote('path/to/file');          // 'path/to/file'   ('/' isn't a meta-char)
Regex::quote('path/to/file', '/');     // 'path\/to\/file'
```

[↑ Back to top](#regex)
