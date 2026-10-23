<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'is_featured' => 'boolean',
        'featured_order' => 'integer',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)->orderBy('featured_order');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    /**
     * Total products assigned directly to this category or any of its child sub-categories.
     */
    public function allProductsCount(): int
    {
        $childIds = $this->relationLoaded('children')
            ? $this->children->pluck('id')->all()
            : $this->children()->pluck('id')->all();

        $allIds = array_merge([$this->id], $childIds);

        return Product::whereIn('category_id', $allIds)->count();
    }
}
