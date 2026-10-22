<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_trending' => 'boolean',
        'is_hot' => 'boolean',
        'bestseller_rank' => 'integer',
        'discount_estimate' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->orderBy('sort_order');
    }

    /**
     * The variation shown on product cards: the first by sort order.
     */
    public function defaultVariation(): HasOne
    {
        return $this->hasOne(ProductVariation::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class)->withPivot('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Everything a product card needs, in one query.
     */
    public function scopeForCard(Builder $query): Builder
    {
        return $query->active()->with('defaultVariation');
    }

    public function inStock(): bool
    {
        return (bool) $this->defaultVariation?->inStock();
    }
}
