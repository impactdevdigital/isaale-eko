<?php

namespace App\Livewire\Admin;

use App\Models\Country;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kitchen Recipes & Ingredient Bundles Manager.
 * Enables store staff to curate authentic African & Caribbean recipes,
 * feature them on the storefront homepage, and link shelf products as
 * one-click purchasable ingredient kits.
 */
class RecipesManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $countryFilter = '';

    public string $featuredFilter = '';

    // Modal state
    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingRecipeId = null;

    // Form fields
    public string $name = '';

    public string $slug = '';

    public string $tagline = '';

    public ?int $country_id = null;

    public ?int $duration_minutes = null;

    public string $image = '';

    public string $thumbnail = '';

    public bool $is_featured = false;

    public int $sort_order = 0;

    // Ingredient Bundling
    public string $ingredientSearch = '';

    /** @var array<int, int> List of product IDs attached as ingredients */
    public array $attachedProductIds = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'countryFilter' => ['except' => ''],
        'featuredFilter' => ['except' => ''],
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:recipes,slug,'.($this->editingRecipeId ?? 'NULL'),
            'tagline' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'image' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCountryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFeaturedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedName(): void
    {
        if (! $this->editingRecipeId) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function toggleFeatured(int $recipeId): void
    {
        $recipe = Recipe::findOrFail($recipeId);
        $recipe->update(['is_featured' => ! $recipe->is_featured]);

        $status = $recipe->is_featured ? 'featured on the homepage' : 'removed from homepage featured';
        session()->flash('success', "Recipe \"{$recipe->name}\" is now {$status}.");
    }

    public function createRecipe(): void
    {
        $this->resetValidation();
        $this->editingRecipeId = null;
        $this->name = '';
        $this->slug = '';
        $this->tagline = '';
        $this->country_id = null;
        $this->duration_minutes = 45;
        $this->image = '';
        $this->thumbnail = '';
        $this->is_featured = false;
        $this->sort_order = (int) (Recipe::max('sort_order') ?? 0) + 1;
        $this->ingredientSearch = '';
        $this->attachedProductIds = [];
        $this->showEditModal = true;
    }

    public function editRecipe(int $recipeId): void
    {
        $this->resetValidation();
        $recipe = Recipe::with('products')->findOrFail($recipeId);
        $this->editingRecipeId = $recipe->id;
        $this->name = $recipe->name;
        $this->slug = $recipe->slug;
        $this->tagline = $recipe->tagline ?? '';
        $this->country_id = $recipe->country_id;
        $this->duration_minutes = $recipe->duration_minutes;
        $this->image = $recipe->image ?? '';
        $this->thumbnail = $recipe->thumbnail ?? '';
        $this->is_featured = (bool) $recipe->is_featured;
        $this->sort_order = (int) $recipe->sort_order;
        $this->ingredientSearch = '';
        $this->attachedProductIds = $recipe->products->pluck('id')->all();
        $this->showEditModal = true;
    }

    public function attachProduct(int $productId): void
    {
        if (! in_array($productId, $this->attachedProductIds, true)) {
            $this->attachedProductIds[] = $productId;
        }
        $this->ingredientSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->attachedProductIds = array_values(array_filter($this->attachedProductIds, fn ($id) => $id !== $productId));
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingRecipeId) {
            $recipe = Recipe::findOrFail($this->editingRecipeId);
            $recipe->update($validated);
            $message = "Recipe \"{$recipe->name}\" updated successfully.";
        } else {
            $recipe = Recipe::create($validated);
            $message = "Recipe \"{$recipe->name}\" created successfully.";
        }

        // Sync ingredient attachments with pivot sort order
        $syncData = [];
        foreach ($this->attachedProductIds as $index => $productId) {
            $syncData[$productId] = ['sort_order' => $index];
        }
        $recipe->products()->sync($syncData);

        $this->showEditModal = false;
        session()->flash('success', $message);
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
        $recipe->products()->detach();
        $recipe->delete();

        $this->showDeleteModal = false;
        $this->editingRecipeId = null;
        session()->flash('success', "Recipe \"{$name}\" has been deleted.");
    }

    public function render()
    {
        $query = Recipe::with(['country', 'products.variations'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('tagline', 'like', "%{$term}%"));
            })
            ->when($this->countryFilter, fn ($q) => $q->where('country_id', $this->countryFilter))
            ->when($this->featuredFilter !== '', fn ($q) => $q->where('is_featured', $this->featuredFilter === '1'));

        $attachedProducts = ! empty($this->attachedProductIds)
            ? Product::with('variations')->whereIn('id', $this->attachedProductIds)->get()->sortBy(fn ($p) => array_search($p->id, $this->attachedProductIds))
            : collect();

        $searchResults = strlen(trim($this->ingredientSearch)) >= 2
            ? Product::with('variations')
                ->where('is_active', true)
                ->where(function ($q) {
                    $term = trim($this->ingredientSearch);
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhereHas('variations', fn ($v) => $v->where('sku', 'like', "%{$term}%"));
                })
                ->whereNotIn('id', $this->attachedProductIds)
                ->take(8)
                ->get()
            : collect();

        $counts = [
            'total' => Recipe::count(),
            'featured' => Recipe::where('is_featured', true)->count(),
            'total_ingredients' => DB::table('product_recipe')->count(),
            'countries' => Recipe::whereNotNull('country_id')->distinct('country_id')->count('country_id'),
        ];

        return view('livewire.admin.recipes-manager', [
            'recipes' => $query->orderBy('sort_order')->orderBy('name')->paginate(12),
            'countries' => Country::orderBy('name')->get(),
            'counts' => $counts,
            'attachedProducts' => $attachedProducts,
            'searchResults' => $searchResults,
        ])->layout('layouts.admin', ['title' => 'Kitchen Recipes & Bundles']);
    }
}
