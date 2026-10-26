<?php

namespace App\Actions\Issues;

use App\Models\OrderIssue;
use App\Models\User;

class OfferResolutions
{
    /**
     * @param OrderIssue $issue
     * @param array<int, string> $options ['refund', 'replacement', 'exchange']
     * @param User $staff
     */
    public function handle(OrderIssue $issue, array $options, User $staff): void
    {
        abort_if(empty($options), 422, 'Please select at least one resolution option to offer.');
        abort_if(array_diff($options, ['refund', 'replacement', 'exchange']), 422, 'Invalid resolution options provided.');

        $issue->update([
            'offered' => array_values($options),
            'status' => OrderIssue::STATUS_AWAITING_CUSTOMER,
            'first_response_at' => $issue->first_response_at ?? now(),
            'assigned_to' => $staff->id,
        ]);

        $issue->events()->create([
            'type' => 'resolution_offered',
            'actor' => 'staff',
            'note' => 'Offered options: ' . implode(', ', $options),
        ]);
    }
}
