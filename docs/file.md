# File

[← Reference](README.md)

Filesystem helpers — every operation throws on failure instead of returning the silent `false` of the underlying PHP functions.

```php
use Rak200\Utils\File;
```

## Contents

- [`read` / `write` / `append`](#read--write--append)
- [`touch`](#touch)
- [`exists`](#exists)
- [`isFile` / `isDir`](#isfile--isdir)
- [`delete`](#delete)
- [`mkdir`](#mkdir)
- [`list`](#list)
- [`ext` / `basename` / `dirname`](#ext--basename--dirname)
- [`realpath`](#realpath)
- [`mime`](#mime)
- [`size`](#size)
- [`lines`](#lines)
- [`readCsv` / `writeCsv`](#readcsv--writecsv)
- [`temp`](#temp)
- [`copy` / `move`](#copy--move)

---

## `read` / `write` / `append`

`write` overwrites; `append` adds to the end (creating the file if missing).

```php
File::write('/tmp/greeting.txt', "Hello\n");
File::append('/tmp/greeting.txt', "World\n");
File::read('/tmp/greeting.txt');    // "Hello\nWorld\n"
```

[↑ Back to top](#file)

---

## `touch`

Sets the modification time of `$path` (defaulting to now), creating an empty file when it does not exist.

```php
File::touch('/tmp/marker');                 // create (or bump mtime)
File::touch('/tmp/marker', 1_600_000_000);  // set a specific Unix timestamp
```

[↑ Back to top](#file)

---

## `exists`

True for any file, directory, or symlink at `$path`.

```php
File::exists('/tmp/greeting.txt');     // true
File::exists('/does/not/exist');       // false
```

[↑ Back to top](#file)

---

## `isFile` / `isDir`

Type-discriminating checks.

```php
File::isFile('/tmp/greeting.txt');     // true
File::isFile('/tmp');                  // false
File::isDir('/tmp');                   // true
File::isDir('/tmp/greeting.txt');      // false
```

> The legacy name `isDirectory` remains available as a `@deprecated` alias since 1.2.0 and will be removed in 2.0.0.

[↑ Back to top](#file)

---

## `delete`

No-op when the file does not exist; only throws when it exists and cannot be deleted.

```php
File::delete('/tmp/greeting.txt');     // removes the file
File::delete('/tmp/already-gone');     // no-op, no exception
```

[↑ Back to top](#file)

---

## `mkdir`

Creates a directory. Recursive by default. No-op when the directory already exists.

```php
File::mkdir('/tmp/cache/build/2026');           // creates parents as needed
File::mkdir('/tmp/cache/build/2026');           // no-op (already exists)
File::mkdir('/tmp/cache/build', recursive: false);
```

[↑ Back to top](#file)

---

## `list`

Returns the entries in `$dir` matching the glob `$pattern` (default `'*'` — everything except dotfiles). Result is a 0-indexed list of absolute paths.

```php
File::list('/var/log');                // every entry under /var/log
File::list('/var/log', '*.log');       // only files ending in .log
File::list('/var/log', 'app-*.log');   // glob with prefix
```

[↑ Back to top](#file)

---

## `ext` / `basename` / `dirname`

Path-component helpers.

```php
File::ext('/var/log/app.log');                   // 'log'
File::ext('README');                             // ''
File::basename('/var/log/app.log');              // 'app.log'
File::basename('/var/log/app.log', '.log');      // 'app'
File::dirname('/var/log/app.log');               // '/var/log'
```

> The previous name `extension` remains as a `@deprecated` alias since 1.14.0 and will be removed in 2.0.0.

[↑ Back to top](#file)

---

## `realpath`

Resolves `$path` to its canonical absolute form, following symlinks and collapsing `.`/`..`. The path must exist on disk (otherwise it throws). For pure, disk-free path math use [`Path`](path.md).

```php
File::realpath('/tmp/../tmp/./app.log');     // '/tmp/app.log'
File::realpath('relative/file.txt');         // absolute path from the CWD
File::realpath('/no/such/path');             // throws RuntimeException
```

[↑ Back to top](#file)

---

## `mime`

Detected via `fileinfo`.

```php
File::mime('/path/to/photo.png');     // 'image/png'
File::mime('/path/to/script.sh');     // 'text/x-shellscript'
```

> The previous name `mimeType` remains as a `@deprecated` alias since 1.14.0 and will be removed in 2.0.0.

[↑ Back to top](#file)

---

## `size`

Size in bytes.

```php
File::size('/path/to/photo.png');     // e.g. 245631
```

[↑ Back to top](#file)

---

## `lines`

Lazy line generator (CR/LF stripped). The file handle is closed automatically when iteration ends or aborts.

```php
foreach (File::lines('/var/log/app.log') as $line) {
    if (str_contains($line, 'ERROR')) {
        echo $line . PHP_EOL;
    }
}
```

[↑ Back to top](#file)

---

## `readCsv` / `writeCsv`

Read a CSV file into a list of row arrays, or write rows back out. `readCsv` skips fully blank lines; both default `$escape` to `''` (no escape character — the modern CSV behaviour PHP 8.4 recommends) and accept custom `$separator`/`$enclosure`. Field values are quoted automatically when they contain the separator, quotes, or newlines.

```php
File::writeCsv('/tmp/people.csv', [
    ['id', 'name'],
    ['1', 'Ann'],
    ['2', 'a,b "c"'],
]);

File::readCsv('/tmp/people.csv');
// [['id', 'name'], ['1', 'Ann'], ['2', 'a,b "c"']]

File::readCsv('/tmp/semicolons.csv', separator: ';');
```

[↑ Back to top](#file)

---

## `temp`

Creates an empty temp file in the system temp dir and returns its absolute path. Default prefix is `utl`.

```php
File::temp();              // e.g. '/tmp/utlAB1cD2'
File::temp('report-');     // e.g. '/tmp/report-XyZ123'
```

> The previous name `tempFile` remains as a `@deprecated` alias since 1.14.0 and will be removed in 2.0.0.

[↑ Back to top](#file)

---

## `copy` / `move`

`move` is an atomic rename when source and target sit on the same filesystem.

```php
File::copy('/tmp/source.txt', '/tmp/copy.txt');
File::move('/tmp/copy.txt',   '/tmp/renamed.txt');
```

[↑ Back to top](#file)
