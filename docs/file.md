# File

[← Reference](README.md)

Filesystem helpers — every operation throws on failure instead of returning the silent `false` of the underlying PHP functions.

```php
use Rak200\Utils\File;
```

## Contents

- [`read` / `write` / `append`](#read--write--append)
- [`exists`](#exists)
- [`isFile` / `isDir`](#isfile--isdir)
- [`delete`](#delete)
- [`mkdir`](#mkdir)
- [`list`](#list)
- [`extension` / `basename` / `dirname`](#extension--basename--dirname)
- [`mimeType`](#mimetype)
- [`size`](#size)
- [`lines`](#lines)
- [`tempFile`](#tempfile)
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

## `extension` / `basename` / `dirname`

Path-component helpers.

```php
File::extension('/var/log/app.log');             // 'log'
File::extension('README');                       // ''
File::basename('/var/log/app.log');              // 'app.log'
File::basename('/var/log/app.log', '.log');      // 'app'
File::dirname('/var/log/app.log');               // '/var/log'
```

[↑ Back to top](#file)

---

## `mimeType`

Detected via `fileinfo`.

```php
File::mimeType('/path/to/photo.png');     // 'image/png'
File::mimeType('/path/to/script.sh');     // 'text/x-shellscript'
```

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

## `tempFile`

Creates an empty temp file in the system temp dir and returns its absolute path. Default prefix is `utl`.

```php
File::tempFile();              // e.g. '/tmp/utlAB1cD2'
File::tempFile('report-');     // e.g. '/tmp/report-XyZ123'
```

[↑ Back to top](#file)

---

## `copy` / `move`

`move` is an atomic rename when source and target sit on the same filesystem.

```php
File::copy('/tmp/source.txt', '/tmp/copy.txt');
File::move('/tmp/copy.txt',   '/tmp/renamed.txt');
```

[↑ Back to top](#file)
