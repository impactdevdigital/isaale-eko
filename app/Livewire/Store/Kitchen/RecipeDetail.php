<?php

namespace App\Livewire\Store\Kitchen;

use App\Actions\Kitchen\AddRecipeToCart;
use App\Models\Recipe;
use App\Services\Kitchen\RecipeResolver;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.store')]
class RecipeDetail extends Component
{
    public Recipe $recipe;

    public int $people = 4;

    /** @var int[] Selected ingredient IDs for cart addition */
    public array $selectedIngredientIds = [];

    public bool $includeOptional = true;

    public bool $addedSuccess = false;

    public int $lastAddedCount = 0;

    public function mount(Recipe $recipe): void
    {
        // Only approved recipes show on the public storefront
        if (! $recipe->isApproved() && ! auth()->user()?->is_admin) {
            abort(404);
        }

        $this->recipe = $recipe->loadMissing([
            'country',
            'ingredients.ingredient.options.variation.product',
            'ingredients.ingredient.substitutes.options.variation',
        ]);

        $this->people = max(1, min((int) ($recipe->base_servings ?: 4), (int) config('kitchen.max_people', 20)));

        // Preselect all non-staple ingredients
        $this->selectedIngredientIds = $this->recipe->ingredients
            ->filter(fn ($ri) => $ri->ingredient && ! $ri->ingredient->is_pantry_staple)
            ->pluck('ingredient_id')
            ->toArray();
    }

    public function incrementPeople(): void
    {
        $max = (int) config('kitchen.max_people', 20);
        if ($this->people < $max) {
            $this->people++;
        }
    }

    public function decrementPeople(): void
    {
        if ($this->people > 1) {
            $this->people--;
        }
    }

    public function toggleIngredient(int $ingredientId): void
    {
        if (in_array($ingredientId, $this->selectedIngredientIds, true)) {
            $this->selectedIngredientIds = array_values(array_filter($this->selectedIngredientIds, fn ($id) => $id !== $ingredientId));
        } else {
            $this->selectedIngredientIds[] = $ingredientId;
        }
    }

    public function addIngredientsToCart(AddRecipeToCart $action): void
    {
        $result = $action->handle(
            recipe: $this->recipe,
            people: $this->people,
            onlyIngredientIds: $this->selectedIngredientIds,
            includeOptional: $this->includeOptional
        );

        $this->lastAddedCount = count($result['added']);
        $this->addedSuccess = true;

        $this->dispatch('cart-updated');

        if ($this->lastAddedCount > 0) {
            session()->flash('cart_success', "Added {$this->lastAddedCount} ingredients for {$this->people} servings to your basket!");
        } else {
            session()->flash('cart_warning', 'No buyable ingredients were selected.');
        }
    }

    public function addAllIngredients(AddRecipeToCart $action): void
    {
        $result = $action->handle(
            recipe: $this->recipe,
            people: $this->people,
            onlyIngredientIds: null,
            includeOptional: true
        );

        $this->lastAddedCount = count($result['added']);
        $this->addedSuccess = true;

        $this->dispatch('cart-updated');

        if ($this->lastAddedCount > 0) {
            session()->flash('cart_success', "Added all {$this->lastAddedCount} ingredients to your basket!");
        } else {
            session()->flash('cart_warning', 'No ingredients available to add right now.');
        }
    }

    public function addSingleVariation(int $variationId): void
    {
        app(\App\Support\Cart::class)->add($variationId, 1);
        $this->dispatch('cart-updated');
        session()->flash('cart_success', 'Ingredient added to your basket!');
    }

    public function render(RecipeResolver $resolver)
    {
        $resolved = $resolver->resolve($this->recipe, $this->people);

        // Find recipes for "Cook Next"
        $cookNext = Recipe::approved()
            ->where('id', '!=', $this->recipe->id)
            ->with('country')
            ->take(3)
            ->get();

        return view('livewire.store.kitchen.recipe-detail', [
            'resolved' => $resolved,
            'cookNext' => $cookNext,
            'cuisines' => config('kitchen.cuisines', []),
        ])->title("{$this->recipe->title} — Ìsalèè Kitchen");
    }
}
