<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\MalformedArgumentException;
use Rak200\Utils\Exception\UtilsException;

/**
 * @internal
 *
 * @coversNothing
 */
final class MalformedArgumentExceptionTest extends TestCase
{
    public function testExtendsInvalidArgumentExceptionAndCarriesTheMarker(): void
    {
        $exception = new MalformedArgumentException('Size must be positive.');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(UtilsException::class, $exception);
        $this->assertSame('Size must be positive.', $exception->getMessage());
    }
}
