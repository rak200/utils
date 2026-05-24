# Url

[← Reference](README.md)

URL parsing, building, and query-string encode/decode. Parsing returns the same keyed components as PHP's `parse_url`, and `build` accepts the same shape — `Url::build(Url::parse($u))` round-trips.

```php
use Rak200\Utils\Url;
```

## Contents

- [`parse` / `parseOrNull`](#parse--parseornull)
- [`build`](#build)
- [`encode` / `decode`](#encode--decode)
- [`encodeQuery` / `decodeQuery`](#encodequery--decodequery)
- [`isAbsolute`](#isabsolute)

---

## `parse` / `parseOrNull`

Splits a URL into its components. `parse` throws `RuntimeException` on malformed input; `parseOrNull` returns `null`. Keys present mirror `parse_url`: `scheme`, `host`, `port`, `user`, `pass`, `path`, `query`, `fragment` (only those present in the input appear).

```php
Url::parse('https://user:pass@example.com:8080/path?x=1#frag');
// [
//     'scheme'   => 'https',
//     'host'     => 'example.com',
//     'port'     => 8080,
//     'user'     => 'user',
//     'pass'     => 'pass',
//     'path'     => '/path',
//     'query'    => 'x=1',
//     'fragment' => 'frag',
// ]

Url::parse('/just/a/path?q=1');
// ['path' => '/just/a/path', 'query' => 'q=1']

Url::parseOrNull('http:///bad');    // null
```

[↑ Back to top](#url)

---

## `build`

Assembles a URL from a component array (same shape as `parse`). Missing pieces are omitted.

```php
Url::build([
    'scheme' => 'https',
    'host'   => 'example.com',
    'path'   => '/users',
    'query'  => 'page=2',
]);
// 'https://example.com/users?page=2'

Url::build(Url::parse('https://example.com/a?b=1#c'));
// 'https://example.com/a?b=1#c'   (round-trips)
```

[↑ Back to top](#url)

---

## `encode` / `decode`

RFC 3986 percent-encoding (the URL-safe variant — spaces become `%20`, not `+`). Use these for path or query-value components, not for whole URLs.

```php
Url::encode('hello world');          // 'hello%20world'
Url::encode('a/b+c');                // 'a%2Fb%2Bc'
Url::decode('hello%20world');        // 'hello world'
```

[↑ Back to top](#url)

---

## `encodeQuery` / `decodeQuery`

Build or parse `application/x-www-form-urlencoded` query strings. Nested arrays use PHP's bracket syntax.

```php
Url::encodeQuery(['name' => 'John Doe', 'tag' => ['php', 'web']]);
// 'name=John%20Doe&tag%5B0%5D=php&tag%5B1%5D=web'

Url::decodeQuery('name=John%20Doe&tag%5B0%5D=php&tag%5B1%5D=web');
// ['name' => 'John Doe', 'tag' => ['php', 'web']]
```

[↑ Back to top](#url)

---

## `isAbsolute`

True when the URL has a scheme (e.g. `https:`, `mailto:`, `file:`); false for path-only or empty input.

```php
Url::isAbsolute('https://example.com');     // true
Url::isAbsolute('mailto:a@b.com');          // true
Url::isAbsolute('/just/a/path');            // false
Url::isAbsolute('relative/path');           // false
```

[↑ Back to top](#url)
