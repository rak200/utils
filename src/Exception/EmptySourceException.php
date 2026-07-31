<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

use UnderflowException;

/**
 * An operation that needs elements ran on an empty source. The library's
 * {@see UnderflowException}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class EmptySourceException extends UnderflowException implements UtilsException {}
