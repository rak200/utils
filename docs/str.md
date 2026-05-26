# Str

[← Reference](README.md)

Multibyte-safe string helpers.

```php
use Rak200\Utils\Str;
```

## Contents

- [`isBlank` / `isNotBlank` / `isEmpty` / `isNonEmptyStr`](#isblank--isnotblank--isempty--isnonemptystr)
- [`length`](#length)
- [`capitalize` / `uncapitalize`](#capitalize--uncapitalize)
- [`upper` / `lower`](#upper--lower)
- [`contains`](#contains)
- [`startsWith` / `endsWith`](#startswith--endswith)
- [`indexOf` / `lastIndexOf`](#indexof--lastindexof)
- [`count`](#count)
- [`trim` / `trimStart` / `trimEnd`](#trim--trimstart--trimend)
- [`substring`](#substring)
- [`replace`](#replace)
- [`replaceFirst` / `replaceLast`](#replacefirst--replacelast)
- [`split`](#split)
- [`join`](#join)
- [`wrap`](#wrap)
- [`padStart` / `padEnd`](#padstart--padend)
- [`repeat`](#repeat)
- [`reverse`](#reverse)
- [`truncate`](#truncate)
- [`slug`](#slug)
- [`toCamelCase` / `toPascalCase` / `toSnakeCase` / `toKebabCase`](#tocamelcase--topascalcase--tosnakecase--tokebabcase)

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

0-based character index of the first/last occurrence of `$needle` (multibyte-aware), or `-1` when not found. An empty needle returns `-1` (no meaningful position).

```php
Str::indexOf('hello world', 'world');     // 6
Str::indexOf('hello', 'xyz');             // -1
Str::indexOf('ação válida', 'á', 2);      // 7
Str::lastIndexOf('abcabc', 'c');          // 5
Str::lastIndexOf('abc', 'z');             // -1
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

Join iterable items into a string, ignoring blank elements. `$lastSeparator`, when given, is used between the final two parts (Oxford-comma style).

```php
Str::join(['a', 'b', 'c'], ', ');                    // 'a, b, c'
Str::join(['a', '', 'b', '   ', 'c'], ', ');         // 'a, b, c'
Str::join(['a', 'b', 'c'], ', ', '[', ']');          // '[a, b, c]'
Str::join(['a', 'b', 'c'], ', ', '', '', ' and ');   // 'a, b and c'
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

## `toCamelCase` / `toPascalCase` / `toSnakeCase` / `toKebabCase`

Case conversion. Splits the input into words on whitespace, dashes, underscores, and case transitions (camelCase / PascalCase boundaries — unicode-aware via `\p{Ll}` / `\p{Nd}` / `\p{Lu}`).

```php
Str::toCamelCase('hello world');     // 'helloWorld'
Str::toCamelCase('user-id');         // 'userId'
Str::toPascalCase('hello world');    // 'HelloWorld'
Str::toPascalCase('hello-world');    // 'HelloWorld'
Str::toSnakeCase('HelloWorld');      // 'hello_world'
Str::toSnakeCase('helloWorld');      // 'hello_world'
Str::toSnakeCase('HTMLParser');      // 'html_parser'
Str::toSnakeCase('óÁgua');           // 'ó_água'        (unicode boundary)
Str::toKebabCase('HelloWorld');      // 'hello-world'
```

[↑ Back to top](#str)
