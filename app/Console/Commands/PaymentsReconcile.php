<?php

namespace App\Console\Commands;

use App\Services\Checkout\PaymentReconciler;
use Illuminate\Console\Command;

/**
 * Asks Square about card orders stuck on "awaiting payment" (a timeout or a
 * dropped connection) and marks each one paid or failed.
 */
class PaymentsReconcile extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Settle card orders whose payment outcome we never heard from Square';

    public function handle(PaymentReconciler $reconciler): int
    {
        $counts = $reconciler->resolveAll();

        $this->info("Paid: {$counts['paid']}, failed: {$counts['failed']}, still waiting: {$counts['pending']}");

        return self::SUCCESS;
    }
}
