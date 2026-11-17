<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Square\OrderSync;
use Illuminate\Console\Command;

/**
 * Retry confirmed e-Transfer orders that didn't reach Square (for example
 * Square was down when staff confirmed them). Card orders can't be resent:
 * their payment already exists in Square on its own.
 */
class SquarePushOrders extends Command
{
    protected $signature = 'square:push-orders {--order= : Only this order number}';

    protected $description = 'Send confirmed e-Transfer orders that are missing from Square to Square';

    public function handle(OrderSync $sync): int
    {
        if (! OrderSync::enabled()) {
            $this->error('Square order sync is off (SQUARE_SYNC_ORDERS) or Square is not configured.');

            return self::FAILURE;
        }

        $orders = Order::query()
            ->where('payment_method', 'etransfer')
            ->where('status', Order::STATUS_PAID)
            ->whereNull('square_synced_at')
            ->when($this->option('order'), fn ($q, $number) => $q->where('number', $number))
            ->get();

        foreach ($orders as $order) {
            $sent = $sync->pushPaidOutsideSquare($order);
            $this->line(($sent ? '<info>sent</info>   ' : '<error>failed</error> ').$order->number.($sent ? '' : '  '.$order->refresh()->square_sync_error));
        }

        $this->info($orders->isEmpty() ? 'Nothing to send.' : 'Done.');

        return self::SUCCESS;
    }
}
