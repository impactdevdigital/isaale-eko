<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderIssue extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_AWAITING_CUSTOMER = 'awaiting_customer';
    public const STATUS_AWAITING_RETURN = 'awaiting_return';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CLOSED = 'closed';

    public const KIND_PROBLEM = 'problem';
    public const KIND_RETURN = 'return';
    public const KIND_SAFETY = 'safety';

    public const CHANNEL_WEBSITE = 'website';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_IN_STORE = 'in_store';

    protected $guarded = [];

    protected $casts = [
        'within_window' => 'boolean',
        'offered' => 'array',
        'exchange_quantity' => 'integer',
        'exchange_difference_cents' => 'integer',
        'reported_at' => 'datetime',
        'due_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function exchangeVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'exchange_variation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderIssueItem::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OrderIssuePhoto::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderIssueEvent::class)->latest();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_REVIEWING, self::STATUS_AWAITING_CUSTOMER, self::STATUS_AWAITING_RETURN], true);
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast() && $this->first_response_at === null;
    }

    public function remainingSlaMinutes(): int
    {
        if (! $this->due_at) {
            return 0;
        }

        return (int) now()->diffInMinutes($this->due_at, false);
    }
}
