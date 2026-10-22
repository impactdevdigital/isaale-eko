<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** Waiting for an online payment (card or wallet). */
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    /** Confirmed; the customer pays at the counter. */
    public const STATUS_PLACED = 'placed';

    protected $guarded = [];

    protected $casts = [
        'pickup_starts_at' => 'datetime',
        'pickup_ends_at' => 'datetime',
        'delivery_address' => 'array',
        'subtotal_cents' => 'integer',
        'savings_cents' => 'integer',
        'discount_cents' => 'integer',
        'delivery_fee_cents' => 'integer',
        'total_cents' => 'integer',
        'placed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
