<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a spend would take a balance below zero. Carries both numbers so
 * the caller can tell the owner exactly how many coins they're short.
 */
class InsufficientCoins extends RuntimeException
{
    public function __construct(
        public readonly int $needed,
        public readonly int $balance,
    ) {
        parent::__construct(__('coins.insufficient', ['needed' => $needed, 'balance' => $balance]));
    }

    public function shortfall(): int
    {
        return max(0, $this->needed - $this->balance);
    }
}
