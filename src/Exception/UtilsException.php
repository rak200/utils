<?php

declare(strict_types=1);

namespace Rak200\Utils\Exception;

use Throwable;

/**
 * Marker implemented by every exception this library throws —
 * `catch (UtilsException)` is the library-scoped catch. Each implementor
 * extends the precise SPL type its failure kind already used, so existing
 * catches of the SPL types keep matching.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
interface UtilsException extends Throwable {}
