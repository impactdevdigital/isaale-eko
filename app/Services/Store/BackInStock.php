<?php

namespace App\Services\Store;

use App\Mail\BackInStockMail;
use App\Models\StockNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Shoppers can ask to hear when a sold-out product is back (product page,
 * "Notify me"). After each Square stock update, email everyone waiting on
 * a product that can be bought again, once.
 */
class BackInStock
{
    /**
     * @return int emails sent
     */
    public function send(): int
    {
        $sent = 0;

        StockNotification::with('product.defaultVariation')
            ->whereNull('notified_at')
            ->whereHas('product', fn ($q) => $q->active())
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->filter(fn (StockNotification $request) => $request->product?->inStock())
            ->each(function (StockNotification $request) use (&$sent) {
                try {
                    Mail::to($request->email)->send(new BackInStockMail($request->product));
                    $request->forceFill(['notified_at' => now()])->save();
                    $sent++;
                } catch (\Throwable $e) {
                    report($e); // left for the next run
                }
            });

        return $sent;
    }
}
