<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Canary;

/**
 * @internal
 *
 * @coversNothing
 */
final class CanaryTest extends TestCase
{
    public function testExceedsIsCoveredButTheBoundaryIsNotPinned(): void
    {
        self::assertTrue(Canary::exceeds(50));
    }
}
