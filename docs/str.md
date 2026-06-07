# Str

[← Reference](README.md)

Multibyte-safe string helpers.

```php
use Rak200\Utils\Str;
```

## Contents

- [`is`](#is)
- [`isBlank` / `isNotBlank` / `isEmpty`](#isblank--isnotblank--isempty)
- [`isWhitespace`](#iswhitespace)
- [`isDigits` / `isAlpha` / `isAlnum`](#isdigits--isalpha--isalnum)
- [`len` / `byteLen`](#len--bytelen)
- [`toBytes` / `fromBytes`](#tobytes--frombytes)
- [`capitalize` / `uncapitalize`](#capitalize--uncapitalize)
- [`upper` / `lower`](#upper--lower)
- [`title`](#title)
- [`contains`](#contains)
- [`startsWith` / `endsWith`](#startswith--endswith)
- [`indexOf` / `lastIndexOf`](#indexof--lastindexof)
- [`count`](#count)
- [`before` / `after`](#before--after)
- [`span`](#span)
- [`trim` / `trimStart` / `trimEnd`](#trim--trimstart--trimend)
- [`sub`](#sub)
- [`replace`](#replace)
- [`replaceFirst` / `replaceLast`](#replacefirst--replacelast)
- [`replaceAt`](#replaceat)
- [`translate`](#translate)
- [`split`](#split)
- [`join`](#join)
- [`joinNatural`](#joinnatural)
- [`wrap`](#wrap)
- [`wordWrap`](#wordwrap)
- [`wordCount`](#wordcount)
- [`padStart` / `padEnd`](#padstart--padend)
- [`repeat`](#repeat)
- [`reverse`](#reverse)
- [`ord` / `chr`](#ord--chr)
- [`trunc`](#trunc)
- [`slug`](#slug)
- [`mask`](#mask)
- [`format` / `scan`](#format--scan)
- [`levenshtein` / `similarity`](#levenshtein--similarity)
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

## `isBlank` / `isNotBlank` / `isEmpty`

`isBlank` and `isNotBlank` accept `mixed` so they double as type guards: `isBlank` is true for `null`, empty strings, and whitespace-only strings; `isNotBlank` is true only for strings that have at least one non-whitespace character (non-strings → false). `isEmpty` is string-typed and checks literal length zero.

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

## `isDigits` / `isAlpha` / `isAlnum`

ASCII character-class predicates (via the `ctype_*` family): all digits, all letters, or all letters/digits. Byte-level — multibyte letters/digits are not recognised — and the empty string is always `false`. These differ from the [`Filter`](filter.md) sanitizers, which strip unwanted characters.

```php
Str::isDigits('0123');     // true
Str::isDigits('12.3');     // false
Str::isDigits('');         // false
Str::isAlpha('abcXYZ');    // true
Str::isAlpha('abcé');      // false   (é is not ASCII)
Str::isAlnum('abc123');    // true
Str::isAlnum('abc 123');   // false   (space)
```

[↑ Back to top](#str)

---

## `len` / `byteLen`

`len` counts Unicode characters; `byteLen` counts raw bytes (the byte-level counterpart, for when byte offsets matter). The two agree for pure-ASCII input and diverge once multibyte characters appear.

```php
Str::len('hello');         // 5
Str::len('ação');          // 4
Str::byteLen('hello');     // 5
Str::byteLen('ação');      // 6   ('ç' and 'ã' are two bytes each in UTF-8)
```

[↑ Back to top](#str)

---

## `toBytes` / `fromBytes`

Convert between a binary string and a 0-indexed list of its byte values (0–255) — the raw-string counterpart to [`Hex::toBytes`](hex.md) / `Hex::fromBytes`. These are bytes, not Unicode code points (use [`ord`](#ord--chr) for a character's code point). `fromBytes` throws on a value outside 0–255.

```php
Str::toBytes('hi');           // [104, 105]
Str::toBytes("\x00\xff");     // [0, 255]
Str::toBytes('');             // []
Str::fromBytes([104, 105]);   // 'hi'
Str::fromBytes([0, 255]);     // "\x00\xff"
Str::fromBytes([256]);        // throws RuntimeException (out of range)
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

## `title`

Title-cases the string: the first letter of each word uppercased, the rest lowercased (multibyte-aware, via `mb_convert_case`).

```php
Str::title('hello world');   // 'Hello World'
Str::title('hello WORLD');   // 'Hello World'
Str::title('árvore útil');   // 'Árvore Útil'
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

## `before` / `after`

Slice around the first occurrence of `$search`. When `$search` is empty or not found, the whole subject is returned unchanged.

```php
Str::before('user@host', '@');   // 'user'
Str::after('user@host', '@');    // 'host'
Str::before('a.b.c', '.');       // 'a'      (first occurrence)
Str::after('a.b.c', '.');        // 'b.c'
Str::after('abc', '@');          // 'abc'    (not found → whole)
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

## `sub`

Multibyte-safe slicing. When `$length` is `null`, takes everything from `$start` to the end.

```php
Str::sub('hello', 1, 3);        // 'ell'
Str::sub('ação', 1);            // 'ção'
Str::sub('hello', -3);          // 'llo'
```

[↑ Back to top](#str)

---

## `replace`

Replaces every occurrence of `$search` with `$replacement`. Pass `ignoreCase: true` for a case-insensitive match.

```php
Str::replace('hello world', 'world', 'there');   // 'hello there'
Str::replace('a-b-c', '-', '/');                 // 'a/b/c'
Str::replace('Hello HELLO', 'hello', 'x', ignoreCase: true);  // 'x x'
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

## `replaceAt`

Replaces the `$length` characters starting at character index `$start` with `$replacement` (multibyte-aware). A negative `$start` counts from the end; a negative `$length` leaves that many characters untouched at the end; `$length = 0` inserts without removing.

```php
Str::replaceAt('hello', 1, 3, 'XY');     // 'hXYo'
Str::replaceAt('abc', 1, 0, '-');        // 'a-bc'   (insert)
Str::replaceAt('hello', -2, 2, '!!');    // 'hel!!'  (negative start)
Str::replaceAt('hello', 1, -1, 'X');     // 'hXo'    (negative length)
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

Split on `$separator` (default `''`). The empty separator yields individual characters, in which case `$limit` controls the chunk size; with a real separator, `$limit` caps the number of pieces (the last piece keeps the remainder).

```php
Str::split('abc');                // ['a', 'b', 'c']   (default '' separator)
Str::split('a,b,c,d', ',');       // ['a', 'b', 'c', 'd']
Str::split('a,b,c,d', ',', 2);    // ['a', 'b,c,d']
Str::split('abcdef', '', 2);      // ['ab', 'cd', 'ef']
```

[↑ Back to top](#str)

---

## `join`

Join iterable items into a string with `$separator` between them, like `implode()` — but accepting any iterable, not just an array. `$separator` defaults to `''` (concatenate). For dropping blank items, wrapping with a prefix/suffix, or an Oxford-style final separator, use [`joinNatural`](#joinnatural).

```php
Str::join(['a', 'b', 'c']);          // 'abc'   (default '' separator = concat)
Str::join(['a', 'b', 'c'], ',');     // 'a,b,c'
Str::join(['a', '', 'b'], ',');      // 'a,,b'  (blanks kept, mirrors implode)
Str::join([], ',');                  // ''
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

## `wordWrap`

Wraps `$value` so no line exceeds `$width` characters, breaking on spaces with `$break`. With `cut: true`, words longer than `$width` are split mid-word. Byte-level (via `wordwrap`); reliable for ASCII text. Throws when `$width < 1`.

```php
Str::wordWrap('aaa bbb ccc', 7);          // "aaa bbb\nccc"
Str::wordWrap('aaa bbb', 4, '-');         // 'aaa-bbb'
Str::wordWrap('abcd', 2, "\n", true);     // "ab\ncd"   (cut long words)
```

[↑ Back to top](#str)

---

## `wordCount`

Counts words — maximal runs of letters or digits, Unicode-aware. Punctuation and whitespace separate words.

```php
Str::wordCount('one two three');   // 3
Str::wordCount("it's");            // 2     (apostrophe splits the word)
Str::wordCount('café résumé');     // 2     (multibyte words counted)
Str::wordCount('   ');             // 0
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

## `trunc`

Truncates to at most `$length` characters, appending `$ellipsis` when truncation actually happens. When `$length` is shorter than `$ellipsis`, returns the leading `$length` characters of `$ellipsis`.

```php
Str::trunc('hello', 10);              // 'hello'
Str::trunc('hello world', 4);         // 'hel…'
Str::trunc('hello world', 6);         // 'hello…'
Str::trunc('hello', 2);               // 'h…'
Str::trunc('hello world', 5, '...');  // 'he...'
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

## `mask`

Masks PII for safe display. Within the window `[start, start+length)`, every character not in `$keep` becomes the first character of `$mask` (length preserved); characters in `$keep` (formatting separators) pass through. Negative `$start` counts from the end; `null` `$length` runs to the end; negative `$length` leaves that many characters untouched at the end. Throws when `$mask` is empty.

```php
// simple (no keep)
Str::mask('4111111111111111', 0, -4);        // '************1111'  (card)
Str::mask('taylor@example.com', 3);          // 'tay***************' (e-mail)
Str::mask('secret', -3);                     // 'sec***'
Str::mask('1234', 0, 2, '#');                // '##34'

// keep separators (Brazilian CPF)
Str::mask('123.456.789-09', 0, -2, keep: '.-');   // '***.***.***-09'
Str::mask('123.456.789-09', 4, -2, keep: '.-');   // '123.***.***-09'

// non-contiguous (1st + 3rd groups) by composition
Str::mask(Str::mask('123.456.789-09', 0, 3, keep: '.-'), 8, 3, keep: '.-');
// '***.456.***-09'
```

[↑ Back to top](#str)

---

## `format` / `scan`

`format` builds a string printf-style (like `vsprintf`); `scan` is the inverse, parsing a string against a printf-style format (like `sscanf`). In `scan`, each conversion that finds no match yields `null`.

```php
Str::format('%s is %d', 'x', 5);     // 'x is 5'
Str::format('%.2f', 3.14159);        // '3.14'

Str::scan('age:42', 'age:%d');       // [42]
Str::scan('John 25', '%s %d');       // ['John', 25]
Str::scan('nope', 'age:%d');         // [null]
```

[↑ Back to top](#str)

---

## `levenshtein` / `similarity`

String-distance metrics (byte-level). `levenshtein` is the edit distance — the minimum single-character insertions, deletions, or substitutions (returns `-1` when either string exceeds 255 bytes). `similarity` is a `0.0`–`100.0` percentage (via `similar_text`) and is asymmetric — swapping the arguments can change the result.

```php
Str::levenshtein('kitten', 'sitting');   // 3
Str::levenshtein('abc', 'abc');          // 0
Str::similarity('abc', 'abc');           // 100.0
Str::similarity('World', 'word');        // 66.66666666666666
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

[↑ Back to top](#str)
