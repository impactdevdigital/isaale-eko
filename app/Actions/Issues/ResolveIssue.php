<?php

namespace App\Actions\Issues;

use App\Actions\Orders\IssueRefund;
use App\Models\OrderIssue;
use App\Models\User;
use App\Services\Orders\RefundCalculator;

class ResolveIssue
{
    public function __construct(
        private IssueRefund $refunds,
        private RefundCalculator $calculator,
        private ScheduleReplacement $replacements
    ) {}

    public function handle(OrderIssue $issue, string $resolution, User $staff, bool $includeDeliveryFee = false): void
    {
        $order = $issue->order;

        if ($resolution === 'refund') {
            $calc = $this->calculator->forIssue($issue, $includeDeliveryFee);
            $this->refunds->handle(
                $order,
                $calc['amount_cents'],
                'issue',
                "Claim {$issue->reference}: " . ($issue->description ? substr($issue->description, 0, 50) : 'Resolved by staff'),
                $issue->id,
                $calc['delivery_fee_cents']
            );

            $issue->events()->create([
                'type' => 'resolved_refund',
                'actor' => 'staff',
                'note' => 'Issued refund of ' . money($calc['amount_cents']),
            ]);
        } elseif ($resolution === 'replacement') {
            $replacement = $this->replacements->handle($issue, $staff);
            $issue->events()->create([
                'type' => 'resolved_replacement',
                'actor' => 'staff',
                'note' => "Scheduled replacement order #{$replacement->number}",
            ]);
        }

        $issue->update([
            'status' => OrderIssue::STATUS_RESOLVED,
            'chosen' => $resolution,
            'resolved_at' => now(),
            'assigned_to' => $staff->id,
        ]);
    }
}
