<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A hand-picked product list shown on the storefront, e.g. "Fresh this week".
 */
class ProductCollection extends Model
{
    protected $table = 'collections';

    protected $guarded = [];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_product', 'collection_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
