<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavedBasket extends Model
{
    /** Most baskets one customer can keep. */
    public const MAX_PER_USER = 20;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SavedBasketItem::class);
    }

    /**
     * What the basket cost when it was saved.
     */
    public function savedTotalCents(): int
    {
        return (int) $this->items->sum(fn (SavedBasketItem $item) => $item->unit_price_cents * $item->quantity);
    }
}
