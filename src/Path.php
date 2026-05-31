<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;
use function array_pop, array_slice, count, ctype_alpha, implode, min,
    preg_match, sprintf, strlen, strrpos, substr;

/**
 * Logical path manipulation — pure string operations that never touch the disk.
 *
 * Every method accepts forward (`/`) or backslash (`\`) separators on input and
 * always emits forward slashes, so output is stable across platforms. Windows
 * drive prefixes (e.g. `C:`) and UNC roots (`//server/share`) are preserved.
 * For filesystem access (read/write/exists/...) use {@see File} instead.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Path {
    private function __construct() {}

    /**
     * Returns true when $path is absolute: starts with `/` or `\` (POSIX / UNC),
     * or with a Windows drive letter (e.g. `C:`, `c:/users`).
     */
    public static function isAbsolute(string $path): bool {
        if ($path === '') {
            return false;
        }
        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }
        return strlen($path) >= 2 && $path[1] === ':' && ctype_alpha($path[0]);
    }

    /**
     * Joins $parts with `/` separators and normalises the result. Empty parts
     * are skipped; a leading `/` on a non-first part is treated as a separator
     * (not as an absolute reset). Only the first part decides whether the
     * result is absolute.
     */
    public static function join(string ...$parts): string {
        $segments = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $segments[] = Str::replace($part, '\\', '/');
        }
        if ($segments === []) {
            return '';
        }
        $joined = $segments[0];
        for ($i = 1, $n = count($segments); $i < $n; $i++) {
            $joined = Str::trimEnd($joined, '/') . '/' . Str::trimStart($segments[$i], '/');
        }
        return self::normalize($joined);
    }

    /**
     * Collapses redundant separators, resolves `.` and `..` segments, and
     * converts every `\` to `/`. An absolute path that walks past its root
     * stays at the root; a relative path may keep leading `..` segments.
     */
    public static function normalize(string $path): string {
        if ($path === '') {
            return '';
        }
        $unified = Str::replace($path, '\\', '/');

        $drive = '';
        if (preg_match('#^([a-zA-Z]):#', $unified, $matches) === 1) {
            $drive = Str::upper($matches[1]) . ':';
            $unified = substr($unified, 2);
        }

        $isAbsolute = $unified !== '' && $unified[0] === '/';
        $segments = Str::split($unified, '/');
        $stack = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($stack !== [] && Arr::last($stack) !== '..') {
                    array_pop($stack);
                } elseif (!$isAbsolute && $drive === '') {
                    $stack[] = '..';
                }
                continue;
            }
            $stack[] = $segment;
        }

        $joined = implode('/', $stack);
        if ($drive !== '') {
            return $drive . ($isAbsolute || $joined !== '' ? '/' : '') . $joined;
        }
        if ($isAbsolute) {
            return '/' . $joined;
        }
        return $joined === '' ? '.' : $joined;
    }

    /**
     * Returns the path that, when resolved against $from, reaches $to. Both
     * paths must be either both absolute or both relative; on Windows, the
     * drive letters must match.
     *
     * @throws RuntimeException When $from and $to cannot be related (mismatched
     *                          anchors or drives).
     */
    public static function relative(string $from, string $to): string {
        $fromNorm = self::normalize($from);
        $toNorm = self::normalize($to);
        if (self::isAbsolute($fromNorm) !== self::isAbsolute($toNorm)) {
            throw new RuntimeException('Cannot compute relative path between absolute and relative paths.');
        }
        $fromDrive = self::driveOf($fromNorm);
        $toDrive = self::driveOf($toNorm);
        if ($fromDrive !== $toDrive) {
            throw new RuntimeException(sprintf('Cannot compute relative path across drives %s and %s.', $fromDrive ?: '(none)', $toDrive ?: '(none)'));
        }

        $fromBody = $fromDrive !== '' ? substr($fromNorm, strlen($fromDrive)) : $fromNorm;
        $toBody = $toDrive !== '' ? substr($toNorm, strlen($toDrive)) : $toNorm;

        $fromParts = self::splitBody($fromBody);
        $toParts = self::splitBody($toBody);

        $common = 0;
        $max = min(count($fromParts), count($toParts));
        while ($common < $max && $fromParts[$common] === $toParts[$common]) {
            $common++;
        }

        $up = count($fromParts) - $common;
        $down = array_slice($toParts, $common);
        $result = [];
        for ($i = 0; $i < $up; $i++) {
            $result[] = '..';
        }
        foreach ($down as $segment) {
            $result[] = $segment;
        }
        return $result === [] ? '.' : implode('/', $result);
    }

    /**
     * Returns the trailing name component of $path (the part after the last
     * separator), optionally stripping the given $suffix.
     */
    public static function basename(string $path, string $suffix = ''): string {
        $unified = Str::replace($path, '\\', '/');
        $unified = Str::trimEnd($unified, '/');
        if ($unified === '') {
            return '';
        }
        $pos = strrpos($unified, '/');
        $base = $pos === false ? $unified : substr($unified, $pos + 1);
        if ($suffix !== '' && $suffix !== $base) {
            $suffixLen = strlen($suffix);
            if (strlen($base) > $suffixLen && substr($base, -$suffixLen) === $suffix) {
                $base = substr($base, 0, -$suffixLen);
            }
        }
        return $base;
    }

    /**
     * Returns the parent-directory portion of $path. For a bare basename
     * (no separator), returns `.`. For the root, returns the root itself.
     */
    public static function dirname(string $path): string {
        if ($path === '') {
            return '.';
        }
        $unified = Str::replace($path, '\\', '/');
        $drive = '';
        if (preg_match('#^([a-zA-Z]):#', $unified, $matches) === 1) {
            $drive = Str::upper($matches[1]) . ':';
            $unified = substr($unified, 2);
        }
        $trimmed = Str::trimEnd($unified, '/');
        $pos = strrpos($trimmed, '/');
        if ($pos === false) {
            return $drive !== '' ? ($unified !== '' && $unified[0] === '/' ? $drive . '/' : $drive) : '.';
        }
        if ($pos === 0) {
            return $drive . '/';
        }
        return $drive . substr($trimmed, 0, $pos);
    }

    /**
     * Returns the extension of $path (the substring after the last `.` in the
     * basename), without the leading dot. Returns `''` when the basename has
     * no `.` or starts with `.` (dotfile with no further dot).
     */
    public static function extension(string $path): string {
        $base = self::basename($path);
        if ($base === '' || $base === '.' || $base === '..') {
            return '';
        }
        $pos = strrpos($base, '.');
        if ($pos === false || $pos === 0) {
            return '';
        }
        return substr($base, $pos + 1);
    }

    /**
     * Returns the basename of $path with its extension removed (or the bare
     * basename when there is no extension).
     */
    public static function filename(string $path): string {
        $base = self::basename($path);
        if ($base === '' || $base === '.' || $base === '..') {
            return $base;
        }
        $pos = strrpos($base, '.');
        if ($pos === false || $pos === 0) {
            return $base;
        }
        return substr($base, 0, $pos);
    }

    /**
     * Extracts the Windows drive prefix (e.g. `C:`) from $path, or returns `''`.
     */
    private static function driveOf(string $path): string {
        if (strlen($path) >= 2 && $path[1] === ':' && ctype_alpha($path[0])) {
            return Str::upper($path[0]) . ':';
        }
        return '';
    }

    /**
     * Splits the body of a normalised path (no drive prefix) into non-empty
     * segments, ignoring a leading `/`.
     *
     * @return list<string>
     */
    private static function splitBody(string $body): array {
        if ($body === '' || $body === '/' || $body === '.') {
            return [];
        }
        $body = Str::trimStart($body, '/');
        if (!Str::contains($body, '/')) {
            return [$body];
        }
        $parts = [];
        foreach (Str::split($body, '/') as $segment) {
            if ($segment !== '') {
                $parts[] = $segment;
            }
        }
        return $parts;
    }
}
