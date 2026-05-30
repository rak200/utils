<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable {
        yield 'object'       => ['{"a":1}', true];
        yield 'null literal' => ['null', true];
        yield 'number'       => ['42', true];
        yield 'string'       => ['"hello"', true];
        yield 'malformed'    => ['{invalid}', false];
        yield 'empty'        => ['', false];
        yield 'php null'     => [null, false];
        yield 'php int'      => [42, false];
        yield 'php array'    => [['a' => 1], false];
        yield 'php object'   => [new \stdClass(), false];
    }

    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void {
        $this->assertSame($expected, Json::is($value));
    }

    #[DataProvider('isProvider')]
    public function testIsValidIsDeprecatedAliasOfIs(mixed $value, bool $expected): void {
        $this->assertSame(Json::is($value), Json::isValid($value));
    }
}
