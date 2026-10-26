<?php

namespace App\Services\Kitchen;

use App\Models\Recipe;
use App\Models\RecipeIngredient;

class RecipeResolver
{
    /**
     * $strategy: 'preferred' = best-ranked orderable option; 'cheapest' = lowest price per standard pack.
     */
    public function resolve(Recipe $recipe, int $people, string $strategy = 'preferred'): ResolvedRecipe
    {
        $maxPeople = (int) config('kitchen.max_people', 20);
        $people = max(1, min($people, $maxPeople));

        $recipe->loadMissing('ingredients.ingredient.options.variation.product');

        $lines = $recipe->ingredients->map(function (RecipeIngredient $ri) use ($people, $strategy) {
            $ing = $ri->ingredient;
            if (! $ing || $ing->is_pantry_staple) {
                return null; // shown as "you probably have this"
            }

            $standardPacks = (int) ceil($people / max(1, $ri->people_per_pack));

            $options = $ing->options->filter(fn ($o) => $o->variation
                && $o->variation->isOrderable()
                && ($o->variation->product ? (bool) $o->variation->product->is_active : true));

            $pick = $strategy === 'cheapest'
                ? $options->sortBy(fn ($o) => $o->variation->price_cents / max(0.01, (float) $o->pack_factor))->first()
                : $options->sortBy('rank')->first();

            if (! $pick) {
                return new ResolvedLine($ing, $ri->role, null, 0, 0);
            }

            $quantity = (int) ceil($standardPacks / max(0.01, (float) $pick->pack_factor));

            return new ResolvedLine($ing, $ri->role, $pick->variation, $quantity, $pick->variation->price_cents);
        })->filter()->values();

        return new ResolvedRecipe($recipe, $people, $lines);
    }
}
