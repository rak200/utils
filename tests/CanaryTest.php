<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;

/** Canary for RFC 0017 step 5 — a deliberately failing assertion. Never merged. */
final class CanaryTest extends TestCase
{
    public function testFailsOnPurposeSoThatComposerTestIsProvenToGate(): void
    {
        self::assertSame(1, 2);
    }
}
