<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

use OutOfBoundsException;

/**
 * A lookup that resolved to nothing — a missing key, index, column, enum
 * name or pattern match. The library's {@see OutOfBoundsException}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class LookupException extends OutOfBoundsException implements UtilsException {}
