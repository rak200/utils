<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

use JsonException;

/**
 * Malformed JSON input/output — the library's rethrow of the native
 * {@see JsonException} raised under JSON_THROW_ON_ERROR, with message,
 * `JSON_ERROR_*` code and the native as `getPrevious()`.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class MalformedJsonException extends JsonException implements UtilsException {}
