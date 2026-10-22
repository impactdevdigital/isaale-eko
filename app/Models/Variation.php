<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variation extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tracks_inventory' => 'boolean',
        'price_cents' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(InventorySnapshot::class, 'variation_id');
    }

    /**
     * 'in' | 'low' | 'out'. Stock NOT tracked in Square counts as 'out'.
     */
    public function availabilityState(): string
    {
        if (!$this->tracks_inventory) {
            return 'out';
        }

        return $this->snapshot?->state ?? 'out';
    }

    /**
     * Can this variation be added to a cart and purchased online?
     */
    public function isOrderable(): bool
    {
        return $this->price_cents !== null && $this->availabilityState() !== 'out';
    }
}
