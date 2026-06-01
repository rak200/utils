# Str

[← Reference](README.md)

Multibyte-safe string helpers.

```php
use Rak200\Utils\Str;
```

## Contents

- [`is`](#is)
- [`isBlank` / `isNotBlank` / `isEmpty` / `isNonEmptyStr`](#isblank--isnotblank--isempty--isnonemptystr)
- [`isWhitespace`](#iswhitespace)
- [`length`](#length)
- [`capitalize` / `uncapitalize`](#capitalize--uncapitalize)
- [`upper` / `lower`](#upper--lower)
- [`contains`](#contains)
- [`startsWith` / `endsWith`](#startswith--endswith)
- [`indexOf` / `lastIndexOf`](#indexof--lastindexof)
- [`count`](#count)
- [`span`](#span)
- [`trim` / `trimStart` / `trimEnd`](#trim--trimstart--trimend)
- [`substring`](#substring)
- [`replace`](#replace)
- [`replaceFirst` / `replaceLast`](#replacefirst--replacelast)
- [`translate`](#translate)
- [`split`](#split)
- [`join`](#join)
- [`joinNatural`](#joinnatural)
- [`wrap`](#wrap)
- [`padStart` / `padEnd`](#padstart--padend)
- [`repeat`](#repeat)
- [`reverse`](#reverse)
- [`ord` / `chr`](#ord--chr)
- [`truncate`](#truncate)
- [`slug`](#slug)
- [`toCamel` / `toPascal` / `toSnake` / `toKebab`](#tocamel--topascal--tosnake--tokebab)

---

## `is`

Domain predicate — true when `$value` is a string. Accepts `mixed` so it can be used as a guard on values whose type is not yet known. [`Type::isStr`](type.md#basic-type-checks) is an alias.

```php
Str::is('hello');   // true
Str::is('');        // true
Str::is(42);        // false
Str::is(null);      // false
Str::is([]);        // false
```

[↑ Back to top](#str)

---

## `isBlank` / `isNotBlank` / `isEmpty` / `isNonEmptyStr`

`isBlank` and `isNotBlank` accept `mixed` so they double as type guards: `isBlank` is true for `null`, empty strings, and whitespace-only strings; `isNotBlank` is true only for strings that have at least one non-whitespace character (non-strings → false). `isEmpty` is string-typed and checks literal length zero. `isNonEmptyStr` accepts `mixed` and is true for any string with at least one character (whitespace counts).

```php
Str::isBlank('   ');         // true
Str::isBlank("\t\n");        // true
Str::isBlank('a');           // false
Str::isBlank(null);          // true
Str::isBlank(0);             // false   (non-string, non-null)

Str::isNotBlank('a');        // true
Str::isNotBlank('  ');       // false
Str::isNotBlank(null);       // false

Str::isEmpty('');            // true
Str::isEmpty(' ');           // false

Str::isNonEmptyStr('a');     // true
Str::isNonEmptyStr(' ');     // true
Str::isNonEmptyStr('');      // false
Str::isNonEmptyStr(null);    // false
```

[↑ Back to top](#str)

---

## `isWhitespace`

True when every character of `$value` is ASCII whitespace (`[ \t\n\r\v\f]`). False for the empty string. Wraps [`ctype_space()`](https://www.php.net/manual/en/function.ctype-space.php), so the check is byte-level — Unicode-only whitespace like `U+00A0` (non-breaking space) returns `false`.

```php
Str::isWhitespace(' ');         // true
Str::isWhitespace("\t\n");      // true
Str::isWhitespace('');          // false
Str::isWhitespace(' a ');       // false
Str::isWhitespace("\xC2\xA0");  // false   (U+00A0 — not ASCII whitespace)
```

[↑ Back to top](#str)

---

## `length`

Number of Unicode characters (not bytes).

```php
Str::length('hello');     // 5
Str::length('ação');      // 4
```

[↑ Back to top](#str)

---

## `capitalize` / `uncapitalize`

Toggle the case of the first character only (multibyte-aware). The rest of the string is left untouched.

```php
Str::capitalize('hello');     // 'Hello'
Str::capitalize('ácido');     // 'Ácido'
Str::uncapitalize('HELLO');   // 'hELLO'
```

[↑ Back to top](#str)

---

## `upper` / `lower`

Case every character.

```php
Str::upper('hello');    // 'HELLO'
Str::upper('ção');      // 'ÇÃO'
Str::lower('HELLO');    // 'hello'
```

[↑ Back to top](#str)

---

## `contains`

Substring check. An empty needle is always contained.

```php
Str::contains('hello world', 'world');   // true
Str::contains('hello world', 'WORLD');   // false
Str::contains('anything', '');           // true
```

[↑ Back to top](#str)

---

## `startsWith` / `endsWith`

```php
Str::startsWith('hello world', 'hello');   // true
Str::startsWith('hello world', 'world');   // false
Str::endsWith('hello world', 'world');     // true
Str::endsWith('file.tar.gz', '.gz');       // true
```

[↑ Back to top](#str)

---

## `indexOf` / `lastIndexOf`

0-based character index of the first/last occurrence of `$needle` (multibyte-aware), or `-1` when not found. An empty needle returns `-1` (no meaningful position). Both accept an `$offset` to bound the search and an `$ignoreCase` flag for case-insensitive matching.

```php
Str::indexOf('hello world', 'world');            // 6
Str::indexOf('hello', 'xyz');                    // -1
Str::indexOf('ação válida', 'á', 2);             // 7
Str::indexOf('Hello', 'h', 0, true);             // 0   (case-insensitive)
Str::lastIndexOf('abcabc', 'c');                 // 5
Str::lastIndexOf('abc', 'z');                    // -1
Str::lastIndexOf('Hello Hello', 'h', 0, true);   // 6   (case-insensitive)
```

[↑ Back to top](#str)

---

## `count`

Number of non-overlapping occurrences of `$needle` in `$haystack` (byte-level via `substr_count`). An empty needle returns `0`.

```php
Str::count('abcabcabc', 'a');       // 3
Str::count('aaaa', 'aa');           // 2   (non-overlapping)
Str::count('abc', 'z');             // 0
```

[↑ Back to top](#str)

---

## `span`

Length of the initial run of `$value` made up only of characters in `$chars`, optionally within the window starting at byte offset `$start` for `$length` bytes (byte-level, via `strspn`). Equals [`length`](#length) exactly when every character of `$value` is in `$chars` — handy for "does this contain only these characters?" checks.

```php
Str::span('hello', 'helo');      // 5   (every char is in the set)
Str::span('aaabbb', 'a');        // 3   (leading run only)
Str::span('01x1', '01');         // 2   (stops at the first char outside the set)
Str::span('xx0011', '01', 2);    // 4   ($start offsets into the string)
```

[↑ Back to top](#str)

---

## `trim` / `trimStart` / `trimEnd`

Strip characters from one or both ends. Default character set is ASCII whitespace.

```php
Str::trim('  hello  ');                // 'hello'
Str::trim('---hello---', '-');         // 'hello'
Str::trimStart('  hello  ');           // 'hello  '
Str::trimEnd('  hello  ');             // '  hello'
```

[↑ Back to top](#str)

---

## `substring`

Multibyte-safe slicing. When `$length` is `null`, takes everything from `$start` to the end.

```php
Str::substring('hello', 1, 3);        // 'ell'
Str::substring('ação', 1);            // 'ção'
Str::substring('hello', -3);          // 'llo'
```

[↑ Back to top](#str)

---

## `replace`

Replaces every occurrence of `$search` with `$replacement`.

```php
Str::replace('hello world', 'world', 'there');   // 'hello there'
Str::replace('a-b-c', '-', '/');                 // 'a/b/c'
```

[↑ Back to top](#str)

---

## `replaceFirst` / `replaceLast`

Replace only the first/last occurrence. Returns the subject unchanged when `$search` is empty or not found.

```php
Str::replaceFirst('foo-foo-foo', 'foo', 'xyz');   // 'xyz-foo-foo'
Str::replaceLast('foo-foo-foo', 'foo', 'xyz');    // 'foo-foo-xyz'
Str::replaceFirst('hello', 'x', 'y');             // 'hello'  (not found)
```

[↑ Back to top](#str)

---

## `translate`

Replace characters by position: each character in `$from` maps to the character at the same position in `$to` (multibyte-aware, single pass — characters introduced by the replacement are not re-translated). Throws when `$from` and `$to` differ in character length.

```php
Str::translate('hello', 'el', 'ip');         // 'hippo'
Str::translate('ab', 'ab', 'bc');            // 'bc'   (single pass; chaining would give 'cc')
Str::translate('áéíóú', 'áéíóú', 'aeiou');   // 'aeiou'
```

[↑ Back to top](#str)

---

## `split`

Split on `$separator`. An empty separator yields individual characters; `$limit` (if given) controls the chunk size in that mode.

```php
Str::split('a,b,c,d', ',');       // ['a', 'b', 'c', 'd']
Str::split('a,b,c,d', ',', 2);    // ['a', 'b,c,d']
Str::split('abc', '');            // ['a', 'b', 'c']
Str::split('abcdef', '', 2);      // ['ab', 'cd', 'ef']
```

[↑ Back to top](#str)

---

## `join`

Join iterable items with `$separator`, like `implode()`. `$prefix` / `$suffix` wrap a non-empty result; `$lastSeparator` (with 2+ parts) joins the final two parts (Oxford-comma style).

> **Deprecation:** the default `$skipBlanks = true` (silently dropping blank items) is deprecated since 1.12.0 and will be removed in 2.0.0 — it emits an `E_USER_DEPRECATED`. Use [`joinNatural`](#joinnatural) to keep that behaviour, or pass `skipBlanks: false` for a plain `implode()`-style join.

```php
Str::join(['a', 'b', 'c'], skipBlanks: false);                         // 'abc'   (default '' separator = concat)
Str::join(['a', '', 'b'], ',', skipBlanks: false);                     // 'a,,b'  (mirrors implode)
Str::join(['a', 'b', 'c'], ', ', '[', ']', skipBlanks: false);         // '[a, b, c]'
Str::join(['a', 'b', 'c'], ', ', '', '', ' and ', skipBlanks: false);  // 'a, b and c'
```

[↑ Back to top](#str)

---

## `joinNatural`

Join iterable items into a natural-language string: **blank items are dropped**, `$prefix` / `$suffix` wrap a non-empty result, and `$lastSeparator` (with 2+ parts) joins the final two parts (Oxford-comma style). Returns `''` when no non-blank items remain.

```php
Str::joinNatural(['a', 'b', 'c'], ', ');                    // 'a, b, c'
Str::joinNatural(['a', '', 'b']);                           // 'ab'  (default '' separator = concat, blanks dropped)
Str::joinNatural(['a', '', 'b', '   ', 'c'], ', ');         // 'a, b, c'  (blanks dropped)
Str::joinNatural(['a', 'b', 'c'], ', ', '[', ']');          // '[a, b, c]'
Str::joinNatural(['a', 'b', 'c'], ', ', '', '', ' and ');   // 'a, b and c'
```

[↑ Back to top](#str)

---

## `wrap`

Wrap a non-blank string with `$prefix` and `$suffix`; returns `''` when the input is blank.

```php
Str::wrap('hello', '[', ']');     // '[hello]'
Str::wrap('', '[', ']');          // ''
Str::wrap('   ', '[', ']');       // ''
```

[↑ Back to top](#str)

---

## `padStart` / `padEnd`

Pad to a target length using `$pad` (multibyte-aware).

```php
Str::padStart('42', 5, '0');      // '00042'
Str::padEnd('hi', 5, '.');        // 'hi...'
Str::padStart('já', 5);           // '   já'
```

[↑ Back to top](#str)

---

## `repeat`

```php
Str::repeat('ab', 3);     // 'ababab'
Str::repeat('-', 5);      // '-----'
```

[↑ Back to top](#str)

---

## `reverse`

Multibyte-aware reverse.

```php
Str::reverse('hello');    // 'olleh'
Str::reverse('ação');     // 'oãça'
```

[↑ Back to top](#str)

---

## `ord` / `chr`

Convert between a character and its Unicode code point (multibyte-aware). `ord` returns the code point of the first character and throws on an empty string or invalid UTF-8; `chr` returns the character for a code point and throws when it is outside `0`–`0x10FFFF`.

```php
Str::ord('A');        // 65
Str::ord('€');        // 8364
Str::chr(65);         // 'A'
Str::chr(0x1F600);    // '😀'
```

[↑ Back to top](#str)

---

## `truncate`

Truncates to at most `$length` characters, appending `$ellipsis` when truncation actually happens. When `$length` is shorter than `$ellipsis`, returns the leading `$length` characters of `$ellipsis`.

```php
Str::truncate('hello', 10);              // 'hello'
Str::truncate('hello world', 4);         // 'hel…'
Str::truncate('hello world', 6);         // 'hello…'
Str::truncate('hello', 2);               // 'h…'
Str::truncate('hello world', 5, '...');  // 'he...'
```

[↑ Back to top](#str)

---

## `slug`

URL-friendly slug. Best-effort transliteration to ASCII via `iconv`, lowercases, and collapses runs of non-alphanumerics into `$separator`.

```php
Str::slug('Hello World!');           // 'hello-world'
Str::slug('Olá, mundo!');            // 'ola-mundo'
Str::slug('foo  bar', '_');          // 'foo_bar'
Str::slug('   ');                    // ''
```

[↑ Back to top](#str)

---

## `toCamel` / `toPascal` / `toSnake` / `toKebab`

Case conversion. Splits the input into words on whitespace, dashes, underscores, and case transitions (camelCase / PascalCase boundaries — unicode-aware via `\p{Ll}` / `\p{Nd}` / `\p{Lu}`).

```php
Str::toCamel('hello world');     // 'helloWorld'
Str::toCamel('user-id');         // 'userId'
Str::toPascal('hello world');    // 'HelloWorld'
Str::toPascal('hello-world');    // 'HelloWorld'
Str::toSnake('HelloWorld');      // 'hello_world'
Str::toSnake('helloWorld');      // 'hello_world'
Str::toSnake('HTMLParser');      // 'html_parser'
Str::toSnake('óÁgua');           // 'ó_água'        (unicode boundary)
Str::toKebab('HelloWorld');      // 'hello-world'
```

> The legacy names `toCamelCase` / `toPascalCase` / `toSnakeCase` / `toKebabCase` remain available as `@deprecated` aliases since 1.2.0 and will be removed in 2.0.0.

[↑ Back to top](#str)
