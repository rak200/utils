<?php

declare(strict_types=1);

namespace Rak200\Utils;

final class Hash {
    private function __construct() {}

    public static function md5(string $value): string {
        return md5($value);
    }

    public static function sha1(string $value): string {
        return sha1($value);
    }

    public static function sha256(string $value): string {
        return hash('sha256', $value);
    }

    public static function sha512(string $value): string {
        return hash('sha512', $value);
    }

    public static function crc32(string $value): int {
        return crc32($value);
    }

    public static function hmac(string $algorithm, string $data, string $key): string {
        return hash_hmac($algorithm, $data, $key);
    }

    public static function equals(string $a, string $b): bool {
        return hash_equals($a, $b);
    }

    public static function password(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
