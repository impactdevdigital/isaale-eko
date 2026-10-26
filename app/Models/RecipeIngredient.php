<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    public const ROLE_REQUIRED = 'required';
    public const ROLE_OPTIONAL = 'optional';

    protected $guarded = [];

    protected $casts = [
        'people_per_pack' => 'integer',
        'sort_order' => 'integer',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function isRequired(): bool
    {
        return $this->role === self::ROLE_REQUIRED;
    }
}
