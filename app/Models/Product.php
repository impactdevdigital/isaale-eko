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
        'gallery' => 'array',
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

    public function stockNotifications(): HasMany
    {
        return $this->hasMany(StockNotification::class);
    }

    /**
     * Product name contains the search term.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        // Wildcards are dropped rather than escaped so the query works the same on SQLite and MySQL.
        $like = '%'.str_replace(['%', '_'], '', $term).'%';

        return $query->where('name', 'like', $like);
    }

    /**
     * Default variation price within [min, max) cents; either bound may be null.
     */
    public function scopePriceBetween(Builder $query, ?int $minCents, ?int $maxCents): Builder
    {
        return $query->whereHas('defaultVariation', fn (Builder $query) => $query
            ->when($minCents !== null, fn ($query) => $query->where('price_cents', '>=', $minCents))
            ->when($maxCents !== null, fn ($query) => $query->where('price_cents', '<', $maxCents)));
    }

    public function scopeAvailable(Builder $query, bool $inStock = true): Builder
    {
        $available = fn (Builder $query) => $query->where(fn ($query) => $query
            ->whereNull('stock_quantity')
            ->orWhere('stock_quantity', '>', 0));

        return $inStock
            ? $query->whereHas('defaultVariation', $available)
            : $query->whereDoesntHave('defaultVariation', $available);
    }

    /**
     * Products carrying the purple tag: trending or a custom label like "Popular".
     */
    public function scopeTrending(Builder $query): Builder
    {
        return $query->where(fn ($query) => $query->where('is_trending', true)->orWhereNotNull('badge_label'));
    }

    public function scopeDiscounted(Builder $query): Builder
    {
        return $query->whereHas('defaultVariation', fn ($query) => $query->whereColumn('previous_price_cents', '>', 'price_cents'));
    }

    public function inStock(): bool
    {
        return $this->is_active && (bool) $this->defaultVariation?->inStock();
    }

    /**
     * Stock state for the product page: in_stock, low_stock, out_of_stock or unavailable.
     */
    public function stockState(): string
    {
        $quantity = $this->defaultVariation?->stock_quantity;

        return match (true) {
            ! $this->is_active || ! $this->defaultVariation => 'unavailable',
            $quantity !== null && $quantity <= 0 => 'out_of_stock',
            $quantity !== null && $quantity <= (int) Setting::get('low_stock_threshold', 5) => 'low_stock',
            default => 'in_stock',
        };
    }

    /**
     * Label for the purple tag, or null when the product has none.
     */
    public function tagLabel(): ?string
    {
        return $this->badge_label ?? ($this->is_trending ? 'Trending' : null);
    }

    /**
     * @return array<int, string>
     */
    public function images(): array
    {
        return $this->gallery ?: array_filter([$this->image]);
    }
}
