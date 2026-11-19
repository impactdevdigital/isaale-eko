<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step in an order's timeline. Written by Order whenever its status
 * or fulfilment status changes.
 */
class OrderStatusEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "19 Nov 2026, 9:42 AM AST", in the store's Atlantic time zone. */
    public function timeLabel(): string
    {
        return $this->created_at->setTimezone(config('shop.timezone'))->format('j M Y, g:i A T');
    }
}
