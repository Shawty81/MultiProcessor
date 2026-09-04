<?php

declare(strict_types=1);

namespace MultiProcessor\Exception;

use RuntimeException;

final class ForkFailedException extends RuntimeException implements ExceptionInterface {}
