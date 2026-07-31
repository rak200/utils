<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\MalformedJsonException;
use Rak200\Utils\Json;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class JsonTest extends TestCase
{
    public function testEncode(): void
    {
        $this->assertSame('{"a":1}', Json::encode(['a' => 1]));
    }

    public function testEncodeThrowsOnInvalid(): void
    {
        $this->expectException(MalformedJsonException::class);
        Json::encode("\xB1\x31");
    }

    public function testDecodeAssocByDefault(): void
    {
        $this->assertSame(['a' => 1], Json::decode('{"a":1}'));
    }

    public function testDecodeAsObject(): void
    {
        $result = Json::decode('{"a":1}', false);
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertSame(1, $result->a);
    }

    public function testDecodeThrowsOnInvalid(): void
    {
        $this->expectException(MalformedJsonException::class);
        Json::decode('{invalid}');
    }

    public function testEncodeWrapPreservesMessageCodeAndPrevious(): void
    {
        try {
            Json::encode("\xB1\x31");
            $this->fail('Json::encode should have thrown on invalid UTF-8.');
        } catch (MalformedJsonException $e) {
            $this->assertSame(JSON_ERROR_UTF8, $e->getCode());
            $previous = $e->getPrevious();
            $this->assertInstanceOf(JsonException::class, $previous);
            $this->assertNotInstanceOf(MalformedJsonException::class, $previous);
            $this->assertSame($previous->getMessage(), $e->getMessage());
        }
    }

    public function testDecodeWrapPreservesMessageCodeAndPrevious(): void
    {
        try {
            Json::decode('{invalid}');
            $this->fail('Json::decode should have thrown on invalid JSON.');
        } catch (MalformedJsonException $e) {
            $this->assertSame(JSON_ERROR_SYNTAX, $e->getCode());
            $previous = $e->getPrevious();
            $this->assertInstanceOf(JsonException::class, $previous);
            $this->assertNotInstanceOf(MalformedJsonException::class, $previous);
            $this->assertSame($previous->getMessage(), $e->getMessage());
        }
    }

    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Json::is($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable
    {
        yield 'object' => ['{"a":1}', true];

        yield 'null literal' => ['null', true];

        yield 'number' => ['42', true];

        yield 'string' => ['"hello"', true];

        yield 'malformed' => ['{invalid}', false];

        yield 'empty' => ['', false];

        yield 'php null' => [null, false];

        yield 'php int' => [42, false];

        yield 'php array' => [['a' => 1], false];

        yield 'php object' => [new stdClass(), false];
    }

    public function testEncodeDefaultFlagsAreZero(): void
    {
        $this->assertSame('"<"', Json::encode('<')); // any default flag bit (e.g. JSON_HEX_TAG) would escape this
    }

    public function testDecodeAcceptsNestingAtDepthLimit(): void
    {
        $depth511 = str_repeat('[', 511) . str_repeat(']', 511);
        $this->assertIsArray(Json::decode($depth511));
    }

    public function testDecodeRejectsNestingBeyondDepthLimit(): void
    {
        $this->expectException(MalformedJsonException::class);
        Json::decode(str_repeat('[', 512) . str_repeat(']', 512));
    }
}
