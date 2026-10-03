<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * A guest tried to change an order the restaurant has already accepted: from
 * then on the kitchen may be at it, so a change goes through a phone call.
 */
class OrderLocked extends RuntimeException {}
