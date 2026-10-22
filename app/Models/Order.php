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

    /** Confirmed and paid online. */
    public const STATUS_PAID = 'paid';

    /** The online payment was declined; the basket was kept for a retry. */
    public const STATUS_PAYMENT_FAILED = 'payment_failed';

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
        'paid_at' => 'datetime',
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

    /**
     * Placed and either paid or to be paid at the counter.
     */
    public function isConfirmed(): bool
    {
        return in_array($this->status, [self::STATUS_PLACED, self::STATUS_PAID], true);
    }

    /**
     * Progress steps for the order status tracker. Fulfilment updates from
     * the store arrive later; for now a confirmed order sits on step one.
     *
     * @return array<int, string>
     */
    public function trackerSteps(): array
    {
        return $this->fulfilment === 'pickup'
            ? ['Order confirmed', 'Preparing for pickup', 'Ready to collect', 'Collected']
            : ['Order confirmed', 'Preparing your order', 'Out for delivery', 'Delivered'];
    }
}
