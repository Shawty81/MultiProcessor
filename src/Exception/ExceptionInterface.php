<?php

declare(strict_types=1);

namespace MultiProcessor\Exception;

use Throwable;

/**
 * Implemented by every exception this library throws, so that a consumer can catch
 * everything that comes out of it with a single type.
 */
interface ExceptionInterface extends Throwable {}
