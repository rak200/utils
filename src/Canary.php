<?php

declare(strict_types=1);

namespace Rak200\Utils;

/**
 * Canary for RFC 0017 step 5 — a boundary no test pins, so the `>` mutant survives.
 *
 * Never merged.
 */
final class Canary
{
    public static function exceeds(int $value): bool
    {
        return $value > 10;
    }
}
