# Json

[← Reference](README.md)

JSON helpers — always throw `JsonException` on malformed input/output (`JSON_THROW_ON_ERROR` is forced).

```php
use Rak200\Utils\Json;
```

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

---

## `isValid`

```php
Json::isValid('{"ok":true}');           // true
Json::isValid('not json');              // false
Json::isValid('{ trailing comma, }');   // false
```
