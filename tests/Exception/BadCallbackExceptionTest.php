<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\BadCallbackException;
use Rak200\Utils\Exception\UtilsException;
use UnexpectedValueException;

/**
 * @internal
 *
 * @coversNothing
 */
final class BadCallbackExceptionTest extends TestCase
{
    public function testExtendsUnexpectedValueExceptionAndCarriesTheMarker(): void
    {
        $exception = new BadCallbackException('Callback must return an iterable.');

        $this->assertInstanceOf(UnexpectedValueException::class, $exception);
        $this->assertInstanceOf(UtilsException::class, $exception);
        $this->assertSame('Callback must return an iterable.', $exception->getMessage());
    }
}
