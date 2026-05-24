# File

Filesystem helpers — every operation throws on failure instead of returning the silent `false` of the underlying PHP functions.

```php
use Rak200\Utils\File;
```

## Contents

- [`read` / `write` / `append`](#read--write--append)
- [`exists`](#exists)
- [`delete`](#delete)
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

---

## `exists`

True for any file, directory, or symlink at `$path`.

```php
File::exists('/tmp/greeting.txt');     // true
File::exists('/does/not/exist');       // false
```

---

## `delete`

No-op when the file does not exist; only throws when it exists and cannot be deleted.

```php
File::delete('/tmp/greeting.txt');     // removes the file
File::delete('/tmp/already-gone');     // no-op, no exception
```

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

---

## `mimeType`

Detected via `fileinfo`.

```php
File::mimeType('/path/to/photo.png');     // 'image/png'
File::mimeType('/path/to/script.sh');     // 'text/x-shellscript'
```

---

## `size`

Size in bytes.

```php
File::size('/path/to/photo.png');     // e.g. 245631
```

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

---

## `tempFile`

Creates an empty temp file in the system temp dir and returns its absolute path. Default prefix is `utl`.

```php
File::tempFile();              // e.g. '/tmp/utlAB1cD2'
File::tempFile('report-');     // e.g. '/tmp/report-XyZ123'
```

---

## `copy` / `move`

`move` is an atomic rename when source and target sit on the same filesystem.

```php
File::copy('/tmp/source.txt', '/tmp/copy.txt');
File::move('/tmp/copy.txt',   '/tmp/renamed.txt');
```
