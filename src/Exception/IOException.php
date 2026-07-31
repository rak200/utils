<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

use RuntimeException;

/**
 * Environment / native I/O failure — a native call failed despite valid
 * preconditions. Abstract grouping node: catch it to cover every I/O domain
 * this library may grow; concrete throws always use a subclass.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
abstract class IOException extends RuntimeException implements UtilsException {}
