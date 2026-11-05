<?php

namespace App\Livewire\Store\Kitchen;

use App\Models\MealCombo;
use App\Models\Recipe;
use App\Services\Kitchen\RecipeResolver;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.store')]
#[Title('Ìsalèè Kitchen & Authentic Recipes')]
class KitchenHome extends Component
{
    #[Url]
    public string $cuisine = '';

    #[Url]
    public string $meal = '';

    #[Url]
    public string $search = '';

    public function setCuisine(string $cuisine): void
    {
        $this->cuisine = $this->cuisine === $cuisine ? '' : $cuisine;
    }

    public function setMeal(string $meal): void
    {
        $this->meal = $this->meal === $meal ? '' : $meal;
    }

    public function render(RecipeResolver $resolver)
    {
        $cuisines = config('kitchen.cuisines', []);

        $recipes = Recipe::approved()
            ->with(['country', 'ingredients.ingredient.options.variation'])
            ->when($this->cuisine, fn ($q) => $q->where('cuisine', $this->cuisine))
            ->when($this->meal, function ($q) {
                $term = strtolower($this->meal);
                $q->where(function ($sub) use ($term) {
                    $sub->whereJsonContains('diet_tags', $term)
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('intro', 'like', "%{$term}%");
                });
            })
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('intro', 'like', "%{$term}%")
                    ->orWhere('tagline', 'like', "%{$term}%"));
            })
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $combos = MealCombo::with(['recipes' => fn ($q) => $q->approved()->with('country')])
            ->where('status', 'active')
            ->take(4)
            ->get();

        // Precompute cost preview for 4 people
        $costPreviews = [];
        foreach ($recipes as $recipe) {
            $costPreviews[$recipe->id] = $resolver->resolve($recipe, 4)->totalCents();
        }

        return view('livewire.store.kitchen.kitchen-home', [
            'recipes' => $recipes,
            'combos' => $combos,
            'cuisines' => $cuisines,
            'costPreviews' => $costPreviews,
        ]);
    }
}
