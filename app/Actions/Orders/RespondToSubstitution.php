<?php

namespace App\Actions\Orders;

use App\Models\OrderItem;
use App\Models\SubstitutionRequest;
use Illuminate\Support\Facades\DB;

class RespondToSubstitution
{
    /**
     * @param SubstitutionRequest $r
     * @param string $answer 'approve' | 'decline' | 'auto_refund'
     * @param string $by 'staff' | 'customer_link' | 'system'
     */
    public function handle(SubstitutionRequest $r, string $answer, string $by): void
    {
        abort_unless(in_array($r->status, [SubstitutionRequest::STATUS_CONTACTING, SubstitutionRequest::STATUS_AWAITING_REPLY], true), 422, 'This substitution request is already closed.');

        if ($by === 'customer_link' && $r->response_deadline_at && $r->response_deadline_at->isPast()) {
            // Customer clicked an expired link -> auto refund instead
            $answer = 'auto_refund';
            $by = 'system';
        }

        DB::transaction(function () use ($r, $answer, $by) {
            $order = $r->order;
            $item = $r->item;

            if ($answer === 'approve' && $r->substitute) {
                // 1. Mark original item as substituted
                $item->update(['line_status' => OrderItem::STATUS_SUBSTITUTED]);

                // 2. Add replacement line item
                $substituteLineTotal = $r->substitute->price_cents * $item->quantity;

                $order->items()->create([
                    'product_id' => $r->substitute->product_id,
                    'product_variation_id' => $r->substitute->id,
                    'name' => $r->substitute->product->name,
                    'variation_name' => $r->substitute->name,
                    'image' => $r->substitute->product->image_url ?? $item->image,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $r->substitute->price_cents,
                    'line_total_cents' => $substituteLineTotal,
                    'line_status' => OrderItem::STATUS_ACTIVE,
                    'item_class' => $r->substitute->product->item_class ?? 'perishable',
                    'is_substitute' => true,
                    'substitute_for_item_id' => $item->id,
                ]);

                $status = SubstitutionRequest::STATUS_APPROVED;
            } else {
                // Declined by customer or timed out: remove item
                $item->update(['line_status' => OrderItem::STATUS_REMOVED]);
                $status = $answer === 'auto_refund'
                    ? SubstitutionRequest::STATUS_AUTO_REFUNDED
                    : SubstitutionRequest::STATUS_DECLINED;
            }

            // 3. Recalculate order active subtotal and totals
            $activeSubtotal = (int) $order->items()->where('line_status', OrderItem::STATUS_ACTIVE)->sum('line_total_cents');
            $order->update([
                'subtotal_cents' => $activeSubtotal,
                'total_cents' => $activeSubtotal + $order->delivery_fee_cents - $order->discount_cents,
            ]);

            // 4. Record resolution on the substitution request
            $r->update([
                'status' => $status,
                'resolved_at' => now(),
            ]);
        });
    }
}
