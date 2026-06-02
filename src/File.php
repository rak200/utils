<?php

declare(strict_types=1);

namespace Rak200\Utils;

use Generator;
use RuntimeException;
use function basename, copy, dirname, fclose, fgetcsv, fgets, file_exists, file_get_contents,
    file_put_contents, filesize, finfo_file, finfo_open, fopen, fputcsv, glob, is_dir, is_file,
    mkdir, pathinfo, realpath, rename, sys_get_temp_dir, tempnam, touch, unlink;

/**
 * Filesystem helpers — every operation throws on failure instead of returning
 * the silent `false` of the underlying PHP functions.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class File {
    private function __construct() {}

    /**
     * Returns the full contents of the file at $path.
     *
     * @throws RuntimeException When the file does not exist or cannot be read.
     */
    public static function read(string $path): string {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: $path");
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Cannot read file: $path");
        }
        return $contents;
    }

    /**
     * Writes $content to $path, creating or overwriting as needed.
     *
     * @throws RuntimeException When the file cannot be written.
     */
    public static function write(string $path, string $content): void {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Cannot write file: $path");
        }
    }

    /**
     * Appends $content to the file at $path, creating the file when missing.
     *
     * @throws RuntimeException When the file cannot be appended to.
     */
    public static function append(string $path, string $content): void {
        if (file_put_contents($path, $content, FILE_APPEND) === false) {
            throw new RuntimeException("Cannot append to file: $path");
        }
    }

    /**
     * Sets the modification (and access) time of $path to $time, defaulting to
     * now. Creates an empty file when $path does not exist.
     *
     * @param int|null $time Unix timestamp; null uses the current time.
     * @throws RuntimeException When the file cannot be touched.
     */
    public static function touch(string $path, ?int $time = null): void {
        $ok = $time === null ? touch($path) : touch($path, $time);
        if (!$ok) {
            throw new RuntimeException("Cannot touch file: $path");
        }
    }

    /**
     * Returns true when $path exists (file, directory, or symlink).
     */
    public static function exists(string $path): bool {
        return file_exists($path);
    }

    /**
     * Returns true when $path is an existing regular file.
     */
    public static function isFile(string $path): bool {
        return is_file($path);
    }

    /**
     * Returns true when $path is an existing directory.
     */
    public static function isDir(string $path): bool {
        return is_dir($path);
    }

    /**
     * @deprecated since 1.2.0, use {@see self::isDir()} instead. Will be removed in 2.0.0.
     */
    public static function isDirectory(string $path): bool {
        return self::isDir($path);
    }

    /**
     * Deletes the file at $path. No-op when it does not exist.
     *
     * @throws RuntimeException When the file exists but cannot be deleted.
     */
    public static function delete(string $path): void {
        if (!file_exists($path)) {
            return;
        }
        if (!unlink($path)) {
            throw new RuntimeException("Cannot delete file: $path");
        }
    }

    /**
     * Returns the extension of $path (without the leading dot) or '' when none.
     */
    public static function ext(string $path): string {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * @deprecated since 1.14.0, use {@see self::ext()} instead. Will be removed in 2.0.0.
     */
    public static function extension(string $path): string {
        return self::ext($path);
    }

    /**
     * Returns the trailing name component of $path, optionally stripping the
     * given $suffix.
     */
    public static function basename(string $path, string $suffix = ''): string {
        return basename($path, $suffix);
    }

    /**
     * Returns the parent-directory portion of $path.
     */
    public static function dirname(string $path): string {
        return dirname($path);
    }

    /**
     * Returns the canonical absolute path of $path, resolving symlinks and
     * `.`/`..` segments. The path must exist on disk.
     *
     * @throws RuntimeException When $path does not exist or cannot be resolved.
     */
    public static function realpath(string $path): string {
        $resolved = realpath($path);
        if ($resolved === false) {
            throw new RuntimeException("Cannot resolve path: $path");
        }
        return $resolved;
    }

    /**
     * Returns the MIME type of $path (e.g. "image/png"), detected via fileinfo.
     *
     * @throws RuntimeException When the file is missing or the type cannot be determined.
     */
    public static function mime(string $path): string {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: $path");
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Cannot open fileinfo database.');
        }
        $type = finfo_file($finfo, $path);
        if ($type === false) {
            throw new RuntimeException("Cannot determine mime type: $path");
        }
        return $type;
    }

    /**
     * @deprecated since 1.14.0, use {@see self::mime()} instead. Will be removed in 2.0.0.
     */
    public static function mimeType(string $path): string {
        return self::mime($path);
    }

    /**
     * Returns the size of the file at $path in bytes.
     *
     * @throws RuntimeException When the file is missing or its size cannot be read.
     */
    public static function size(string $path): int {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: $path");
        }
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException("Cannot determine file size: $path");
        }
        return $size;
    }

    /**
     * Lazily yields each line of the file at $path, stripped of trailing CR/LF.
     * The file handle is closed automatically when iteration ends or aborts.
     *
     * @throws RuntimeException When the file is missing or cannot be opened.
     * @return Generator<int, string>
     */
    public static function lines(string $path): Generator {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: $path");
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot open file: $path");
        }
        try {
            while (($line = fgets($handle)) !== false) {
                yield Str::trimEnd($line, "\r\n");
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Creates a new empty temporary file in the system temp dir and returns its
     * absolute path. $prefix defaults to "utl".
     *
     * @throws RuntimeException When the temp file cannot be created.
     */
    public static function temp(?string $prefix = null): string {
        $path = tempnam(sys_get_temp_dir(), $prefix ?? 'utl');
        if ($path === false) {
            throw new RuntimeException('Cannot create temp file.');
        }
        return $path;
    }

    /**
     * @deprecated since 1.14.0, use {@see self::temp()} instead. Will be removed in 2.0.0.
     */
    public static function tempFile(?string $prefix = null): string {
        return self::temp($prefix);
    }

    /**
     * Copies $source to $target, overwriting an existing $target.
     *
     * @throws RuntimeException When $source is missing or the copy fails.
     */
    public static function copy(string $source, string $target): void {
        if (!is_file($source)) {
            throw new RuntimeException("Source file not found: $source");
        }
        if (!copy($source, $target)) {
            throw new RuntimeException("Cannot copy $source to $target.");
        }
    }

    /**
     * Moves $source to $target (atomic rename when on the same filesystem).
     *
     * @throws RuntimeException When $source is missing or the move fails.
     */
    public static function move(string $source, string $target): void {
        if (!is_file($source)) {
            throw new RuntimeException("Source file not found: $source");
        }
        if (!rename($source, $target)) {
            throw new RuntimeException("Cannot move $source to $target.");
        }
    }

    /**
     * Creates the directory at $path. Recursive by default. No-op when the
     * directory already exists.
     *
     * @throws RuntimeException When the directory cannot be created.
     */
    public static function mkdir(string $path, bool $recursive = true, int $mode = 0777): void {
        if (is_dir($path)) {
            return;
        }
        if (!mkdir($path, $mode, $recursive) && !is_dir($path)) {
            throw new RuntimeException("Cannot create directory: $path");
        }
    }

    /**
     * Returns the entries in $dir matching the glob $pattern (default '*' —
     * everything except dotfiles), as a 0-indexed list of absolute paths.
     *
     * @throws RuntimeException When $dir is not a directory or cannot be listed.
     * @return list<string>
     */
    public static function list(string $dir, string $pattern = '*'): array {
        if (!is_dir($dir)) {
            throw new RuntimeException("Directory not found: $dir");
        }
        $result = glob(Str::trimEnd($dir, '/\\') . DIRECTORY_SEPARATOR . $pattern);
        if ($result === false) {
            throw new RuntimeException("Cannot list directory: $dir");
        }
        return $result;
    }

    /**
     * Reads the CSV file at $path into a list of rows, each a list of field
     * strings. Fully blank lines are skipped. $escape defaults to '' (no escape
     * character), matching the modern CSV behaviour PHP 8.4 recommends.
     *
     * @throws RuntimeException When the file is missing or cannot be opened.
     * @return list<list<string|null>>
     */
    public static function readCsv(
        string $path,
        string $separator = ',',
        string $enclosure = '"',
        string $escape = '',
    ): array {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: $path");
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot open file: $path");
        }
        try {
            $rows = [];
            while (($row = fgetcsv($handle, null, $separator, $enclosure, $escape)) !== false) {
                if ($row === [null]) {
                    continue;
                }
                $rows[] = $row;
            }
            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Writes $rows to the CSV file at $path (creating or overwriting it). Each
     * row is an array of fields cast to string. $escape defaults to '' (no escape
     * character), matching the modern CSV behaviour PHP 8.4 recommends.
     *
     * @param iterable<array<array-key, string|int|float|bool|null|\Stringable>> $rows
     * @throws RuntimeException When the file cannot be opened or a row cannot be written.
     */
    public static function writeCsv(
        string $path,
        iterable $rows,
        string $separator = ',',
        string $enclosure = '"',
        string $escape = '',
    ): void {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Cannot open file for writing: $path");
        }
        try {
            foreach ($rows as $row) {
                if (fputcsv($handle, $row, $separator, $enclosure, $escape, "\n") === false) {
                    throw new RuntimeException("Cannot write CSV row to: $path");
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
