<?php

namespace App\Actions\Kitchen;

use App\Models\Recipe;
use App\Services\Kitchen\RecipeResolver;
use App\Support\Cart;

class AddRecipeToCart
{
    public function __construct(private RecipeResolver $resolver, private Cart $cart) {}

    /**
     * @param Recipe $recipe
     * @param int $people
     * @param int[]|null $onlyIngredientIds null means "everything available"; or pass the ids the customer ticked.
     * @param bool $includeOptional
     * @return array{added: array, skipped: array}
     */
    public function handle(Recipe $recipe, int $people, ?array $onlyIngredientIds = null, bool $includeOptional = false): array
    {
        $resolved = $this->resolver->resolve($recipe, $people);
        $added = [];
        $skipped = [];

        foreach ($resolved->lines as $line) {
            $wanted = $onlyIngredientIds === null
                ? ($line->role === 'required' || $includeOptional)
                : in_array($line->ingredient->id, $onlyIngredientIds, true);

            if (! $wanted) {
                continue;
            }

            if (! $line->available()) {
                $skipped[] = $line->ingredient;
                continue;
            }

            $this->cart->add($line->variation->id, $line->quantity);
            $added[] = $line;
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
        ];
    }
}
