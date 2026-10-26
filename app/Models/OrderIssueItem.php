<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIssueItem extends Model
{
    public const TYPE_WRONG_PRODUCT = 'wrong_product';
    public const TYPE_WRONG_QUANTITY = 'wrong_quantity';
    public const TYPE_MISSING_ITEM = 'missing_item';
    public const TYPE_DAMAGED = 'damaged';
    public const TYPE_SPOILED_DEFECTIVE = 'spoiled_defective';
    public const TYPE_NOT_AS_ORDERED = 'not_as_ordered';
    public const TYPE_LATE_OR_UNDELIVERED = 'late_or_undelivered';
    public const TYPE_OTHER_FULFILMENT_ERROR = 'other_fulfilment_error';
    public const TYPE_CHANGE_OF_MIND = 'change_of_mind';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(OrderIssue::class, 'order_issue_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function typeLabel(): string
    {
        return match ($this->issue_type) {
            self::TYPE_WRONG_PRODUCT => 'Wrong product received',
            self::TYPE_WRONG_QUANTITY => 'Wrong quantity received',
            self::TYPE_MISSING_ITEM => 'Item missing from order',
            self::TYPE_DAMAGED => 'Item arrived damaged',
            self::TYPE_SPOILED_DEFECTIVE => 'Spoiled or defective',
            self::TYPE_NOT_AS_ORDERED => 'Not as ordered',
            self::TYPE_LATE_OR_UNDELIVERED => 'Late or undelivered',
            self::TYPE_OTHER_FULFILMENT_ERROR => 'Other fulfilment error',
            self::TYPE_CHANGE_OF_MIND => 'Change of mind (non-food)',
            default => ucfirst(str_replace('_', ' ', $this->issue_type)),
        };
    }
}
