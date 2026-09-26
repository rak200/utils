<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\MalformedJsonException;
use Rak200\Utils\Exception\UtilsException;

/**
 * @internal
 */
#[CoversClass(MalformedJsonException::class)]
final class MalformedJsonExceptionTest extends TestCase
{
    public function testExtendsTheNativeJsonExceptionAndCarriesTheMarker(): void
    {
        $exception = new MalformedJsonException('Syntax error');

        $this->assertInstanceOf(JsonException::class, $exception);
        $this->assertInstanceOf(UtilsException::class, $exception);
        $this->assertSame('Syntax error', $exception->getMessage());
    }
}
