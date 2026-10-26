<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubstitutionRequest extends Model
{
    public const STATUS_CONTACTING = 'contacting';
    public const STATUS_AWAITING_REPLY = 'awaiting_reply';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_AUTO_REFUNDED = 'auto_refunded';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'call_log' => 'array',
        'price_difference_cents' => 'integer',
        'message_sent_at' => 'datetime',
        'response_deadline_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'substitute_variation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_CONTACTING, self::STATUS_AWAITING_REPLY], true);
    }

    public function isAwaitingReply(): bool
    {
        return $this->status === self::STATUS_AWAITING_REPLY;
    }

    public function hasExpired(): bool
    {
        return $this->response_deadline_at !== null && $this->response_deadline_at->isPast();
    }

    public function remainingSeconds(): int
    {
        if (!$this->response_deadline_at) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->response_deadline_at, false));
    }
}
