<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

use UnexpectedValueException;

/**
 * A user callback returned an unusable value. The library's
 * {@see UnexpectedValueException}.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class BadCallbackException extends UnexpectedValueException implements UtilsException {}
