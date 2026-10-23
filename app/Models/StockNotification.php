<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A shopper asking to be emailed when a product is back in stock.
 */
class StockNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
