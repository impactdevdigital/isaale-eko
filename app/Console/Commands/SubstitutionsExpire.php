<?php

namespace App\Console\Commands;

use App\Actions\Orders\ExpireSubstitutions;
use Illuminate\Console\Command;

class SubstitutionsExpire extends Command
{
    protected $signature = 'substitutions:expire';

    protected $description = 'Expire overdue substitution requests and remove unavailable items from orders';

    public function handle(ExpireSubstitutions $expire): int
    {
        $count = $expire->due();

        if ($count > 0) {
            $this->info("Expired and auto-refunded {$count} pending substitution request(s).");
        }

        return self::SUCCESS;
    }
}
