<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

/**
 * A filesystem operation failed — a read/write/delete/list/temp/finfo native
 * returned failure despite valid preconditions. Raised only by the `File`
 * helpers.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class FilesystemException extends IOException {}
