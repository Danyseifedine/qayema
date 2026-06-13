<?php

namespace App\Exceptions;

use Exception;

class TooManyContactMessages extends Exception
{
    public function __construct(public readonly int $retryAfterHours)
    {
        parent::__construct('Too many contact messages from this address.');
    }
}
