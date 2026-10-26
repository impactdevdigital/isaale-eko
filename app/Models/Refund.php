<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const STATUS_INITIATING = 'initiating';
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    public const SOURCE_ISSUE = 'issue';
    public const SOURCE_SUBSTITUTION = 'substitution';
    public const SOURCE_UNCOLLECTED = 'uncollected';
    public const SOURCE_EXCHANGE = 'exchange';
    public const SOURCE_GOODWILL = 'goodwill';
    public const SOURCE_IN_STORE = 'in_store';

    protected $guarded = [];

    protected $casts = [
        'amount_cents' => 'integer',
        'delivery_fee_cents' => 'integer',
        'initiated_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(OrderIssue::class, 'order_issue_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isInitiated(): bool
    {
        return $this->status === self::STATUS_INITIATED;
    }
}
