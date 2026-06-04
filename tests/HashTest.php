<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Hash;

/**
 * @internal
 *
 * @coversNothing
 */
final class HashTest extends TestCase
{
    public function testMd5(): void
    {
        $this->assertSame('5d41402abc4b2a76b9719d911017c592', Hash::md5('hello'));
    }

    public function testSha1(): void
    {
        $this->assertSame('aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d', Hash::sha1('hello'));
    }

    public function testSha256(): void
    {
        $this->assertSame(
            '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824',
            Hash::sha256('hello'),
        );
    }

    public function testSha512Length(): void
    {
        $this->assertSame(128, strlen(Hash::sha512('hello')));
    }

    public function testCrc32(): void
    {
        $this->assertSame(crc32('hello'), Hash::crc32('hello'));
    }

    public function testHmac(): void
    {
        $expected = hash_hmac('sha256', 'data', 'secret');
        $this->assertSame($expected, Hash::hmac('sha256', 'data', 'secret'));
    }

    public function testEqualsConstantTime(): void
    {
        $this->assertTrue(Hash::equals('abc', 'abc'));
        $this->assertFalse(Hash::equals('abc', 'abd'));
        $this->assertFalse(Hash::equals('abc', 'abcd'));
    }

    public function testPasswordAndVerify(): void
    {
        $hash = Hash::password('s3cret');
        $this->assertTrue(Hash::verify('s3cret', $hash));
        $this->assertFalse(Hash::verify('wrong', $hash));
    }
}
