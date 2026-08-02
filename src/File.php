<?php

declare(strict_types=1);

namespace Rak200\Utils;

use Generator;
use Rak200\Utils\Exception\FilesystemException;
use Stringable;

use function basename;
use function copy;
use function dirname;
use function fclose;
use function fgetcsv;
use function fgets;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function finfo_file;
use function finfo_open;
use function fopen;
use function fputcsv;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function pathinfo;
use function realpath;
use function rename;
use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function unlink;

/**
 * Filesystem helpers — every operation throws on failure instead of returning
 * the silent `false` of the underlying PHP functions.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class File
{
    private function __construct() {}

    /**
     * Returns the full contents of the file at $path.
     *
     * @throws FilesystemException when the file does not exist or cannot be read
     */
    public static function read(string $path): string
    {
        if (!is_file($path)) {
            throw new FilesystemException("File not found: {$path}");
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new FilesystemException("Cannot read file: {$path}");
        }

        return $contents;
    }

    /**
     * Writes $content to $path, creating or overwriting as needed.
     *
     * @throws FilesystemException when the file cannot be written
     */
    public static function write(string $path, string $content): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new FilesystemException("Cannot write file: {$path}");
        }
    }

    /**
     * Appends $content to the file at $path, creating the file when missing.
     *
     * @throws FilesystemException when the file cannot be appended to
     */
    public static function append(string $path, string $content): void
    {
        if (file_put_contents($path, $content, FILE_APPEND) === false) {
            throw new FilesystemException("Cannot append to file: {$path}");
        }
    }

    /**
     * Sets the modification (and access) time of $path to $time, defaulting to
     * now. Creates an empty file when $path does not exist.
     *
     * @param null|int $time unix timestamp; null uses the current time
     *
     * @throws FilesystemException when the file cannot be touched
     */
    public static function touch(string $path, ?int $time = null): void
    {
        $ok = $time === null ? touch($path) : touch($path, $time);
        if (!$ok) {
            throw new FilesystemException("Cannot touch file: {$path}");
        }
    }

    /**
     * Returns true when $path exists (file, directory, or symlink).
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Returns true when $path is an existing regular file.
     */
    public static function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Returns true when $path is an existing directory.
     */
    public static function isDir(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Deletes the file at $path. No-op when it does not exist.
     *
     * @throws FilesystemException when the file exists but cannot be deleted
     */
    public static function delete(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (!unlink($path)) {
            throw new FilesystemException("Cannot delete file: {$path}");
        }
    }

    /**
     * Returns the extension of $path (without the leading dot) or '' when none.
     */
    public static function ext(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Returns the trailing name component of $path, optionally stripping the
     * given $suffix.
     */
    public static function basename(string $path, string $suffix = ''): string
    {
        return basename($path, $suffix);
    }

    /**
     * Returns the parent-directory portion of $path.
     */
    public static function dirname(string $path): string
    {
        return dirname($path);
    }

    /**
     * Returns the canonical absolute path of $path, resolving symlinks and
     * `.`/`..` segments. The path must exist on disk.
     *
     * @throws FilesystemException when $path does not exist or cannot be resolved
     */
    public static function realpath(string $path): string
    {
        $resolved = realpath($path);
        if ($resolved === false) {
            throw new FilesystemException("Cannot resolve path: {$path}");
        }

        return $resolved;
    }

    /**
     * Returns the MIME type of $path (e.g. "image/png"), detected via fileinfo.
     *
     * @throws FilesystemException when the file is missing or the type cannot be determined
     */
    public static function mime(string $path): string
    {
        if (!is_file($path)) {
            throw new FilesystemException("File not found: {$path}");
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new FilesystemException('Cannot open fileinfo database.');
        }
        $type = finfo_file($finfo, $path);
        if ($type === false) {
            throw new FilesystemException("Cannot determine mime type: {$path}");
        }

        return $type;
    }

    /**
     * Returns the size of the file at $path in bytes.
     *
     * @throws FilesystemException when the file is missing or its size cannot be read
     */
    public static function size(string $path): int
    {
        if (!is_file($path)) {
            throw new FilesystemException("File not found: {$path}");
        }
        $size = filesize($path);
        if ($size === false) {
            throw new FilesystemException("Cannot determine file size: {$path}");
        }

        return $size;
    }

    /**
     * Lazily yields each line of the file at $path, stripped of trailing CR/LF.
     * The file handle is closed automatically when iteration ends or aborts.
     *
     * @return Generator<int, string>
     *
     * @throws FilesystemException when the file is missing or cannot be opened
     */
    public static function lines(string $path): Generator
    {
        if (!is_file($path)) {
            throw new FilesystemException("File not found: {$path}");
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new FilesystemException("Cannot open file: {$path}");
        }

        // @infection-ignore-all: without the finally, the handle is still closed when its last reference drops;
        // the explicit close only makes the release deterministic on early generator abandonment
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
     * @throws FilesystemException when the temp file cannot be created
     */
    public static function temp(?string $prefix = null): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix ?? 'utl');
        if ($path === false) {
            throw new FilesystemException('Cannot create temp file.');
        }

        return $path;
    }

    /**
     * Copies $source to $target, overwriting an existing $target.
     *
     * @throws FilesystemException when $source is missing or the copy fails
     */
    public static function copy(string $source, string $target): void
    {
        if (!is_file($source)) {
            throw new FilesystemException("Source file not found: {$source}");
        }
        if (!copy($source, $target)) {
            throw new FilesystemException("Cannot copy {$source} to {$target}.");
        }
    }

    /**
     * Moves $source to $target (atomic rename when on the same filesystem).
     *
     * @throws FilesystemException when $source is missing or the move fails
     */
    public static function move(string $source, string $target): void
    {
        if (!is_file($source)) {
            throw new FilesystemException("Source file not found: {$source}");
        }
        if (!rename($source, $target)) {
            throw new FilesystemException("Cannot move {$source} to {$target}.");
        }
    }

    /**
     * Creates the directory at $path. Recursive by default. No-op when the
     * directory already exists.
     *
     * @throws FilesystemException when the directory cannot be created
     */
    public static function mkdir(string $path, bool $recursive = true, int $mode = /* @infection-ignore-all: the effective permissions are masked by umask and ignored on Windows — not portably assertable */ 0o777): void
    {
        if (is_dir($path)) {
            return;
        }
        // @infection-ignore-all: the is_dir half only differs when a concurrent process creates the directory
        // between the check above and this call — a race that cannot be arranged in a test
        if (!mkdir($path, $mode, $recursive) && !is_dir($path)) {
            throw new FilesystemException("Cannot create directory: {$path}");
        }
    }

    /**
     * Returns the entries in $dir matching the glob $pattern (default '*' —
     * everything except dotfiles), as a 0-indexed list of absolute paths.
     *
     * @return list<string>
     *
     * @throws FilesystemException when $dir is not a directory or cannot be listed
     */
    public static function list(string $dir, string $pattern = '*'): array
    {
        if (!is_dir($dir)) {
            throw new FilesystemException("Directory not found: {$dir}");
        }
        $result = glob(Str::trimEnd($dir, '/\\') . DIRECTORY_SEPARATOR . $pattern);
        if ($result === false) {
            throw new FilesystemException("Cannot list directory: {$dir}");
        }

        return $result;
    }

    /**
     * Reads the CSV file at $path into a list of rows, each a list of field
     * strings. Fully blank lines are skipped. $escape defaults to '' (no escape
     * character), matching the modern CSV behaviour PHP 8.4 recommends.
     *
     * @return list<list<null|string>>
     *
     * @throws FilesystemException when the file is missing or cannot be opened
     */
    public static function readCsv(
        string $path,
        string $separator = ',',
        string $enclosure = '"',
        string $escape = '',
    ): array {
        if (!is_file($path)) {
            throw new FilesystemException("File not found: {$path}");
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new FilesystemException("Cannot open file: {$path}");
        }

        // @infection-ignore-all: without the finally, the handle is still closed when its last reference drops;
        // the explicit close only makes the release deterministic
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
     * @param iterable<array<array-key, null|bool|float|int|string|Stringable>> $rows
     *
     * @throws FilesystemException when the file cannot be opened or a row cannot be written
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
            throw new FilesystemException("Cannot open file for writing: {$path}");
        }

        // @infection-ignore-all: without the finally, the handle is still closed when its last reference drops;
        // the explicit close only makes the release deterministic
        try {
            foreach ($rows as $row) {
                if (fputcsv($handle, $row, $separator, $enclosure, $escape, "\n") === false) {
                    throw new FilesystemException("Cannot write CSV row to: {$path}");
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
