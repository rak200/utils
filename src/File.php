<?php

declare(strict_types=1);

namespace Rak200\Utils;

use Generator;
use RuntimeException;
use function array_values, basename, copy, dirname, fclose, fgets, file_exists, file_get_contents,
    file_put_contents, filesize, finfo_close, finfo_file, finfo_open, fopen, glob, is_dir, is_file,
    mkdir, pathinfo, rename, rtrim, sprintf, sys_get_temp_dir, tempnam, unlink;

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
            throw new RuntimeException(sprintf('File not found: %s', $path));
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Cannot read file: %s', $path));
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
            throw new RuntimeException(sprintf('Cannot write file: %s', $path));
        }
    }

    /**
     * Appends $content to the file at $path, creating the file when missing.
     *
     * @throws RuntimeException When the file cannot be appended to.
     */
    public static function append(string $path, string $content): void {
        if (file_put_contents($path, $content, FILE_APPEND) === false) {
            throw new RuntimeException(sprintf('Cannot append to file: %s', $path));
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
            throw new RuntimeException(sprintf('Cannot delete file: %s', $path));
        }
    }

    /**
     * Returns the extension of $path (without the leading dot) or '' when none.
     */
    public static function extension(string $path): string {
        return pathinfo($path, PATHINFO_EXTENSION);
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
     * Returns the MIME type of $path (e.g. "image/png"), detected via fileinfo.
     *
     * @throws RuntimeException When the file is missing or the type cannot be determined.
     */
    public static function mimeType(string $path): string {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('File not found: %s', $path));
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Cannot open fileinfo database.');
        }
        $type = finfo_file($finfo, $path);
        finfo_close($finfo);
        if ($type === false) {
            throw new RuntimeException(sprintf('Cannot determine mime type: %s', $path));
        }
        return $type;
    }

    /**
     * Returns the size of the file at $path in bytes.
     *
     * @throws RuntimeException When the file is missing or its size cannot be read.
     */
    public static function size(string $path): int {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('File not found: %s', $path));
        }
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException(sprintf('Cannot determine file size: %s', $path));
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
            throw new RuntimeException(sprintf('File not found: %s', $path));
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Cannot open file: %s', $path));
        }
        try {
            while (($line = fgets($handle)) !== false) {
                yield rtrim($line, "\r\n");
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
    public static function tempFile(?string $prefix = null): string {
        $path = tempnam(sys_get_temp_dir(), $prefix ?? 'utl');
        if ($path === false) {
            throw new RuntimeException('Cannot create temp file.');
        }
        return $path;
    }

    /**
     * Copies $source to $target, overwriting an existing $target.
     *
     * @throws RuntimeException When $source is missing or the copy fails.
     */
    public static function copy(string $source, string $target): void {
        if (!is_file($source)) {
            throw new RuntimeException(sprintf('Source file not found: %s', $source));
        }
        if (!copy($source, $target)) {
            throw new RuntimeException(sprintf('Cannot copy %s to %s.', $source, $target));
        }
    }

    /**
     * Moves $source to $target (atomic rename when on the same filesystem).
     *
     * @throws RuntimeException When $source is missing or the move fails.
     */
    public static function move(string $source, string $target): void {
        if (!is_file($source)) {
            throw new RuntimeException(sprintf('Source file not found: %s', $source));
        }
        if (!rename($source, $target)) {
            throw new RuntimeException(sprintf('Cannot move %s to %s.', $source, $target));
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
            throw new RuntimeException(sprintf('Cannot create directory: %s', $path));
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
            throw new RuntimeException(sprintf('Directory not found: %s', $dir));
        }
        $result = glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $pattern);
        if ($result === false) {
            throw new RuntimeException(sprintf('Cannot list directory: %s', $dir));
        }
        return $result;
    }
}
