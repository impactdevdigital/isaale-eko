<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line of an order. Name, size and price are copied at checkout so the
 * order stays correct when the catalogue changes.
 */
class OrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_price_cents' => 'integer',
        'quantity' => 'integer',
        'line_total_cents' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
