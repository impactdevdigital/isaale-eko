<?php

namespace App\Livewire\Store\Kitchen;

use App\Actions\Kitchen\AddRecipeToCart;
use App\Models\Recipe;
use App\Services\Analytics;
use App\Services\Kitchen\RecipeResolver;
use App\Support\Cart;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
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

        Analytics::track('view_recipe', ['recipe_id' => $recipe->id, 'recipe_name' => $recipe->name]);

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

    /**
     * Save or unsave this recipe as a favourite meal; guests are asked to sign in.
     */
    public function toggleSaved(): void
    {
        if (! $user = Auth::user()) {
            $this->dispatch('sign-in-open');

            return;
        }

        $user->savedRecipes()->toggle($this->recipe->id);
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
        app(Cart::class)->add($variationId, 1);
        $this->dispatch('cart-updated');
        session()->flash('cart_success', 'Ingredient added to your basket!');
    }

    /**
     * Recipe structured data so the dish can show as a rich recipe result.
     */
    private function seo(): void
    {
        $recipe = $this->recipe;
        $image = $recipe->image_url ?: $recipe->image;
        $description = $recipe->description ?: $recipe->intro ?: $recipe->tagline
            ?: "How to cook {$recipe->title}, with every ingredient ready to add to your Ìsàlèè Èkó basket.";

        Seo::description($description)
            ->image($image)
            ->type('article')
            ->canonical(route('kitchen.recipe', $recipe));

        $ingredients = $recipe->ingredients()->get()
            ->map(fn ($i) => trim(collect([$i->quantity ?? null, $i->unit ?? null, $i->name ?? null])->filter()->implode(' ')))
            ->filter()->values();
        if ($ingredients->isEmpty()) {
            $ingredients = $recipe->products()->get()->map(fn ($p) => $p->localized_name)->values();
        }

        Seo::schema(array_filter([
            '@type' => 'Recipe',
            'name' => $recipe->title ?: $recipe->name,
            'description' => Str::limit(strip_tags((string) $description), 500),
            'image' => $image ? [Seo::absolute($image)] : null,
            'author' => ['@type' => 'Organization', 'name' => Seo::SITE_NAME],
            'recipeCuisine' => $recipe->country?->name ?? $recipe->cuisine,
            'recipeYield' => $recipe->base_servings ? $recipe->base_servings.' servings' : null,
            'prepTime' => $recipe->prep_minutes ? 'PT'.$recipe->prep_minutes.'M' : null,
            'totalTime' => $recipe->duration_minutes ? 'PT'.$recipe->duration_minutes.'M' : null,
            'keywords' => collect([$recipe->cuisine, ...($recipe->diet_tags ?? [])])->filter()->implode(', ') ?: null,
            'recipeIngredient' => $ingredients->all() ?: null,
            'recipeInstructions' => collect($recipe->parsedSteps())->map(fn ($step) => [
                '@type' => 'HowToStep',
                'name' => $step['title'],
                'text' => $step['body'] ?: $step['title'],
            ])->all() ?: null,
        ]));
    }

    public function render(RecipeResolver $resolver)
    {
        $resolved = $resolver->resolve($this->recipe, $this->people);
        $this->seo();

        // Find recipes for "Cook Next"
        $cookNext = Recipe::approved()
            ->where('id', '!=', $this->recipe->id)
            ->with('country')
            ->take(3)
            ->get();

        return view('livewire.store.kitchen.recipe-detail', [
            'resolved' => $resolved,
            'cookNext' => $cookNext,
            'stories' => $this->recipe->stories()->published()->latest('published_at')->take(3)->get(),
            'isSaved' => Auth::check() && Auth::user()->savedRecipes()->whereKey($this->recipe->id)->exists(),
            'cuisines' => config('kitchen.cuisines', []),
        ])->title("{$this->recipe->title} Recipe");
    }
}
