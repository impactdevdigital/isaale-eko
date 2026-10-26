<?php

namespace App\Console\Commands;

use App\Actions\Orders\CancelUncollectedOrder;
use App\Models\Order;
use App\Models\Setting;
use App\Notifications\OrderUncollected;
use App\Notifications\PickupOverdueReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class HandleUncollectedOrders extends Command
{
    protected $signature = 'orders:handle-uncollected';

    protected $description = 'Scan overdue pickup orders, mark uncollected past grace period, and send reminders.';

    public function handle(CancelUncollectedOrder $cancelAction): int
    {
        $graceDays = (int) Setting::get('uncollected_grace_days', 3);
        $action = Setting::get('uncollected_action', 'hold_for_staff');
        $cutoff = now()->subDays($graceDays);

        $this->info("Scanning uncollected orders (Grace period: {$graceDays} days, Action: {$action})...");

        // 1. Process orders that have exceeded the full grace period
        $overdueOrders = Order::query()
            ->where('fulfilment', 'pickup')
            ->whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])
            ->where('fulfilment_status', '!=', Order::FULFILMENT_COMPLETED)
            ->where('pickup_starts_at', '<=', $cutoff)
            ->whereNull('uncollected_at')
            ->get();

        foreach ($overdueOrders as $order) {
            if ($action === 'auto_cancel') {
                $cancelAction->handle($order);
                $this->warn("Order #{$order->number} auto-cancelled and refunded per store policy.");
            } else {
                $order->update([
                    'status' => Order::STATUS_UNCOLLECTED,
                    'uncollected_at' => now(),
                ]);

                if ($order->contact_email) {
                    Notification::route('mail', $order->contact_email)
                        ->notify(new OrderUncollected($order, 'held'));
                }

                $this->line("Order #{$order->number} marked as uncollected (held for staff review).");
            }
        }

        // 2. Send friendly overdue reminders to orders that passed pickup window by 1+ hours (still in grace)
        $reminderOrders = Order::query()
            ->where('fulfilment', 'pickup')
            ->whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])
            ->where('fulfilment_status', '!=', Order::FULFILMENT_COMPLETED)
            ->where('pickup_starts_at', '>', $cutoff)
            ->where('pickup_ends_at', '<=', now()->subHour())
            ->whereNull('pickup_overdue_reminder_sent_at')
            ->whereNull('uncollected_at')
            ->get();

        foreach ($reminderOrders as $order) {
            $order->update([
                'pickup_overdue_reminder_sent_at' => now(),
            ]);

            if ($order->contact_email) {
                Notification::route('mail', $order->contact_email)
                    ->notify(new PickupOverdueReminder($order));
            }

            $this->line("Sent pickup reminder for order #{$order->number}.");
        }

        $this->info("Completed. {$overdueOrders->count()} uncollected handled, {$reminderOrders->count()} reminders sent.");

        return Command::SUCCESS;
    }
}
