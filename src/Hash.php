<?php

declare(strict_types=1);

namespace Rak200\Utils;

/**
 * Hashing helpers (digests, HMAC, password hashing & verification).
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class Hash {
    private function __construct() {}

    /**
     * Returns the MD5 digest of $value as a lowercase hex string. Not suitable
     * for security-sensitive uses.
     */
    public static function md5(string $value): string {
        return md5($value);
    }

    /**
     * Returns the SHA-1 digest of $value as a lowercase hex string. Not suitable
     * for security-sensitive uses.
     */
    public static function sha1(string $value): string {
        return sha1($value);
    }

    /**
     * Returns the SHA-256 digest of $value as a lowercase hex string.
     */
    public static function sha256(string $value): string {
        return hash('sha256', $value);
    }

    /**
     * Returns the SHA-512 digest of $value as a lowercase hex string.
     */
    public static function sha512(string $value): string {
        return hash('sha512', $value);
    }

    /**
     * Returns the CRC32 checksum of $value as an unsigned 32-bit integer.
     */
    public static function crc32(string $value): int {
        return crc32($value);
    }

    /**
     * Returns the HMAC of $data using $algorithm (e.g. 'sha256') and $key,
     * as a lowercase hex string.
     */
    public static function hmac(string $algorithm, string $data, string $key): string {
        return hash_hmac($algorithm, $data, $key);
    }

    /**
     * Compares two strings in constant time, preventing timing attacks on
     * digest/secret comparisons.
     */
    public static function equals(string $a, string $b): bool {
        return hash_equals($a, $b);
    }

    /**
     * Returns a password hash using PHP's current PASSWORD_DEFAULT algorithm.
     */
    public static function password(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Returns true when $password matches the hash produced by {@see password()}.
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
