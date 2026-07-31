<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\LookupException;
use Rak200\Utils\Exception\UtilsException;

/**
 * @internal
 *
 * @coversNothing
 */
final class LookupExceptionTest extends TestCase
{
    public function testExtendsOutOfBoundsExceptionAndCarriesTheMarker(): void
    {
        $exception = new LookupException('Key "nope" not found in array.');

        $this->assertInstanceOf(OutOfBoundsException::class, $exception);
        $this->assertInstanceOf(UtilsException::class, $exception);
        $this->assertSame('Key "nope" not found in array.', $exception->getMessage());
    }
}
