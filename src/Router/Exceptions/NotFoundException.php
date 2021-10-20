<?php

namespace Artyum\Router\Exceptions;

use Exception;
use Throwable;

class NotFoundException extends Exception
{
    public function __construct($message = 'No route matched the current request.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
