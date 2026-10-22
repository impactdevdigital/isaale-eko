<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price_cents' => 'integer',
        'previous_price_cents' => 'integer',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Untracked stock (null) counts as available.
     */
    public function inStock(): bool
    {
        return $this->stock_quantity === null || $this->stock_quantity > 0;
    }
}
