<?php

namespace App\Actions\Issues;

use App\Models\Order;
use App\Models\OrderIssue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScheduleReplacement
{
    public function handle(OrderIssue $issue, User $staff): Order
    {
        $original = $issue->order;

        return DB::transaction(function () use ($original, $issue) {
            $replacementCount = Order::where('replacement_for_id', $original->id)->count() + 1;

            $replacement = Order::create([
                'number' => $original->number . '-R' . $replacementCount,
                'user_id' => $original->user_id,
                'status' => 'paid',
                'fulfilment_status' => Order::FULFILMENT_PREPARING,
                'fulfilment' => $original->fulfilment,
                'contact_name' => $original->contact_name,
                'contact_email' => $original->contact_email,
                'contact_phone' => $original->contact_phone,
                'pickup_starts_at' => $original->pickup_starts_at,
                'pickup_ends_at' => $original->pickup_ends_at,
                'delivery_address' => $original->delivery_address,
                'subtotal_cents' => 0,
                'savings_cents' => 0,
                'discount_cents' => 0,
                'delivery_fee_cents' => 0,
                'total_cents' => 0,
                'payment_method' => $original->payment_method,
                'replacement_for_id' => $original->id,
                'placed_at' => now(),
            ]);

            foreach ($issue->items as $ii) {
                if ($ii->orderItem) {
                    $replacement->items()->create([
                        'product_id' => $ii->orderItem->product_id,
                        'product_variation_id' => $ii->orderItem->product_variation_id,
                        'name' => $ii->orderItem->name,
                        'variation_name' => $ii->orderItem->variation_name,
                        'image' => $ii->orderItem->image,
                        'quantity' => $ii->quantity,
                        'unit_price_cents' => 0,
                        'line_total_cents' => 0,
                        'line_status' => 'active',
                        'item_class' => $ii->orderItem->item_class ?? 'perishable',
                        'is_substitute' => false,
                    ]);
                }
            }

            $issue->events()->create([
                'type' => 'replacement_scheduled',
                'actor' => 'staff',
                'note' => "Replacement order created: #{$replacement->number}",
            ]);

            return $replacement;
        });
    }
}
