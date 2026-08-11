# Filter

[← Reference](README.md)

Input sanitisation and lenient coercion of untrusted values. Every method is total — none throws, **including on invalid UTF-8**, which is the input this class exists for. **Sanitisers** are `string → string` transforms; **predicates** (`is*`) answer a `string → bool` format question without changing the value; **coercers** (`to*`) turn a `mixed` value into a typed result, returning the caller-supplied `$default` when the value cannot be represented.

```php
use Rak200\Utils\Filter;
```

The coercers differ from the [`Num`](num.md) parsers on purpose: `Num::parseInt`/`parseFloat` are strict `string` parsers (no surrounding whitespace; throw or return null), whereas `Filter::toInt`/`toFloat` are lenient `mixed` coercers that trim and fall back to a default — the right fit for request data.

## Contents

- [`escapeHtml` / `unescapeHtml`](#escapehtml--unescapehtml)
- [`stripTags`](#striptags)
- [`digits` / `alpha` / `alnum`](#digits--alpha--alnum)
- [`squish`](#squish)
- [`stripControl`](#stripcontrol)
- [`ascii`](#ascii)
- [`email` / `url`](#email--url)
- [`isEmail`](#isemail)
- [`toStr`](#tostr)
- [`toInt`](#toint)
- [`toFloat`](#tofloat)
- [`toBool`](#tobool)

---

## `escapeHtml` / `unescapeHtml`

`escapeHtml` converts `& < > " '` to their HTML entities (`ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8) for safe interpolation into HTML; `unescapeHtml` reverses it. Pass `doubleEncode: false` to leave existing entities untouched.

```php
Filter::escapeHtml('<b>"x" & \'y\'</b>');   // '&lt;b&gt;&quot;x&quot; &amp; &#039;y&#039;&lt;/b&gt;'
Filter::escapeHtml('café <b>');             // 'café &lt;b&gt;'  (multibyte text preserved)
Filter::escapeHtml('&amp;');                // '&amp;amp;'
Filter::escapeHtml('&amp; <', false);       // '&amp; &lt;'      (existing entity kept)

Filter::unescapeHtml('&lt;a&gt; &amp; &quot;x&quot;');   // '<a> & "x"'
```

[↑ Back to top](#filter)

---

## `stripTags`

Strips HTML and PHP tags. `$allowedTags` is a list of tags to keep, in the legacy string form.

```php
Filter::stripTags('<p>Hi <b>there</b></p>');          // 'Hi there'
Filter::stripTags('<p>Hi <b>there</b></p>', '<b>');   // 'Hi <b>there</b>'
```

[↑ Back to top](#filter)

---

## `digits` / `alpha` / `alnum`

Character whitelists: keep only ASCII digits, only Unicode letters (`\p{L}`), or only Unicode letters and numbers (`\p{L}\p{N}`); everything else is removed.

```php
Filter::digits('+55 (11) 99999-0000');   // '5511999990000'
Filter::digits('a1b2c3');                // '123'

Filter::alpha('abc123 def');             // 'abcdef'
Filter::alpha('café 1!');                // 'café'

Filter::alnum('a1 b2! café');            // 'a1b2café'
```

[↑ Back to top](#filter)

---

## `squish`

Collapses every run of whitespace into a single space and trims the ends.

```php
Filter::squish("  a\t\n b   c  ");   // 'a b c'
Filter::squish("   \t\n  ");         // ''
```

[↑ Back to top](#filter)

---

## `stripControl`

Removes ASCII and Unicode control characters (`\p{Cc}` — the C0/C1 ranges, including null bytes, escapes, tabs, and newlines).

```php
Filter::stripControl("a\tb\nc\0d");   // 'abcd'
Filter::stripControl("caf\x00é");     // 'café'
```

[↑ Back to top](#filter)

---

## `ascii`

Transliterates to ASCII on a best-effort basis (via `iconv`'s `ASCII//TRANSLIT//IGNORE`): accented letters become their plain forms and untranslatable characters are dropped. ASCII input passes through unchanged. The exact mapping is platform-dependent, and the input is returned unchanged when `iconv` is unavailable.

```php
Filter::ascii('Hello World 123!');   // 'Hello World 123!'
Filter::ascii('café résumé');        // 'cafe resume'  (best-effort; ASCII-only)
```

[↑ Back to top](#filter)

---

## `email` / `url`

Sanitise by removing characters not allowed in an e-mail address (`FILTER_SANITIZE_EMAIL`) or URL (`FILTER_SANITIZE_URL`). Illegal characters are dropped — this is sanitisation, not validation (see [`isEmail`](#isemail) to validate an address, [`Url::is`](url.md#is) to validate a URL).

```php
Filter::email('john doe@example.com');        // 'johndoe@example.com'
Filter::email('a@b.com<script>');             // 'a@b.comscript'  (brackets dropped, text kept)

Filter::url('https://example.com/path');      // 'https://example.com/path'
```

[↑ Back to top](#filter)

---

## `isEmail`

True when `$value` is a syntactically valid e-mail address (`FILTER_VALIDATE_EMAIL`) — the validating counterpart of [`email`](#email--url), which only strips illegal characters. Structure only: no DNS lookup and no deliverability check. The empty string and surrounding whitespace are rejected, and so is a dot-less domain, so the check is stricter than RFC 5321 allows. This is [`Url::is`](url.md#is) for addresses.

```php
Filter::isEmail('john.doe@example.com');     // true
Filter::isEmail('a+tag@sub.example.co.uk');  // true
Filter::isEmail('john.doe');                 // false  (no @)
Filter::isEmail(' john@example.com ');       // false  (surrounding whitespace)
Filter::isEmail('john@localhost');           // false  (dot-less domain)
```

Sanitising is not validating — the sanitised *result* can pass a check the input never did:

```php
Filter::isEmail('a@b.com<script>');            // false
Filter::email('a@b.com<script>');              // 'a@b.comscript'
Filter::isEmail(Filter::email('a@b.com<script>')); // true
```

[↑ Back to top](#filter)

---

## `toStr`

Coerces to a string: strings pass through; int/float/bool and `Stringable` objects are cast; null, arrays, and non-stringable objects yield `$default`. Note the native bool cast (`true → "1"`, `false → ""`).

```php
Filter::toStr('hi');               // 'hi'
Filter::toStr(42);                 // '42'
Filter::toStr(true);               // '1'
Filter::toStr(null);               // null
Filter::toStr(null, 'fallback');   // 'fallback'
Filter::toStr(['a']);              // null
```

[↑ Back to top](#filter)

---

## `toInt`

Coerces to an int: ints pass through; strings are trimmed and parsed (digits with an optional sign); a float with an integral value is cast. Non-integral floats, bools, and numeric strings with a decimal point yield `$default`.

```php
Filter::toInt('42');           // 42
Filter::toInt(' 42 ');         // 42
Filter::toInt('-7');           // -7
Filter::toInt(3.0);            // 3
Filter::toInt('3.14');         // null
Filter::toInt('abc');          // null
Filter::toInt('abc', 0);       // 0
```

[↑ Back to top](#filter)

---

## `toFloat`

Coerces to a float: ints and floats are cast; strings are trimmed and parsed. Anything else yields `$default`.

```php
Filter::toFloat('3.14');       // 3.14
Filter::toFloat(' 1e3 ');      // 1000.0
Filter::toFloat(42);           // 42.0
Filter::toFloat('abc');        // null
Filter::toFloat('abc', 0.0);   // 0.0
```

[↑ Back to top](#filter)

---

## `toBool`

Coerces to a bool with HTML-form semantics: bools pass through; `1`/`0` map to true/false; strings (case-insensitive, trimmed) `"1"`, `"true"`, `"on"`, `"yes"` are true and `"0"`, `"false"`, `"off"`, `"no"`, `""` are false. Anything else yields `$default`.

```php
Filter::toBool('on');          // true
Filter::toBool('yes');         // true
Filter::toBool('0');           // false
Filter::toBool('');            // false
Filter::toBool('maybe');       // null
Filter::toBool('maybe', false);// false
```

[↑ Back to top](#filter)
