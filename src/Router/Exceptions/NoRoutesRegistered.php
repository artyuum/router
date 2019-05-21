<?php

namespace Artyum\Router\Exceptions;

use Exception;
use Throwable;

class NoRoutesRegistered extends Exception {

    public function __construct($message = 'No routes registered.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
