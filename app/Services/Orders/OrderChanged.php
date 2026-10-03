<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * A guest sent a change made from an older version of their order: another
 * tab or phone changed it first, and sending this one would undo that.
 */
class OrderChanged extends RuntimeException {}
