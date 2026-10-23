<?php

namespace App\Services\Checkout;

use RuntimeException;

/**
 * Something the shopper can fix: the message is shown as-is.
 */
class CheckoutProblem extends RuntimeException {}
