<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /** Cancelled by the customer before the store started on it. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Fulfilment progress of a confirmed order; null means just confirmed. */
    public const FULFILMENT_PREPARING = 'preparing';

    public const FULFILMENT_READY = 'ready';

    public const FULFILMENT_COMPLETED = 'completed';

    /** Order history tabs in the account area. */
    public const TABS = ['all' => 'All orders', 'unpaid' => 'Unpaid', 'processing' => 'Processing', 'shipped' => 'Shipped', 'returns' => 'Returns'];

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
        'cancelled_at' => 'datetime',
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
     * Orders a customer sees in their history. A declined payment never
     * became an order (the basket was kept), so those attempts are hidden.
     */
    public function scopeVisibleToCustomer(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_PAYMENT_FAILED);
    }

    /**
     * Narrow the history to one of the account tabs.
     */
    public function scopeInTab(Builder $query, string $tab): void
    {
        match ($tab) {
            'unpaid' => $query->where(fn ($q) => $q
                ->where('status', self::STATUS_AWAITING_PAYMENT)
                ->orWhere(fn ($q) => $q->where('status', self::STATUS_PLACED)->where(fn ($q) => $q
                    ->whereNull('fulfilment_status')
                    ->orWhere('fulfilment_status', '!=', self::FULFILMENT_COMPLETED)))),
            'processing' => $query->whereIn('status', [self::STATUS_PLACED, self::STATUS_PAID])
                ->where(fn ($q) => $q->whereNull('fulfilment_status')->orWhere('fulfilment_status', self::FULFILMENT_PREPARING)),
            'shipped' => $query->whereIn('status', [self::STATUS_PLACED, self::STATUS_PAID])
                ->whereIn('fulfilment_status', [self::FULFILMENT_READY, self::FULFILMENT_COMPLETED]),
            // Returns aren't handled online yet.
            'returns' => $query->whereRaw('1 = 0'),
            default => null,
        };
    }

    /**
     * Placed and either paid or to be paid at the counter.
     */
    public function isConfirmed(): bool
    {
        return in_array($this->status, [self::STATUS_PLACED, self::STATUS_PAID], true);
    }

    /**
     * Still owes money: pay-at-pickup before collection, or an online
     * payment the payment service hasn't confirmed yet.
     */
    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_AWAITING_PAYMENT
            || ($this->status === self::STATUS_PLACED && $this->fulfilment_status !== self::FULFILMENT_COMPLETED);
    }

    /**
     * Customers may cancel a pay-at-pickup order until the store starts
     * preparing it. Paid orders need a refund, so they go through support.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PLACED && $this->fulfilment_status === null;
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * Progress steps for the order status tracker.
     *
     * @return array<int, string>
     */
    public function trackerSteps(): array
    {
        return $this->fulfilment === 'pickup'
            ? ['Order confirmed', 'Preparing for pickup', 'Ready to collect', 'Collected']
            : ['Order confirmed', 'Preparing for delivery', 'Out for delivery', 'Delivered'];
    }

    /**
     * Index of the tracker step reached, or -1 before confirmation.
     */
    public function trackerStep(): int
    {
        if (! $this->isConfirmed()) {
            return -1;
        }

        return match ($this->fulfilment_status) {
            self::FULFILMENT_PREPARING => 1,
            self::FULFILMENT_READY => 2,
            self::FULFILMENT_COMPLETED => 3,
            default => 0,
        };
    }

    /**
     * Short shopper-facing status, e.g. in the order history.
     */
    public function statusLabel(): string
    {
        return match (true) {
            $this->status === self::STATUS_CANCELLED => 'Cancelled',
            $this->status === self::STATUS_PAYMENT_FAILED => 'Payment declined',
            $this->status === self::STATUS_AWAITING_PAYMENT => 'Payment pending',
            default => $this->trackerSteps()[$this->trackerStep()],
        };
    }
}
