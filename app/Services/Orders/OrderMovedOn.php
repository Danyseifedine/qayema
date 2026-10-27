<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * The owner tried to move an order to a status it can no longer reach from
 * where it stands now (another tab, or the guest, got there first).
 */
class OrderMovedOn extends RuntimeException {}
