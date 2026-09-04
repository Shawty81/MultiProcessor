<?php

declare(strict_types=1);

namespace MultiProcessor\Exception;

use InvalidArgumentException;

final class InvalidSettingsException extends InvalidArgumentException implements ExceptionInterface {}
