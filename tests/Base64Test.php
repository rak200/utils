<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Base64;
use RuntimeException;

final class Base64Test extends TestCase {
    public function testEncodeDecodeRoundTrip(): void {
        $value = "Hello, World!\x00\x01\x02";
        $this->assertSame($value, Base64::decode(Base64::encode($value)));
    }

    public function testEncodeProducesStandardBase64(): void {
        $this->assertSame('aGVsbG8=', Base64::encode('hello'));
    }

    public function testDecodeRejectsInvalid(): void {
        $this->expectException(RuntimeException::class);
        Base64::decode('!!!not-base64!!!');
    }

    public function testEncodeUrlReplacesAndStripsPadding(): void {
        $bytes = "\xfa\xfb\xfc\xfd";
        $standard = base64_encode($bytes);
        $url = Base64::encodeUrl($bytes);
        $this->assertSame(rtrim(strtr($standard, '+/', '-_'), '='), $url);
        $this->assertStringNotContainsString('=', $url);
        $this->assertStringNotContainsString('+', $url);
        $this->assertStringNotContainsString('/', $url);
    }

    public function testEncodeDecodeUrlRoundTrip(): void {
        $value = "any \xff bytes \x00 here";
        $this->assertSame($value, Base64::decodeUrl(Base64::encodeUrl($value)));
    }

    public function testDecodeUrlRejectsInvalid(): void {
        $this->expectException(RuntimeException::class);
        Base64::decodeUrl('!!!');
    }
}
