<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRedemption extends Model
{
    protected $guarded = [];

    protected $casts = [
        'discount_cents' => 'integer',
    ];

    /**
     * Redemptions whose order wasn't abandoned at payment or cancelled.
     */
    public static function counted(): Builder
    {
        return static::query()
            ->join('orders', 'orders.id', '=', 'promotion_redemptions.order_id')
            ->whereNotIn('orders.status', [Order::STATUS_PAYMENT_FAILED, Order::STATUS_CANCELLED]);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
