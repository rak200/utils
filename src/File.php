<?php

declare(strict_types=1);

namespace Rak200\Utils;

use Generator;
use RuntimeException;

final class File {
    private function __construct() {}

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

    public static function write(string $path, string $content): void {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException(sprintf('Cannot write file: %s', $path));
        }
    }

    public static function append(string $path, string $content): void {
        if (file_put_contents($path, $content, FILE_APPEND) === false) {
            throw new RuntimeException(sprintf('Cannot append to file: %s', $path));
        }
    }

    public static function exists(string $path): bool {
        return file_exists($path);
    }

    public static function delete(string $path): void {
        if (!file_exists($path)) {
            return;
        }
        if (!unlink($path)) {
            throw new RuntimeException(sprintf('Cannot delete file: %s', $path));
        }
    }

    public static function extension(string $path): string {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public static function basename(string $path, string $suffix = ''): string {
        return basename($path, $suffix);
    }

    public static function dirname(string $path): string {
        return dirname($path);
    }

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

    public static function tempFile(?string $prefix = null): string {
        $path = tempnam(sys_get_temp_dir(), $prefix ?? 'utl');
        if ($path === false) {
            throw new RuntimeException('Cannot create temp file.');
        }
        return $path;
    }

    public static function copy(string $source, string $target): void {
        if (!is_file($source)) {
            throw new RuntimeException(sprintf('Source file not found: %s', $source));
        }
        if (!copy($source, $target)) {
            throw new RuntimeException(sprintf('Cannot copy %s to %s.', $source, $target));
        }
    }

    public static function move(string $source, string $target): void {
        if (!is_file($source)) {
            throw new RuntimeException(sprintf('Source file not found: %s', $source));
        }
        if (!rename($source, $target)) {
            throw new RuntimeException(sprintf('Cannot move %s to %s.', $source, $target));
        }
    }
}
