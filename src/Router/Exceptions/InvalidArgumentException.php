<?php

namespace Artyum\Router\Exceptions;

use Exception;
use Throwable;

class InvalidArgumentException extends Exception
{
    public function __construct($message = 'The $handler argument must be a callable or a string', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
