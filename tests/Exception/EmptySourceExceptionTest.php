<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\EmptySourceException;
use Rak200\Utils\Exception\UtilsException;
use UnderflowException;

/**
 * @internal
 *
 * @coversNothing
 */
final class EmptySourceExceptionTest extends TestCase
{
    public function testExtendsUnderflowExceptionAndCarriesTheMarker(): void
    {
        $exception = new EmptySourceException('Array cannot be empty.');

        $this->assertInstanceOf(UnderflowException::class, $exception);
        $this->assertInstanceOf(UtilsException::class, $exception);
        $this->assertSame('Array cannot be empty.', $exception->getMessage());
    }
}
