<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\IOException;
use Rak200\Utils\Exception\UtilsException;
use ReflectionClass;
use RuntimeException;

/**
 * @internal
 *
 * @coversNothing
 */
final class IOExceptionTest extends TestCase
{
    public function testIsAbstractExtendsRuntimeExceptionAndCarriesTheMarker(): void
    {
        $reflection = new ReflectionClass(IOException::class);

        $this->assertTrue($reflection->isAbstract());
        $this->assertTrue($reflection->isSubclassOf(RuntimeException::class));
        $this->assertTrue($reflection->implementsInterface(UtilsException::class));
    }
}
