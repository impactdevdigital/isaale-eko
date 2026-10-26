<?php

namespace App\Services\Kitchen;

use App\Models\Ingredient;
use Illuminate\Support\Collection;

class IngredientSearch
{
    /**
     * Finds active ingredients matching term in name, alternative names, or translations.
     */
    public function find(string $q, int $limit = 15): Collection
    {
        $term = trim($q);
        if ($term === '') {
            return Ingredient::where('status', 'active')
                ->where('is_pantry_staple', false)
                ->orderBy('name')
                ->limit($limit)
                ->get();
        }

        return Ingredient::with('translations')
            ->where('status', 'active')
            ->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('alt_names', 'like', "%{$term}%")
                    ->orWhereHas('translations', fn ($t) => $t->whereIn('field', ['name', 'alt_names'])->where('value', 'like', "%{$term}%"));
            })
            ->limit($limit)
            ->get();
    }
}
