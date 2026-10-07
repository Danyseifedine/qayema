<?php

namespace App\Services\Orders;

use RuntimeException;

/** The order was cancelled: what it held is no longer anyone's to change. */
class OrderClosed extends RuntimeException {}
