<?php

namespace App\Services\Kitchen;

use App\Models\Recipe;
use Illuminate\Support\Collection;

class PantryMatcher
{
    public function __construct(private RecipeResolver $resolver) {}

    /**
     * @param int[] $haveIngredientIds
     * @param int $people
     * @param float $minCoverage 0.0 to 1.0 (default 0.3)
     * @return Collection
     */
    public function match(array $haveIngredientIds, int $people = 4, float $minCoverage = 0.3): Collection
    {
        if (empty($haveIngredientIds)) {
            return collect();
        }

        return Recipe::approved()
            ->with(['ingredients.ingredient', 'country'])
            ->get()
            ->map(function (Recipe $r) use ($haveIngredientIds, $people) {
                $required = $r->ingredients
                    ->where('role', 'required')
                    ->reject(fn ($ri) => ! $ri->ingredient || $ri->ingredient->is_pantry_staple);

                if ($required->isEmpty()) {
                    return null;
                }

                $have = $required->filter(fn ($ri) => in_array($ri->ingredient_id, $haveIngredientIds, true));
                if ($have->isEmpty()) {
                    return null;
                }

                $needIds = $required->pluck('ingredient_id')->diff($haveIngredientIds);
                $resolved = $this->resolver->resolve($r, $people);
                $needLines = $resolved->lines->filter(fn ($l) => $needIds->contains($l->ingredient->id))->values();

                $coverage = $have->count() / $required->count();

                return [
                    'recipe' => $r,
                    'coverage' => $coverage,
                    'coverage_percent' => (int) round($coverage * 100),
                    'have' => $have->pluck('ingredient')->values(),
                    'need' => $needLines,
                    'cost_to_complete_cents' => $needLines->sum(fn ($l) => $l->totalCents()),
                    'buyable' => $needLines->every(fn ($l) => $l->available()),
                ];
            })
            ->filter(fn ($m) => $m !== null && $m['coverage'] >= $minCoverage)
            ->sortByDesc('coverage')
            ->values();
    }
}
