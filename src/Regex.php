<?php

declare(strict_types=1);

namespace Rak200\Utils;

use RuntimeException;

final class Regex {
    private function __construct() {}

    public static function matches(string $pattern, string $subject): bool {
        $result = preg_match($pattern, $subject);
        if ($result === false) {
            throw new RuntimeException(sprintf('Invalid regex pattern: %s', $pattern));
        }
        return $result === 1;
    }

    /**
     * @return array<int|string, string>
     */
    public static function match(string $pattern, string $subject): array {
        $result = self::matchOrNull($pattern, $subject);
        if ($result === null) {
            throw new RuntimeException(sprintf('No match for pattern %s.', $pattern));
        }
        return $result;
    }

    /**
     * @return array<int|string, string>|null
     */
    public static function matchOrNull(string $pattern, string $subject): ?array {
        $matches = [];
        $result = preg_match($pattern, $subject, $matches);
        if ($result === false) {
            throw new RuntimeException(sprintf('Invalid regex pattern: %s', $pattern));
        }
        return $result === 1 ? $matches : null;
    }

    /**
     * @return array<int|string, list<string>>
     */
    public static function matchAll(string $pattern, string $subject): array {
        $matches = [];
        $result = preg_match_all($pattern, $subject, $matches);
        if ($result === false) {
            throw new RuntimeException(sprintf('Invalid regex pattern: %s', $pattern));
        }
        return $matches;
    }

    public static function replace(string $pattern, string $replacement, string $subject): string {
        $result = preg_replace($pattern, $replacement, $subject);
        if ($result === null) {
            throw new RuntimeException(sprintf('Invalid regex pattern: %s', $pattern));
        }
        return $result;
    }

    public static function replaceCallback(string $pattern, callable $callback, string $subject): string {
        $result = preg_replace_callback($pattern, $callback, $subject);
        if ($result === null) {
            throw new RuntimeException(sprintf('Invalid regex pattern: %s', $pattern));
        }
        return $result;
    }

    /**
     * @return list<string>
     */
    public static function split(string $pattern, string $subject): array {
        $result = preg_split($pattern, $subject);
        if ($result === false) {
            throw new RuntimeException(sprintf('Invalid regex pattern: %s', $pattern));
        }
        return $result;
    }

    public static function quote(string $value, string $delimiter = '/'): string {
        return preg_quote($value, $delimiter);
    }
}
