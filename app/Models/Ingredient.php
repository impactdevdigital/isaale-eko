<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'is_pantry_staple' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(IngredientProduct::class)->orderBy('rank');
    }

    public function substitutes(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_substitutions', 'ingredient_id', 'substitute_id')
            ->withPivot('note')
            ->withTimestamps();
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
