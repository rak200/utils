<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

use InvalidArgumentException;

/**
 * Malformed input or an out-of-domain argument — a parse failure, a bad
 * encoding, a rejected argument guard. The library's {@see InvalidArgumentException}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class MalformedArgumentException extends InvalidArgumentException implements UtilsException {}
