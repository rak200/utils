# Path

[← Reference](README.md)

Logical path manipulation — pure string operations that never touch the disk. Forward (`/`) or backslash (`\`) separators are accepted on input; output always uses `/`. For filesystem access, see [File](file.md).

```php
use Rak200\Utils\Path;
```

## Contents

- [`join`](#join)
- [`normalize`](#normalize)
- [`relative`](#relative)
- [`isAbsolute`](#isabsolute)
- [`basename`](#basename)
- [`dirname`](#dirname)
- [`ext`](#ext)
- [`filename`](#filename)

---

## `join`

Concatenates segments with `/`, then normalises the result. Empty parts are skipped; a leading `/` on a non-first part is treated as a separator (not as an absolute reset — only the first part decides whether the result is absolute).

```php
Path::join('a', 'b', 'c');               // 'a/b/c'
Path::join('a/', '/b/', '/c');           // 'a/b/c'
Path::join('/a', 'b');                   // '/a/b'
Path::join('/a', '/x', 'y');             // '/a/x/y'
Path::join('a\\b', 'c');                 // 'a/b/c'
```

[↑ Back to top](#path)

---

## `normalize`

Collapses redundant separators, resolves `.` and `..`, and converts `\` to `/`. Absolute paths stop at the root when walking past it; relative paths may keep leading `..` segments.

```php
Path::normalize('//a///b//c');           // '/a/b/c'
Path::normalize('/a/./b/../c');          // '/a/c'
Path::normalize('a/./b/../c');           // 'a/c'
Path::normalize('/../../..');            // '/'        (stops at root)
Path::normalize('../../x');              // '../../x'  (relative keeps ..)
Path::normalize('.');                    // '.'
Path::normalize('C:\\Users\\me');        // 'C:/Users/me'
```

[↑ Back to top](#path)

---

## `relative`

Returns the path that, when resolved against `$from`, reaches `$to`. Throws when one side is absolute and the other is relative, or when the Windows drives differ.

```php
Path::relative('/a/b/c', '/a/b/d/e');    // '../d/e'
Path::relative('/a/b/c', '/a/b');        // '..'
Path::relative('/a/b/c/d', '/a');        // '../../..'
Path::relative('/a/b', '/a/b');          // '.'
Path::relative('/a/b', '/a/b/c/d');      // 'c/d'
Path::relative('a/b/c', 'a/b/d');        // '../d'
```

[↑ Back to top](#path)

---

## `isAbsolute`

True for paths starting with `/`, `\`, or a Windows drive letter (`C:`, `c:/users`, `C:\Users`); false otherwise.

```php
Path::isAbsolute('/a/b');                // true
Path::isAbsolute('C:/Users');            // true
Path::isAbsolute('C:\\Users');           // true
Path::isAbsolute('\\\\server\\share');   // true
Path::isAbsolute('a/b');                 // false
Path::isAbsolute('./foo');               // false
Path::isAbsolute('');                    // false
```

[↑ Back to top](#path)

---

## `basename`

Returns the trailing name component, optionally stripping a fixed `$suffix` from the end.

```php
Path::basename('/a/b/file.txt');             // 'file.txt'
Path::basename('a/b/file.txt/');             // 'file.txt'
Path::basename('/a/file.txt', '.txt');       // 'file'
Path::basename('archive.tar.gz', '.gz');     // 'archive.tar'
Path::basename('/');                          // ''
```

[↑ Back to top](#path)

---

## `dirname`

Returns the parent-directory portion. A bare basename gives `.`; the root stays the root.

```php
Path::dirname('/a/b/file.txt');              // '/a/b'
Path::dirname('a/b/file.txt');               // 'a/b'
Path::dirname('file.txt');                   // '.'
Path::dirname('/file.txt');                  // '/'
Path::dirname('C:/Users/me');                // 'C:/Users'
```

[↑ Back to top](#path)

---

## `ext`

Returns the part of the basename after the last `.`, without the leading dot. Returns `''` for `README`, `.hidden`, or paths ending in `/`.

```php
Path::ext('/a/file.txt');              // 'txt'
Path::ext('archive.tar.gz');           // 'gz'
Path::ext('README');                   // ''
Path::ext('.hidden');                  // ''
```

[↑ Back to top](#path)

---

## `filename`

Returns the basename with its extension removed (or the bare basename when there is no extension).

```php
Path::filename('/a/file.txt');               // 'file'
Path::filename('archive.tar.gz');            // 'archive.tar'
Path::filename('README');                    // 'README'
Path::filename('.hidden');                   // '.hidden'
```

[↑ Back to top](#path)
