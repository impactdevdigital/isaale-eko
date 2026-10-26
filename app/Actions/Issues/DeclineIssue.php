<?php

namespace App\Actions\Issues;

use App\Models\OrderIssue;
use App\Models\User;

class DeclineIssue
{
    public const REASONS = [
        'food_change_of_mind' => 'Food and perishable products are not eligible for return or refund due to change of mind.',
        'outside_window' => 'The claim was submitted outside the policy reporting window without grounds for exception.',
        'no_evidence' => 'Requested photos or proof of defect were not provided.',
        'not_in_order' => 'The item reported was not part of this order record.',
        'damage_after_delivery' => 'Damage occurred after confirmed delivery/collection handoff.',
        'other' => 'Does not meet eligibility criteria under store return and refund policy.',
    ];

    public function handle(OrderIssue $issue, string $reasonKey, ?string $customNote, User $staff): void
    {
        $explanation = self::REASONS[$reasonKey] ?? self::REASONS['other'];
        if ($customNote) {
            $explanation .= ' ' . trim($customNote);
        }

        $issue->update([
            'status' => OrderIssue::STATUS_DECLINED,
            'resolved_at' => now(),
            'assigned_to' => $staff->id,
        ]);

        $issue->events()->create([
            'type' => 'declined',
            'actor' => 'staff',
            'note' => "Declined: {$explanation} (Note: This decision does not limit any statutory rights under applicable consumer protection law)",
        ]);
    }
}
