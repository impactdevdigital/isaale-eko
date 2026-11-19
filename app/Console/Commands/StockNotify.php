<?php

namespace App\Console\Commands;

use App\Services\Store\BackInStock;
use Illuminate\Console\Command;

/**
 * Back-in-stock emails also go out right after each Square stock update;
 * this catches anything a mail hiccup left behind.
 */
class StockNotify extends Command
{
    protected $signature = 'stock:notify';

    protected $description = 'Email shoppers who asked to hear when a sold-out product is back';

    public function handle(BackInStock $backInStock): int
    {
        $this->info('Back-in-stock emails sent: '.$backInStock->send());

        return self::SUCCESS;
    }
}
