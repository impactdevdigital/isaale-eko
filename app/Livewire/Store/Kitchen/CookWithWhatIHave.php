<?php

namespace App\Livewire\Store\Kitchen;

use App\Actions\Kitchen\AddRecipeToCart;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Services\Kitchen\IngredientSearch;
use App\Services\Kitchen\PantryMatcher;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.store')]
#[Title('Cook With What I Have — Pantry Matcher')]
class CookWithWhatIHave extends Component
{
    /** @var int[] List of ingredient IDs customer has */
    public array $pantryIngredientIds = [];

    public string $search = '';

    public int $people = 4;

    public function mount(): void
    {
        // Pre-select popular ingredients if passed in query string
        if (request()->has('ingredients')) {
            $slugs = explode(',', (string) request('ingredients'));
            $this->pantryIngredientIds = Ingredient::whereIn('slug', $slugs)->pluck('id')->toArray();
        }
    }

    public function addIngredient(int $id): void
    {
        if (! in_array($id, $this->pantryIngredientIds, true)) {
            $this->pantryIngredientIds[] = $id;
        }
        $this->search = '';
    }

    public function removeIngredient(int $id): void
    {
        $this->pantryIngredientIds = array_values(array_filter($this->pantryIngredientIds, fn ($i) => $i !== $id));
    }

    public function toggleQuick(string $slug): void
    {
        $ing = Ingredient::where('slug', $slug)->first();
        if ($ing) {
            if (in_array($ing->id, $this->pantryIngredientIds, true)) {
                $this->removeIngredient($ing->id);
            } else {
                $this->addIngredient($ing->id);
            }
        }
    }

    public function clearAll(): void
    {
        $this->pantryIngredientIds = [];
        $this->search = '';
    }

    public function addMissingToCart(int $recipeId, AddRecipeToCart $action): void
    {
        $recipe = Recipe::findOrFail($recipeId);

        // Find missing ingredient IDs for this recipe
        $requiredIngredients = $recipe->ingredients
            ->where('role', 'required')
            ->reject(fn ($ri) => ! $ri->ingredient || $ri->ingredient->is_pantry_staple)
            ->pluck('ingredient_id');

        $missingIds = $requiredIngredients->diff($this->pantryIngredientIds)->values()->toArray();

        $result = $action->handle(
            recipe: $recipe,
            people: $this->people,
            onlyIngredientIds: $missingIds,
            includeOptional: false
        );

        $this->dispatch('cart-updated');

        $addedCount = count($result['added']);
        if ($addedCount > 0) {
            session()->flash('match_success', "Added {$addedCount} missing ingredients for {$recipe->title} to your basket!");
        } else {
            session()->flash('match_info', 'All required ingredients for this recipe are already in your pantry!');
        }
    }

    public function render(IngredientSearch $searcher, PantryMatcher $matcher)
    {
        $searchResults = strlen(trim($this->search)) >= 2
            ? $searcher->find($this->search, 8)->reject(fn ($i) => in_array($i->id, $this->pantryIngredientIds, true))
            : collect();

        $selectedIngredients = ! empty($this->pantryIngredientIds)
            ? Ingredient::whereIn('id', $this->pantryIngredientIds)->orderBy('name')->get()
            : collect();

        $matches = ! empty($this->pantryIngredientIds)
            ? $matcher->match($this->pantryIngredientIds, $this->people, 0.25)
            : collect();

        $quickPantry = [
            'parboiled-rice' => 'Parboiled Rice',
            'palm-oil' => 'Red Palm Oil',
            'cooking-oil' => 'Cooking Oil',
            'tomato-paste' => 'Tomato Paste',
            'scotch-bonnet' => 'Scotch Bonnet',
            'egusi' => 'Ground Egusi',
            'fresh-beef' => 'Fresh Beef',
            'crayfish' => 'Dried Crayfish',
            'stockfish' => 'Dried Stockfish',
            'zobo-leaves' => 'Zobo Leaves',
            'ginger' => 'Ginger',
            'seasoning-cubes' => 'Seasoning Cubes',
        ];

        return view('livewire.store.kitchen.cook-with-what-i-have', [
            'searchResults' => $searchResults,
            'selectedIngredients' => $selectedIngredients,
            'matches' => $matches,
            'quickPantry' => $quickPantry,
            'cuisines' => config('kitchen.cuisines', []),
        ]);
    }
}
