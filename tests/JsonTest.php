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

    public function testIs(): void {
        $this->assertTrue(Json::is('{"a":1}'));
        $this->assertTrue(Json::is('null'));
        $this->assertTrue(Json::is('42'));
        $this->assertTrue(Json::is('"hello"'));
        $this->assertFalse(Json::is('{invalid}'));
        $this->assertFalse(Json::is(''));
    }

    public function testIsRejectsNonStrings(): void {
        $this->assertFalse(Json::is(null));
        $this->assertFalse(Json::is(42));
        $this->assertFalse(Json::is(['a' => 1]));
        $this->assertFalse(Json::is(new \stdClass()));
    }

    public function testIsValidIsDeprecatedAliasOfIs(): void {
        $this->assertSame(Json::is('{"a":1}'), Json::isValid('{"a":1}'));
        $this->assertSame(Json::is('null'), Json::isValid('null'));
        $this->assertSame(Json::is('{invalid}'), Json::isValid('{invalid}'));
        $this->assertSame(Json::is(''), Json::isValid(''));
    }
}
