<?php

namespace App\Actions\Kitchen;

use App\Models\Recipe;
use App\Models\User;
use App\Services\Kitchen\RecipeValidator;
use InvalidArgumentException;

class ApproveRecipe
{
    public function __construct(private RecipeValidator $validator) {}

    public function handle(Recipe $recipe, ?User $by = null): void
    {
        $problems = $this->validator->problems($recipe);
        if (! empty($problems)) {
            throw new InvalidArgumentException(implode(' ', $problems));
        }

        $recipe->update([
            'status' => Recipe::STATUS_APPROVED,
            'approved_by' => $by?->id,
            'approved_at' => now(),
            'review_due_at' => now()->addYear(),
        ]);
    }
}
