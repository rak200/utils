<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\FilesystemException;
use Rak200\Utils\Exception\IOException;
use Rak200\Utils\Exception\UtilsException;
use RuntimeException;

/**
 * @internal
 *
 * @coversNothing
 */
final class FilesystemExceptionTest extends TestCase
{
    public function testExtendsTheIOBranchAndCarriesTheMarker(): void
    {
        $exception = new FilesystemException('Unable to read file "missing.txt".');

        $this->assertInstanceOf(IOException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(UtilsException::class, $exception);
        $this->assertSame('Unable to read file "missing.txt".', $exception->getMessage());
    }
}
