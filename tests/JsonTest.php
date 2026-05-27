<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Json;

final class JsonTest extends TestCase {
    public function testEncode(): void {
        $this->assertSame('{"a":1}', Json::encode(['a' => 1]));
    }

    public function testEncodeThrowsOnInvalid(): void {
        $this->expectException(JsonException::class);
        Json::encode("\xB1\x31");
    }

    public function testDecodeAssocByDefault(): void {
        $this->assertSame(['a' => 1], Json::decode('{"a":1}'));
    }

    public function testDecodeAsObject(): void {
        $result = Json::decode('{"a":1}', false);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame(1, $result->a);
    }

    public function testDecodeThrowsOnInvalid(): void {
        $this->expectException(JsonException::class);
        Json::decode('{invalid}');
    }

    public function testIsValid(): void {
        $this->assertTrue(Json::isValid('{"a":1}'));
        $this->assertTrue(Json::isValid('null'));
        $this->assertFalse(Json::isValid('{invalid}'));
        $this->assertFalse(Json::isValid(''));
    }
}
