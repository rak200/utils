# Json

[← Reference](README.md)

JSON helpers — always throw [`MalformedJsonException`](exceptions.md) on malformed input/output (`JSON_THROW_ON_ERROR` is forced; the native `JsonException` stays readable as `getPrevious()`).

```php
use Rak200\Utils\Json;
```

## Contents

- [`encode`](#encode)
- [`decode`](#decode)
- [`is`](#is)

---

## `encode`

`$flags` is OR'd with `JSON_THROW_ON_ERROR`.

```php
Json::encode(['name' => 'rak', 'age' => 30]);
// '{"name":"rak","age":30}'

Json::encode(['unicode' => 'ação'], JSON_UNESCAPED_UNICODE);
// '{"unicode":"ação"}'

Json::encode(['nested' => [1, 2]], JSON_PRETTY_PRINT);
// '{
//     "nested": [
//         1,
//         2
//     ]
// }'
```

[↑ Back to top](#json)

---

## `decode`

Objects become associative arrays by default; pass `$assoc = false` for `stdClass`.

```php
Json::decode('{"name":"rak","age":30}');
// ['name' => 'rak', 'age' => 30]

Json::decode('{"name":"rak"}', false);
// stdClass { name: 'rak' }

Json::decode('[1, 2, 3]');
// [1, 2, 3]
```

[↑ Back to top](#json)

---

## `is`

Domain predicate — true when `$value` is a string that parses successfully as JSON. Accepts `mixed` so it can be used as a guard (non-strings short-circuit to false without calling `decode`).

```php
Json::is('{"ok":true}');           // true
Json::is('null');                  // true
Json::is('42');                    // true
Json::is('not json');              // false
Json::is('{ trailing comma, }');   // false
Json::is(['ok' => true]);          // false  (an array is not a JSON string)
Json::is(null);                    // false
```

[↑ Back to top](#json)
