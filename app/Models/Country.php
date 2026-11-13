<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'is_listed' => 'boolean',
    ];

    /**
     * Countries shown to shoppers (home tiles, shop filters, country pages).
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('is_listed', true);
    }

    public function flagUrl(): ?string
    {
        return $this->code ? asset('images/flags/'.strtolower($this->code).'.svg') : null;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
