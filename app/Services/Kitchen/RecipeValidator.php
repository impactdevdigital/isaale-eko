<?php

namespace App\Services\Kitchen;

use App\Models\Recipe;

class RecipeValidator
{
    /**
     * @return string[] An empty list means "good to approve".
     */
    public function problems(Recipe $r): array
    {
        $r->loadMissing('ingredients.ingredient.options');
        $p = [];

        if (blank($r->steps)) {
            $p[] = 'The recipe has no steps.';
        }

        $cuisines = config('kitchen.cuisines', []);
        if (! array_key_exists($r->cuisine, $cuisines)) {
            $p[] = 'Choose a valid cuisine from the list.';
        }

        if ($r->ingredients->where('role', 'required')->isEmpty()) {
            $p[] = 'Add at least one required ingredient.';
        }

        foreach ($r->ingredients as $ri) {
            if (! $ri->ingredient) {
                continue;
            }
            if (! $ri->ingredient->is_pantry_staple && $ri->ingredient->options->isEmpty()) {
                $p[] = "{$ri->ingredient->name} has no store products linked to it.";
            }
        }

        return $p;
    }
}
