# Exceptions

[← Reference](README.md)

Every failure this library raises lives in `Rak200\Utils\Exception`: a `UtilsException`
marker interface implemented by empty-bodied domain classes, each extending the precise
SPL type its failure kind used since 4.0.0. Catch one class per *kind*, one branch for all
I/O, or the whole library with a single clause — while every pre-existing catch of an SPL
type keeps working untouched.

```php
use Rak200\Utils\Exception\UtilsException;
```

## Contents

- [The hierarchy](#the-hierarchy)
- [The library-scoped catch](#the-library-scoped-catch)
- [SPL catches keep working](#spl-catches-keep-working)
- [The `Json` wrap](#the-json-wrap)

---

## The hierarchy

```text
Throwable
└── UtilsException (interface — the marker)
    ├── MalformedArgumentException  extends InvalidArgumentException   malformed input / out-of-domain argument
    ├── LookupException             extends OutOfBoundsException       lookup with no result
    ├── EmptySourceException        extends UnderflowException         operation on an empty source
    ├── BadCallbackException        extends UnexpectedValueException   user callback returned an unusable value
    ├── IOException (abstract)      extends RuntimeException           environment / native I/O failure
    │   └── FilesystemException                                        filesystem operation failed (File only)
    └── MalformedJsonException      extends \JsonException             malformed JSON input/output (Json only)
```

Who throws what:

- **`MalformedArgumentException`** — parse failures (`Num::parseInt` / `parseFloat` /
  `parseNumber`, `Dt::parse`, `Url::parse`, `Rand::uuidV7Time` / `ulidTime`), malformed
  encodings (`Base64` / `Hex` / `Bit` decode, invalid UTF-8, invalid regex patterns), and
  argument-domain guards (negative sizes, base/bit-index ranges, division by zero).
- **`LookupException`** — `Arr::find` / `get` / `getKey` / `search` / `keyPosition`,
  `Iter::find` / `nth`, `Enum::fromName` / `fromValue`, `Regex::match`, `Arr::pluck` /
  `keyBy` on a missing column.
- **`EmptySourceException`** — `Arr::first` / `last` / `firstKey` / `lastKey` / `shift` /
  `pop`, `Iter::first` / `last`, `Num::min` / `max` / `avg`, `Dt::min` / `max`,
  `Rand::choice`, `Enum::random`, `Str::ord` on `''`.
- **`BadCallbackException`** — `Arr::keyBy` resolved-key type, `Arr::flatMap` /
  `Iter::flatMap` on a non-iterable callback result.
- **`FilesystemException`** — every `File` failure: a read/write/delete/list/temp/finfo
  native returned failure despite valid preconditions. The abstract `IOException` above it
  is the grouping node future I/O domains slot under — concrete throws always use a
  subclass, so `catch (IOException)` is "any I/O failure", present and future.
- **`MalformedJsonException`** — `Json::encode` / `decode`; see
  [the `Json` wrap](#the-json-wrap).

`Filter`, `Hash` and `Type` throw nothing. Two deliberately-native spots remain outside
the marker: `Dt::parseOrNull` *catches* the engine's `Exception` internally (nothing
escapes), and a drained generator re-consumed through `Iter` raises the engine's own
`Exception` — engine-raised, not the library's to rename.

[↑ Back to top](#exceptions)

---

## The library-scoped catch

What SPL alone cannot express: "a failure from *this* library" as one clause.

```php
use Rak200\Utils\Exception\UtilsException;
use Rak200\Utils\Json;
use Rak200\Utils\Num;

try {
    $config = Json::decode($raw);
    $port   = Num::parseInt($config['port']);
} catch (UtilsException $e) {
    // any rak200/utils failure — malformed JSON and malformed port alike
    $port = 8080;
}
```

Branching by kind still works — the domain classes are ordinary types:

```php
use Rak200\Utils\Arr;
use Rak200\Utils\Exception\LookupException;

try {
    $email = Arr::get($data, 'user.email');
} catch (LookupException) {
    $email = null; // path did not resolve
}
```

[↑ Back to top](#exceptions)

---

## SPL catches keep working

BC holds by inheritance: each domain class extends the SPL type 4.0.0 assigned to its
kind, so a pre-existing `catch (\UnderflowException)` now catches `EmptySourceException`
— a subclass — exactly as before. The SPL ancestry is unchanged: `MalformedArgumentException`
descends from `LogicException` (via `InvalidArgumentException`); `LookupException`,
`EmptySourceException`, `BadCallbackException` and the `IOException` branch descend from
`RuntimeException`; `MalformedJsonException` descends from the native `\JsonException`.

[↑ Back to top](#exceptions)

---

## The `Json` wrap

`Json::encode` / `decode` force `JSON_THROW_ON_ERROR`, and the native `\JsonException`
that raises cannot be subclass-swapped at its throw-site. Both methods therefore catch it
and rethrow `MalformedJsonException` — nothing a caller could previously read is lost:

```php
use Rak200\Utils\Exception\MalformedJsonException;
use Rak200\Utils\Json;

try {
    Json::decode('{invalid}');
} catch (MalformedJsonException $e) {
    $e->getMessage();  // 'Syntax error' — the native's message, verbatim
    $e->getCode();     // JSON_ERROR_SYNTAX — the native's code, verbatim
    $e->getPrevious(); // the original \JsonException
}
```

`MalformedJsonException` itself extends `\JsonException`, so a pre-existing
`catch (\JsonException)` keeps matching too.

[↑ Back to top](#exceptions)
