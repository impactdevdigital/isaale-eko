<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A line of an order. Name, size and price are copied at checkout so the
 * order stays correct when the catalogue changes.
 */
class OrderItem extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUBSTITUTED = 'substituted';
    public const STATUS_REMOVED = 'removed';

    protected $guarded = [];

    protected $casts = [
        'unit_price_cents' => 'integer',
        'quantity' => 'integer',
        'line_total_cents' => 'integer',
        'is_substitute' => 'boolean',
        'substitute_for_item_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function substitutionRequests(): HasMany
    {
        return $this->hasMany(SubstitutionRequest::class, 'order_item_id');
    }

    public function activeSubstitutionRequest(): HasOne
    {
        return $this->hasOne(SubstitutionRequest::class, 'order_item_id')
            ->whereIn('status', [SubstitutionRequest::STATUS_CONTACTING, SubstitutionRequest::STATUS_AWAITING_REPLY])
            ->latestOfMany();
    }

    public function latestSubstitutionRequest(): HasOne
    {
        return $this->hasOne(SubstitutionRequest::class, 'order_item_id')->latestOfMany();
    }

    public function substituteFor(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'substitute_for_item_id');
    }

    public function substitutes(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'substitute_for_item_id');
    }

    public function isSubstituted(): bool
    {
        return $this->line_status === self::STATUS_SUBSTITUTED;
    }

    public function isRemoved(): bool
    {
        return $this->line_status === self::STATUS_REMOVED;
    }

    public function isActive(): bool
    {
        return $this->line_status === self::STATUS_ACTIVE;
    }
}
