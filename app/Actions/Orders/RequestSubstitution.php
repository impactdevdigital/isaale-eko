<?php

namespace App\Actions\Orders;

use App\Models\OrderItem;
use App\Models\ProductVariation;
use App\Models\Setting;
use App\Models\SubstitutionRequest;
use App\Models\User;
use App\Notifications\SubstitutionQuestion;
use Illuminate\Support\Facades\Notification;

class RequestSubstitution
{
    public function create(OrderItem $item, ?ProductVariation $substitute, User $staff, ?string $note = null): SubstitutionRequest
    {
        abort_if($substitute && ! $substitute->inStock(), 422, 'That substitute variation is not in stock.');

        // Cancel any previous pending request for this same line
        SubstitutionRequest::where('order_item_id', $item->id)
            ->whereIn('status', [SubstitutionRequest::STATUS_CONTACTING, SubstitutionRequest::STATUS_AWAITING_REPLY])
            ->update(['status' => SubstitutionRequest::STATUS_CANCELLED, 'resolved_at' => now()]);

        // Difference: (substitute_unit_price * original_qty) - original_line_total
        $priceDiff = $substitute
            ? ($substitute->price_cents * $item->quantity) - $item->line_total_cents
            : -$item->line_total_cents;

        return SubstitutionRequest::create([
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'created_by' => $staff->id,
            'substitute_variation_id' => $substitute?->id,
            'price_difference_cents' => $priceDiff,
            'status' => SubstitutionRequest::STATUS_CONTACTING,
            'note' => $note,
        ]);
    }

    public function logCall(SubstitutionRequest $r, bool $reached, User $staff): void
    {
        $log = $r->call_log ?? [];
        $log[] = [
            'at' => now()->toIso8601String(),
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'reached' => $reached,
        ];

        $r->update(['call_log' => $log]);
    }

    /**
     * Phone did not work or staff wants to dispatch the email notification.
     * Starts the 15-minute countdown clock.
     */
    public function sendMessage(SubstitutionRequest $r): void
    {
        $waitMinutes = (int) Setting::get('substitution_response_minutes', 15);

        $r->update([
            'status' => SubstitutionRequest::STATUS_AWAITING_REPLY,
            'message_sent_at' => now(),
            'response_deadline_at' => now()->addMinutes($waitMinutes),
        ]);

        if ($r->order->contact_email) {
            Notification::route('mail', $r->order->contact_email)
                ->notify(new SubstitutionQuestion($r));
        }
    }
}
