<?php

namespace App\Console\Commands;

use App\Models\OrderIssue;
use Illuminate\Console\Command;

class IssuesRemindOverdue extends Command
{
    protected $signature = 'issues:remind-overdue';

    protected $description = 'Check for open order issues that have exceeded their 24-hour response target';

    public function handle(): int
    {
        $overdue = OrderIssue::where('status', OrderIssue::STATUS_NEW)
            ->whereNull('first_response_at')
            ->where('due_at', '<=', now())
            ->with('order')
            ->get();

        if ($overdue->isNotEmpty()) {
            $this->warn("Found {$overdue->count()} customer issue case(s) past their 24-hour SLA target.");
            foreach ($overdue as $case) {
                $this->line("- [Case {$case->reference}] Order #{$case->order->number} ({$case->channel}) – Due: {$case->due_at}");
            }
        }

        return self::SUCCESS;
    }
}
