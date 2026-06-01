<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Hex;
use RuntimeException;

final class HexTest extends TestCase {
    public function testIsAcceptsValidHex(): void {
        $this->assertTrue(Hex::is(''));
        $this->assertTrue(Hex::is('00'));
        $this->assertTrue(Hex::is('deadbeef'));
        $this->assertTrue(Hex::is('DEADBEEF'));
        $this->assertTrue(Hex::is('DeAdBeEf'));
        $this->assertTrue(Hex::is(Hex::encode("Hello, World!\x00\x01\x02")));
    }

    public function testIsRejectsBadInput(): void {
        $this->assertFalse(Hex::is('f'));       // odd length
        $this->assertFalse(Hex::is('abc'));     // odd length
        $this->assertFalse(Hex::is('gg'));      // non-hex char
        $this->assertFalse(Hex::is('0x1f'));    // non-hex char
        $this->assertFalse(Hex::is('ée'));      // multibyte, not hex
    }

    public function testEncodeProducesLowercaseHex(): void {
        $this->assertSame('68656c6c6f', Hex::encode('hello'));
        $this->assertSame('', Hex::encode(''));
        $this->assertSame('00010203', Hex::encode("\x00\x01\x02\x03"));
    }

    public function testEncodeDecodeRoundTrip(): void {
        $value = "any \xff bytes \x00 here";
        $this->assertSame($value, Hex::decode(Hex::encode($value)));
    }

    public function testDecodeAcceptsUppercase(): void {
        $this->assertSame("\xde\xad\xbe\xef", Hex::decode('DEADBEEF'));
        $this->assertSame("\xde\xad\xbe\xef", Hex::decode('deadbeef'));
    }

    public function testDecodeEmptyString(): void {
        $this->assertSame('', Hex::decode(''));
    }

    public function testDecodeRejectsOddLength(): void {
        $this->expectException(RuntimeException::class);
        Hex::decode('abc');
    }

    public function testDecodeRejectsNonHex(): void {
        $this->expectException(RuntimeException::class);
        Hex::decode('zz');
    }

    public function testToBytes(): void {
        $this->assertSame([], Hex::toBytes(''));
        $this->assertSame([0, 1, 2, 3], Hex::toBytes('00010203'));
        $this->assertSame([222, 173, 190, 239], Hex::toBytes('deadbeef'));
        $this->assertSame([222, 173, 190, 239], Hex::toBytes('DEADBEEF'));
        $this->assertSame([255], Hex::toBytes('ff'));
    }

    public function testToBytesRejectsInvalid(): void {
        $this->expectException(RuntimeException::class);
        Hex::toBytes('abc');
    }

    public function testFromBytes(): void {
        $this->assertSame('', Hex::fromBytes([]));
        $this->assertSame('00010203', Hex::fromBytes([0, 1, 2, 3]));
        $this->assertSame('deadbeef', Hex::fromBytes([222, 173, 190, 239]));
        $this->assertSame('0fff', Hex::fromBytes([15, 255]));
    }

    public function testFromBytesRejectsOutOfRange(): void {
        $this->expectException(RuntimeException::class);
        Hex::fromBytes([0, 256]);
    }

    public function testFromBytesRejectsNegative(): void {
        $this->expectException(RuntimeException::class);
        Hex::fromBytes([-1]);
    }

    public function testBytesRoundTrip(): void {
        $value = "any \xff bytes \x00 here";
        $bytes = Hex::toBytes(Hex::encode($value));
        $this->assertSame($value, Hex::decode(Hex::fromBytes($bytes)));
    }
}
