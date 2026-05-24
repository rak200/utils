# Str

Multibyte-safe string helpers.

```php
use Rak200\Utils\Str;
```

## Contents

- [`isBlank` / `isNotBlank` / `isEmpty`](#isblank--isnotblank--isempty)
- [`length`](#length)
- [`capitalize` / `uncapitalize`](#capitalize--uncapitalize)
- [`upper` / `lower`](#upper--lower)
- [`contains`](#contains)
- [`startsWith` / `endsWith`](#startswith--endswith)
- [`trim` / `trimStart` / `trimEnd`](#trim--trimstart--trimend)
- [`replace`](#replace)
- [`split`](#split)
- [`join`](#join)
- [`wrap`](#wrap)
- [`padStart` / `padEnd`](#padstart--padend)
- [`repeat`](#repeat)
- [`reverse`](#reverse)
- [`toCamelCase` / `toPascalCase` / `toSnakeCase` / `toKebabCase`](#tocamelcase--topascalcase--tosnakecase--tokebabcase)

---

## `isBlank` / `isNotBlank` / `isEmpty`

`isBlank` treats whitespace-only as empty; `isEmpty` requires literal length zero; `isNotBlank` is the inverse of `isBlank`.

```php
Str::isBlank('   ');      // true
Str::isBlank("\t\n");     // true
Str::isBlank('a');        // false
Str::isNotBlank('a');     // true
Str::isEmpty('');         // true
Str::isEmpty(' ');        // false
```

---

## `length`

Number of Unicode characters (not bytes).

```php
Str::length('hello');     // 5
Str::length('ação');      // 4
```

---

## `capitalize` / `uncapitalize`

Toggle the case of the first character only (multibyte-aware). The rest of the string is left untouched.

```php
Str::capitalize('hello');     // 'Hello'
Str::capitalize('ácido');     // 'Ácido'
Str::uncapitalize('HELLO');   // 'hELLO'
```

---

## `upper` / `lower`

Case every character.

```php
Str::upper('hello');    // 'HELLO'
Str::upper('ção');      // 'ÇÃO'
Str::lower('HELLO');    // 'hello'
```

---

## `contains`

Substring check. An empty needle is always contained.

```php
Str::contains('hello world', 'world');   // true
Str::contains('hello world', 'WORLD');   // false
Str::contains('anything', '');           // true
```

---

## `startsWith` / `endsWith`

```php
Str::startsWith('hello world', 'hello');   // true
Str::startsWith('hello world', 'world');   // false
Str::endsWith('hello world', 'world');     // true
Str::endsWith('file.tar.gz', '.gz');       // true
```

---

## `trim` / `trimStart` / `trimEnd`

Strip characters from one or both ends. Default character set is ASCII whitespace.

```php
Str::trim('  hello  ');                // 'hello'
Str::trim('---hello---', '-');         // 'hello'
Str::trimStart('  hello  ');           // 'hello  '
Str::trimEnd('  hello  ');             // '  hello'
```

---

## `replace`

Replaces every occurrence of `$search` with `$replacement`.

```php
Str::replace('hello world', 'world', 'there');   // 'hello there'
Str::replace('a-b-c', '-', '/');                 // 'a/b/c'
```

---

## `split`

Split on `$separator`. An empty separator yields individual characters; `$limit` (if given) controls the chunk size in that mode.

```php
Str::split('a,b,c,d', ',');       // ['a', 'b', 'c', 'd']
Str::split('a,b,c,d', ',', 2);    // ['a', 'b,c,d']
Str::split('abc', '');            // ['a', 'b', 'c']
Str::split('abcdef', '', 2);      // ['ab', 'cd', 'ef']
```

---

## `join`

Join iterable items into a string, ignoring blank elements. `$lastSeparator`, when given, is used between the final two parts (Oxford-comma style).

```php
Str::join(['a', 'b', 'c'], ', ');                    // 'a, b, c'
Str::join(['a', '', 'b', '   ', 'c'], ', ');         // 'a, b, c'
Str::join(['a', 'b', 'c'], ', ', '[', ']');          // '[a, b, c]'
Str::join(['a', 'b', 'c'], ', ', '', '', ' and ');   // 'a, b and c'
```

---

## `wrap`

Wrap a non-blank string with `$prefix` and `$suffix`; returns `''` when the input is blank.

```php
Str::wrap('hello', '[', ']');     // '[hello]'
Str::wrap('', '[', ']');          // ''
Str::wrap('   ', '[', ']');       // ''
```

---

## `padStart` / `padEnd`

Pad to a target length using `$pad` (multibyte-aware).

```php
Str::padStart('42', 5, '0');      // '00042'
Str::padEnd('hi', 5, '.');        // 'hi...'
Str::padStart('já', 5);           // '   já'
```

---

## `repeat`

```php
Str::repeat('ab', 3);     // 'ababab'
Str::repeat('-', 5);      // '-----'
```

---

## `reverse`

Multibyte-aware reverse.

```php
Str::reverse('hello');    // 'olleh'
Str::reverse('ação');     // 'oãça'
```

---

## `toCamelCase` / `toPascalCase` / `toSnakeCase` / `toKebabCase`

Case conversion. Splits the input into words on whitespace, dashes, underscores, and case transitions (camelCase / PascalCase boundaries).

```php
Str::toCamelCase('hello world');     // 'helloWorld'
Str::toCamelCase('user-id');         // 'userId'
Str::toPascalCase('hello world');    // 'HelloWorld'
Str::toPascalCase('hello-world');    // 'HelloWorld'
Str::toSnakeCase('HelloWorld');      // 'hello_world'
Str::toSnakeCase('helloWorld');      // 'hello_world'
Str::toKebabCase('HelloWorld');      // 'hello-world'
```
