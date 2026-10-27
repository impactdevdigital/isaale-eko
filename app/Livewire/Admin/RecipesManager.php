<?php

namespace App\Livewire\Admin;

use App\Actions\Kitchen\ApproveRecipe;
use App\Models\Country;
use App\Models\Ingredient;
use App\Models\IngredientProduct;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\Kitchen\RecipeResolver;
use App\Services\Kitchen\RecipeValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Kitchen Recipes & Ingredients Engine Admin Desk.
 * Manages authentic recipes, culinary ingredients, store variation options,
 * chef-approved substitutions, and stage-gate recipe approvals.
 */
class RecipesManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public const PRESET_RECIPES = [
        ['name' => 'Nigerian Jollof Rice', 'path' => 'images/store/recipes/jollof-rice.webp', 'origin' => 'Nigeria'],
        ['name' => 'Egusi Soup', 'path' => 'images/store/recipes/egusi-soup.webp', 'origin' => 'Nigeria'],
        ['name' => 'Banga Palm Soup', 'path' => 'images/store/recipes/banga-soup.webp', 'origin' => 'Delta / Nigeria'],
        ['name' => 'Zobo Hibiscus Drink', 'path' => 'images/store/recipes/zobo-hibiscus-drink.webp', 'origin' => 'West Africa'],
        ['name' => 'Curry Goat', 'path' => 'images/store/recipes/curry-goat.webp', 'origin' => 'Jamaica / Caribbean'],
    ];

    /** Active Tab: 'recipes' | 'ingredients' | 'substitutions' */
    public string $tab = 'recipes';

    // Filters for Recipes
    public string $search = '';
    public string $cuisineFilter = '';
    public string $statusFilter = '';
    public string $countryFilter = '';
    public string $featuredFilter = '';

    // Recipe Modal State
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingRecipeId = null;

    // Recipe Form Fields
    public string $name = '';
    public string $title = '';
    public string $slug = '';
    public string $cuisine = 'west_african';
    public int $base_servings = 4;
    public ?int $prep_minutes = 45;
    public ?int $duration_minutes = 45;
    public string $tagline = '';
    public string $intro = '';
    public string $description = '';
    public string $steps = '';
    public ?int $country_id = null;
    public string $image = '';
    public string $thumbnail = '';
    public $imageUpload = null;
    public string $selectedPreset = '';
    public string $imageSourceTab = 'presets';
    public bool $showAdvanced = false;
    public bool $is_featured = false;
    public int $sort_order = 0;
    public string $status = Recipe::STATUS_DRAFT;

    /** @var array<int, array{ingredient_id: int, name: string, role: string, people_per_pack: int, note: ?string, is_pantry_staple: bool, has_options: bool}> */
    public array $recipeIngredients = [];
    public string $ingredientSearchQuery = '';

    // Ingredients Tab State
    public string $ingredientSearch = '';
    public bool $showIngredientModal = false;
    public ?int $editingIngredientId = null;
    public string $ingredientName = '';
    public string $ingredientAltNames = '';
    public bool $ingredientIsPantryStaple = false;
    public bool $showDeleteIngredientModal = false;
    public ?int $deletingIngredientId = null;

    // Product Options (Linking Variation to Ingredient) Modal
    public bool $showOptionsModal = false;
    public ?int $managingIngredientId = null;
    public string $variationSearchQuery = '';
    public ?int $selectedVariationId = null;
    public int $optionRank = 1;
    public float $optionPackFactor = 1.0;

    // Substitutions Tab State
    public bool $showSubstitutionModal = false;
    public ?int $subBaseIngredientId = null;
    public ?int $subSubstituteId = null;
    public string $subNote = '';

    protected $queryString = [
        'tab' => ['except' => 'recipes'],
        'search' => ['except' => ''],
        'cuisineFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'countryFilter' => ['except' => ''],
        'featuredFilter' => ['except' => ''],
    ];

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['recipes', 'ingredients', 'substitutions'], true) ? $tab : 'recipes';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCuisineFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedName(): void
    {
        if (! $this->editingRecipeId) {
            $this->slug = Str::slug($this->name);
            $this->title = $this->name;
        }
    }

    public function selectPreset(string $path): void
    {
        $this->selectedPreset = $path;
        $this->image = $path;
        $this->imageUpload = null;
    }

    public function removeImage(): void
    {
        $this->selectedPreset = '';
        $this->image = '';
        $this->imageUpload = null;
    }

    public function toggleAdvanced(): void
    {
        $this->showAdvanced = ! $this->showAdvanced;
    }

    // --- RECIPE ACTIONS ---

    public function createRecipe(): void
    {
        $this->resetValidation();
        $this->editingRecipeId = null;
        $this->name = '';
        $this->title = '';
        $this->slug = '';
        $this->cuisine = 'west_african';
        $this->base_servings = 4;
        $this->prep_minutes = 45;
        $this->duration_minutes = 45;
        $this->tagline = '';
        $this->intro = '';
        $this->description = '';
        $this->steps = '';
        $this->country_id = null;
        $this->image = '';
        $this->thumbnail = '';
        $this->imageUpload = null;
        $this->selectedPreset = '';
        $this->imageSourceTab = 'presets';
        $this->showAdvanced = false;
        $this->is_featured = false;
        $this->sort_order = (int) (Recipe::max('sort_order') ?? 0) + 1;
        $this->status = Recipe::STATUS_DRAFT;
        $this->recipeIngredients = [];
        $this->ingredientSearchQuery = '';
        $this->showEditModal = true;
    }

    public function editRecipe(int $recipeId): void
    {
        $this->resetValidation();
        $recipe = Recipe::with(['ingredients.ingredient.options'])->findOrFail($recipeId);
        $this->editingRecipeId = $recipe->id;
        $this->name = $recipe->name;
        $this->title = $recipe->title ?: $recipe->name;
        $this->slug = $recipe->slug;
        $this->cuisine = $recipe->cuisine ?? 'west_african';
        $this->base_servings = $recipe->base_servings ?: 4;
        $this->prep_minutes = $recipe->prep_minutes ?: $recipe->duration_minutes ?: 45;
        $this->duration_minutes = $recipe->duration_minutes ?: $this->prep_minutes;
        $this->tagline = $recipe->tagline ?? '';
        $this->intro = $recipe->intro ?: $this->tagline;
        $this->description = (string) ($recipe->description ?? '');
        $this->steps = $recipe->steps ?? '';
        $this->country_id = $recipe->country_id;
        $this->image = $recipe->image ?? '';
        $this->thumbnail = $recipe->thumbnail ?? '';
        $this->imageUpload = null;
        $this->selectedPreset = $recipe->image ?? '';
        $this->imageSourceTab = 'presets';
        $this->showAdvanced = false;
        $this->is_featured = (bool) $recipe->is_featured;
        $this->sort_order = (int) $recipe->sort_order;
        $this->status = $recipe->status ?? Recipe::STATUS_DRAFT;

        $this->recipeIngredients = $recipe->ingredients->map(function ($ri) {
            $ing = $ri->ingredient;
            return [
                'ingredient_id' => $ri->ingredient_id,
                'name' => $ing?->name ?? 'Unknown',
                'role' => $ri->role ?? 'required',
                'people_per_pack' => (int) ($ri->people_per_pack ?: 4),
                'note' => $ri->note ?? '',
                'is_pantry_staple' => (bool) ($ing?->is_pantry_staple ?? false),
                'has_options' => $ing ? $ing->options->isNotEmpty() : false,
            ];
        })->toArray();

        $this->attachedProductIds = $recipe->products ? $recipe->products->pluck('id')->all() : [];
        $this->ingredientSearchQuery = '';
        $this->showEditModal = true;
    }

    public function addIngredientToRecipe(int $ingredientId): void
    {
        // Don't add duplicate
        foreach ($this->recipeIngredients as $ri) {
            if ($ri['ingredient_id'] === $ingredientId) {
                $this->ingredientSearchQuery = '';
                return;
            }
        }

        $ing = Ingredient::with('options')->find($ingredientId);
        if ($ing) {
            $this->recipeIngredients[] = [
                'ingredient_id' => $ing->id,
                'name' => $ing->name,
                'role' => 'required',
                'people_per_pack' => 4,
                'note' => '',
                'is_pantry_staple' => (bool) $ing->is_pantry_staple,
                'has_options' => $ing->options->isNotEmpty(),
            ];
        }
        $this->ingredientSearchQuery = '';
    }

    /** @var array<int, int> Legacy product attachments for backwards compatibility */
    public array $attachedProductIds = [];

    public function attachProduct(int $productId): void
    {
        if (! in_array($productId, $this->attachedProductIds, true)) {
            $this->attachedProductIds[] = $productId;
        }
        $product = Product::find($productId);
        if ($product) {
            $ing = Ingredient::firstOrCreate(
                ['slug' => $product->slug],
                ['name' => $product->name, 'status' => 'active']
            );
            $this->addIngredientToRecipe($ing->id);
        }
    }

    public function removeProduct(int $productId): void
    {
        $this->attachedProductIds = array_values(array_filter($this->attachedProductIds, fn ($id) => $id !== $productId));
        $product = Product::find($productId);
        if ($product) {
            $ing = Ingredient::where('slug', $product->slug)->first();
            if ($ing) {
                foreach ($this->recipeIngredients as $idx => $ri) {
                    if ($ri['ingredient_id'] === $ing->id) {
                        $this->removeIngredientFromRecipe($idx);
                        break;
                    }
                }
            }
        }
    }

    public function save(): void
    {
        $this->saveRecipe();
    }

    public function removeIngredientFromRecipe(int $index): void
    {
        unset($this->recipeIngredients[$index]);
        $this->recipeIngredients = array_values($this->recipeIngredients);
    }

    public function saveRecipe(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:recipes,slug,'.($this->editingRecipeId ?? 'NULL'),
            'cuisine' => 'required|string',
            'base_servings' => 'required|integer|min:1|max:20',
            'prep_minutes' => 'nullable|integer|min:1|max:480',
            'steps' => 'nullable|string',
            'tagline' => 'nullable|string|max:255',
            'intro' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'imageUpload' => 'nullable|mimes:jpeg,jpg,png,webp,svg|max:5120',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $imagePath = $this->selectedPreset ?: ($this->image ?: null);

        if ($this->imageUpload) {
            $ext = $this->imageUpload->getClientOriginalExtension() ?: 'webp';
            $filename = 'recipe-'.Str::slug($this->name ?: 'dish').'-'.time().'.'.$ext;
            $this->imageUpload->storeAs('recipes/uploads', $filename, 'public');
            $imagePath = 'storage/recipes/uploads/'.$filename;
        }

        $data = [
            'name' => trim($this->name),
            'title' => trim($this->title ?: $this->name),
            'slug' => Str::slug($this->slug ?: $this->name),
            'cuisine' => $this->cuisine,
            'base_servings' => $this->base_servings,
            'prep_minutes' => $this->prep_minutes ?: $this->duration_minutes,
            'duration_minutes' => $this->duration_minutes ?: $this->prep_minutes,
            'tagline' => trim($this->tagline) ?: null,
            'intro' => trim($this->intro) ?: (trim($this->tagline) ?: null),
            'description' => trim($this->description) ?: null,
            'steps' => trim($this->steps) ?: null,
            'country_id' => $this->country_id ?: null,
            'image' => $imagePath,
            'image_url' => $imagePath,
            'thumbnail' => $this->thumbnail ?: null,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editingRecipeId) {
                $recipe = Recipe::findOrFail($this->editingRecipeId);
                $recipe->update($data);
            } else {
                $recipe = Recipe::create([...$data, 'status' => Recipe::STATUS_DRAFT]);
                $this->editingRecipeId = $recipe->id;
            }

            // Sync recipe_ingredients
            $recipe->ingredients()->delete();
            $productsToSync = [];
            foreach ($this->recipeIngredients as $index => $ri) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ri['ingredient_id'],
                    'role' => $ri['role'] ?? 'required',
                    'people_per_pack' => max(1, (int) ($ri['people_per_pack'] ?? 4)),
                    'note' => $ri['note'] ?? null,
                    'sort_order' => $index,
                ]);

                // Maintain product_recipe pivot compatibility for storefront
                $ingOptions = IngredientProduct::with('variation.product')
                    ->where('ingredient_id', $ri['ingredient_id'])
                    ->orderBy('rank')
                    ->get();
                foreach ($ingOptions as $opt) {
                    if ($opt->variation?->product_id) {
                        $productsToSync[$opt->variation->product_id] = ['sort_order' => count($productsToSync)];
                    }
                }
            }
            foreach ($this->attachedProductIds as $pId) {
                if (! isset($productsToSync[$pId])) {
                    $productsToSync[$pId] = ['sort_order' => count($productsToSync)];
                }
            }
            $recipe->products()->sync($productsToSync);
        });

        session()->flash('success', "Recipe \"{$this->name}\" saved successfully.");
        $this->showEditModal = false;
    }

    public function approveRecipeAction(int $recipeId, ApproveRecipe $approver): void
    {
        $recipe = Recipe::findOrFail($recipeId);

        try {
            $approver->handle($recipe, auth()->user());
            session()->flash('success', "Recipe \"{$recipe->name}\" has been fully approved and is live on the storefront!");
        } catch (\Throwable $e) {
            session()->flash('error', "Cannot approve recipe: ".$e->getMessage());
        }
    }

    public function toggleFeatured(int $recipeId): void
    {
        $recipe = Recipe::findOrFail($recipeId);
        $recipe->update(['is_featured' => ! $recipe->is_featured]);
        $status = $recipe->is_featured ? 'featured on the homepage' : 'removed from homepage featured';
        session()->flash('success', "Recipe \"{$recipe->name}\" is now {$status}.");
    }

    public function openDeleteModal(int $recipeId): void
    {
        $this->editingRecipeId = $recipeId;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if (! $this->editingRecipeId) {
            return;
        }

        $recipe = Recipe::findOrFail($this->editingRecipeId);
        $name = $recipe->name;
        $recipe->ingredients()->delete();
        $recipe->products()->detach();
        $recipe->delete();

        $this->showDeleteModal = false;
        $this->editingRecipeId = null;
        session()->flash('success', "Recipe \"{$name}\" has been deleted.");
    }

    // --- INGREDIENTS ACTIONS ---

    public function createIngredient(): void
    {
        $this->editingIngredientId = null;
        $this->ingredientName = '';
        $this->ingredientAltNames = '';
        $this->ingredientIsPantryStaple = false;
        $this->showIngredientModal = true;
    }

    public function editIngredient(int $id): void
    {
        $ing = Ingredient::findOrFail($id);
        $this->editingIngredientId = $ing->id;
        $this->ingredientName = $ing->name;
        $this->ingredientAltNames = $ing->alt_names ?? '';
        $this->ingredientIsPantryStaple = (bool) $ing->is_pantry_staple;
        $this->showIngredientModal = true;
    }

    public function saveIngredient(): void
    {
        $this->validate([
            'ingredientName' => 'required|string|max:255',
            'ingredientAltNames' => 'nullable|string|max:500',
            'ingredientIsPantryStaple' => 'boolean',
        ]);

        $data = [
            'name' => trim($this->ingredientName),
            'slug' => Str::slug($this->ingredientName),
            'alt_names' => trim($this->ingredientAltNames) ?: null,
            'is_pantry_staple' => $this->ingredientIsPantryStaple,
            'status' => 'active',
        ];

        if ($this->editingIngredientId) {
            Ingredient::where('id', $this->editingIngredientId)->update($data);
            session()->flash('success', "Ingredient \"{$this->ingredientName}\" updated.");
        } else {
            Ingredient::create($data);
            session()->flash('success', "Ingredient \"{$this->ingredientName}\" created.");
        }

        $this->showIngredientModal = false;
    }

    public function openDeleteIngredient(int $id): void
    {
        $this->deletingIngredientId = $id;
        $this->showDeleteIngredientModal = true;
    }

    public function confirmDeleteIngredient(): void
    {
        if (! $this->deletingIngredientId) {
            return;
        }

        $ing = Ingredient::findOrFail($this->deletingIngredientId);
        $name = $ing->name;
        $ing->options()->delete();
        $ing->substitutes()->detach();
        $ing->delete();

        $this->showDeleteIngredientModal = false;
        $this->deletingIngredientId = null;
        session()->flash('success', "Ingredient \"{$name}\" deleted.");
    }

    // --- PRODUCT OPTIONS ACTIONS ---

    public function openManageOptions(int $ingredientId): void
    {
        $this->managingIngredientId = $ingredientId;
        $this->variationSearchQuery = '';
        $this->selectedVariationId = null;
        $this->optionRank = (int) (IngredientProduct::where('ingredient_id', $ingredientId)->max('rank') ?? 0) + 1;
        $this->optionPackFactor = 1.0;
        $this->showOptionsModal = true;
    }

    public function addProductOption(): void
    {
        $this->validate([
            'selectedVariationId' => 'required|exists:product_variations,id',
            'optionRank' => 'required|integer|min:1|max:20',
            'optionPackFactor' => 'required|numeric|min:0.1|max:50.0',
        ]);

        IngredientProduct::updateOrCreate(
            [
                'ingredient_id' => $this->managingIngredientId,
                'product_variation_id' => $this->selectedVariationId,
            ],
            [
                'rank' => $this->optionRank,
                'pack_factor' => $this->optionPackFactor,
            ]
        );

        $this->selectedVariationId = null;
        $this->variationSearchQuery = '';
        $this->optionRank++;
        session()->flash('options_success', 'Product variation attached to ingredient.');
    }

    public function removeProductOption(int $optionId): void
    {
        IngredientProduct::where('id', $optionId)->delete();
        session()->flash('options_success', 'Option removed.');
    }

    // --- SUBSTITUTIONS ACTIONS ---

    public function createSubstitution(): void
    {
        $this->subBaseIngredientId = null;
        $this->subSubstituteId = null;
        $this->subNote = '';
        $this->showSubstitutionModal = true;
    }

    public function saveSubstitution(): void
    {
        $this->validate([
            'subBaseIngredientId' => 'required|exists:ingredients,id',
            'subSubstituteId' => 'required|exists:ingredients,id|different:subBaseIngredientId',
            'subNote' => 'nullable|string|max:255',
        ]);

        $base = Ingredient::findOrFail($this->subBaseIngredientId);
        $base->substitutes()->syncWithoutDetaching([
            $this->subSubstituteId => ['note' => trim($this->subNote) ?: null],
        ]);

        $this->showSubstitutionModal = false;
        session()->flash('success', 'Approved ingredient substitution recorded.');
    }

    public function removeSubstitution(int $baseId, int $subId): void
    {
        $base = Ingredient::findOrFail($baseId);
        $base->substitutes()->detach($subId);
        session()->flash('success', 'Substitution removed.');
    }

    public function render(RecipeValidator $validator, RecipeResolver $resolver)
    {
        // 1. Recipes tab query
        $recipesQuery = Recipe::with(['country', 'ingredients.ingredient.options.variation', 'approvedBy'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('tagline', 'like', "%{$term}%"));
            })
            ->when($this->cuisineFilter, fn ($q) => $q->where('cuisine', $this->cuisineFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->countryFilter, fn ($q) => $q->where('country_id', $this->countryFilter))
            ->when($this->featuredFilter !== '', fn ($q) => $q->where('is_featured', $this->featuredFilter === '1'));

        $recipes = $recipesQuery->orderBy('sort_order')->orderBy('name')->paginate(12);

        // Precompute problems for visible recipes
        $recipeProblems = [];
        $recipeCostPreviews = [];
        foreach ($recipes as $r) {
            $recipeProblems[$r->id] = $validator->problems($r);
            $recipeCostPreviews[$r->id] = $resolver->resolve($r, 4)->totalCents();
        }

        // 2. Ingredients tab query
        $ingredients = Ingredient::with(['options.variation.product', 'substitutes'])
            ->when($this->ingredientSearch, function ($q) {
                $term = trim($this->ingredientSearch);
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('alt_names', 'like', "%{$term}%");
            })
            ->orderBy('is_pantry_staple')
            ->orderBy('name')
            ->paginate(15, ['*'], 'ingredientsPage');

        // 3. Substitutions tab query
        $substitutions = Ingredient::with('substitutes')
            ->whereHas('substitutes')
            ->orderBy('name')
            ->get();

        // 4. Searching for ingredients when in Recipe edit modal
        $ingredientSearchResults = strlen(trim($this->ingredientSearchQuery)) >= 2
            ? Ingredient::where('status', 'active')
                ->where(function ($q) {
                    $term = trim($this->ingredientSearchQuery);
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('alt_names', 'like', "%{$term}%");
                })
                ->take(8)
                ->get()
            : collect();

        // 5. Searching for variations when managing options
        $variationSearchResults = strlen(trim($this->variationSearchQuery)) >= 2
            ? ProductVariation::with('product')
                ->whereHas('product', fn ($q) => $q->where('is_active', true))
                ->where(function ($q) {
                    $term = trim($this->variationSearchQuery);
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"));
                })
                ->take(8)
                ->get()
            : collect();

        $managingIngredient = $this->managingIngredientId
            ? Ingredient::with(['options.variation.product'])->find($this->managingIngredientId)
            : null;

        $allIngredients = Ingredient::orderBy('name')->get(['id', 'name']);

        $counts = [
            'total_recipes' => Recipe::count(),
            'approved_recipes' => Recipe::where('status', Recipe::STATUS_APPROVED)->count(),
            'draft_recipes' => Recipe::where('status', Recipe::STATUS_DRAFT)->count(),
            'total_ingredients' => Ingredient::count(),
            'total_substitutions' => DB::table('ingredient_substitutions')->count(),
            'cuisines' => count(config('kitchen.cuisines', [])),
        ];

        return view('livewire.admin.recipes-manager', [
            'recipes' => $recipes,
            'recipeProblems' => $recipeProblems,
            'recipeCostPreviews' => $recipeCostPreviews,
            'ingredients' => $ingredients,
            'substitutions' => $substitutions,
            'ingredientSearchResults' => $ingredientSearchResults,
            'variationSearchResults' => $variationSearchResults,
            'managingIngredient' => $managingIngredient,
            'allIngredients' => $allIngredients,
            'countries' => Country::orderBy('name')->get(),
            'cuisines' => config('kitchen.cuisines', []),
            'counts' => $counts,
            'presets' => self::PRESET_RECIPES,
        ])->layout('layouts.admin', ['title' => 'Kitchen Desk & Recipes Engine']);
    }
}
