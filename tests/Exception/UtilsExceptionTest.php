<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\UtilsException;
use ReflectionClass;
use Throwable;

/**
 * @internal
 *
 * @coversNothing
 */
final class UtilsExceptionTest extends TestCase
{
    public function testIsAnInterfaceExtendingThrowable(): void
    {
        $reflection = new ReflectionClass(UtilsException::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertTrue($reflection->implementsInterface(Throwable::class));
    }
}
