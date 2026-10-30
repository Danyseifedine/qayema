<?php

namespace App\Services\Pos;

use RuntimeException;

/**
 * The POS could not be reached, refused the call, or sent something this
 * menu cannot use. The message is written for the owner; `retry` says
 * whether trying again later may help.
 */
class PosException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retry = true, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }
}
