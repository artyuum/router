<?php

namespace Artyum\Router\Exceptions;

use Exception;
use Throwable;

class UnsupportHTTPMethodException extends Exception
{
    public function __construct($message = 'Unsupported HTTP method(s).', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
