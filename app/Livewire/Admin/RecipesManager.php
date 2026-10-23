<?php

namespace App\Livewire\Admin;

use App\Models\Country;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Kitchen Recipes & Ingredient Bundles Manager.
 * Enables store staff to curate authentic African & Caribbean recipes,
 * feature them on the storefront homepage, and link shelf products as
 * one-click purchasable ingredient kits.
 */
class RecipesManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** Authentic recipe meal presets exported from the store library */
    public const PRESET_RECIPES = [
        ['name' => 'Nigerian Jollof Rice', 'path' => 'images/store/recipes/jollof-rice.webp', 'origin' => 'Nigeria'],
        ['name' => 'Egusi Soup', 'path' => 'images/store/recipes/egusi-soup.webp', 'origin' => 'Nigeria'],
        ['name' => 'Banga Palm Soup', 'path' => 'images/store/recipes/banga-soup.webp', 'origin' => 'Delta / Nigeria'],
        ['name' => 'Zobo Hibiscus Drink', 'path' => 'images/store/recipes/zobo-hibiscus-drink.webp', 'origin' => 'West Africa'],
        ['name' => 'Curry Goat', 'path' => 'images/store/recipes/thumbs/curry-goat.webp', 'origin' => 'Jamaica / Caribbean'],
    ];

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

    /** Temporary file upload instance */
    public $imageUpload = null;

    /** Path of photo chosen from store library presets */
    public string $selectedPreset = '';

    /** Active image selector tab: 'presets' | 'upload' */
    public string $imageSourceTab = 'presets';

    /** Toggle for advanced technical settings (URL slug, sort order) */
    public bool $showAdvanced = false;

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
            'imageUpload' => 'nullable|mimes:jpeg,jpg,png,webp,svg|max:5120',
            'thumbnail' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
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
        $this->imageUpload = null;
        $this->selectedPreset = '';
        $this->imageSourceTab = 'presets';
        $this->showAdvanced = false;
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
        $this->imageUpload = null;
        $this->selectedPreset = $recipe->image ?? '';
        $this->imageSourceTab = 'presets';
        $this->showAdvanced = false;
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
        $this->validate();

        $imagePath = $this->selectedPreset ?: ($this->image ?: null);

        if ($this->imageUpload) {
            $ext = $this->imageUpload->getClientOriginalExtension() ?: 'webp';
            $filename = 'recipe-'.Str::slug($this->name ?: 'dish').'-'.time().'.'.$ext;
            $this->imageUpload->storeAs('recipes/uploads', $filename, 'public');
            $imagePath = 'storage/recipes/uploads/'.$filename;
        }

        $data = [
            'name' => trim($this->name),
            'slug' => Str::slug($this->slug ?: $this->name),
            'tagline' => trim($this->tagline) ?: null,
            'country_id' => $this->country_id ?: null,
            'duration_minutes' => $this->duration_minutes ? (int) $this->duration_minutes : null,
            'image' => $imagePath,
            'thumbnail' => $this->thumbnail ?: null,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingRecipeId) {
            $recipe = Recipe::findOrFail($this->editingRecipeId);
            $recipe->update($data);
            $message = "Recipe \"{$recipe->name}\" updated successfully.";
        } else {
            $recipe = Recipe::create($data);
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
            'presets' => self::PRESET_RECIPES,
        ])->layout('layouts.admin', ['title' => 'Kitchen Recipes & Bundles']);
    }
}
