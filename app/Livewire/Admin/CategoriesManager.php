<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Services\Square\CatalogSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Categories & Sub-Categories Manager.
 * Mirrors Square POS catalog hierarchy (Parent Departments, nested Sub-Categories,
 * Square IDs, and product counts) for store staff.
 */
class CategoriesManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** Preset category food photos exported from the Figma library */
    public const PRESET_IMAGES = [
        ['name' => 'Flours & Baking', 'path' => 'images/store/subcategories/flours-and-baking.webp', 'emoji' => '🌾'],
        ['name' => 'Grains & Cereals', 'path' => 'images/store/subcategories/grains-and-cereals.webp', 'emoji' => '🌾'],
        ['name' => 'Rice & Beans', 'path' => 'images/store/categories/rice-and-beans.webp', 'emoji' => '🫘'],
        ['name' => 'Meat & Fish', 'path' => 'images/store/categories/meat-and-fish.webp', 'emoji' => '🍗'],
        ['name' => 'Fresh Produce', 'path' => 'images/store/categories/fresh-produce.webp', 'emoji' => '🥬'],
        ['name' => 'Vegetables', 'path' => 'images/store/subcategories/vegetables.webp', 'emoji' => '🥬'],
        ['name' => 'Spices & Seasonings', 'path' => 'images/store/subcategories/spices-and-seasonings.webp', 'emoji' => '🌶️'],
        ['name' => 'Cooking Oils', 'path' => 'images/store/subcategories/cooking-oils.webp', 'emoji' => '🫒'],
        ['name' => 'Seasonings', 'path' => 'images/store/categories/seasonings.webp', 'emoji' => '🧂'],
        ['name' => 'Sauces & Pastes', 'path' => 'images/store/categories/sauces.webp', 'emoji' => '🥫'],
        ['name' => 'Snacks & Pastries', 'path' => 'images/store/categories/snacks.webp', 'emoji' => '🍿'],
        ['name' => 'Drinks & Beverages', 'path' => 'images/store/categories/drinks.webp', 'emoji' => '🥤'],
        ['name' => 'Pantry Staples', 'path' => 'images/store/categories/pantry-staples.webp', 'emoji' => '📦'],
        ['name' => 'Beauty & Home', 'path' => 'images/store/categories/beauty-and-home.webp', 'emoji' => '🧼'],
    ];

    /** Quick selectable grocery emoji palette for store staff */
    public const QUICK_EMOJIS = [
        '🌾' => 'Grains',
        '🫘' => 'Beans',
        '🌶️' => 'Pepper',
        '🍗' => 'Meat',
        '🐟' => 'Fish',
        '🥬' => 'Greens',
        '🥔' => 'Tubers',
        '🫒' => 'Oils',
        '🥤' => 'Drinks',
        '🍿' => 'Snacks',
        '🥫' => 'Cans',
        '🧼' => 'Care',
    ];

    public string $search = '';

    /** Active view mode: 'hierarchy' (Square POS parent-child), 'all_sub' (all sub-categories), 'parents' (parent departments only) */
    public string $viewMode = 'hierarchy';

    public string $parentFilter = '';

    public string $featuredFilter = '';

    public string $syncFilter = '';

    /** @var array<int, int> IDs of parent categories currently expanded in hierarchy view */
    public array $expandedParents = [];

    // Modal State
    public bool $showModal = false;

    public ?int $editingCategoryId = null;

    // Form fields
    public string $name = '';

    public string $slug = '';

    public ?int $parent_id = null;

    public string $square_id = '';

    public string $emoji = '';

    public string $image = '';

    /** Temporary file upload instance */
    public $imageUpload = null;

    /** Path of image chosen from store library presets */
    public string $selectedPreset = '';

    /** Active image selector tab: 'presets' | 'upload' */
    public string $imageSourceTab = 'presets';

    /** Toggle for advanced technical fields (slug, Square ID, sort order) */
    public bool $showAdvanced = false;

    public bool $is_featured = false;

    public int $sort_order = 0;

    // Delete confirmation modal state
    public bool $showDeleteModal = false;

    public ?int $deletingCategoryId = null;

    public string $deletingCategoryName = '';

    public int $deletingCategoryHasProducts = 0;

    public int $deletingCategoryHasChildren = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'viewMode' => ['except' => 'hierarchy'],
        'parentFilter' => ['except' => ''],
        'featuredFilter' => ['except' => ''],
        'syncFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Expand all root departments by default for immediate clarity
        $this->expandedParents = Category::roots()->pluck('id')->all();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedViewMode(): void
    {
        $this->resetPage();
    }

    public function updatedParentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFeaturedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSyncFilter(): void
    {
        $this->resetPage();
    }

    public function updatedName(): void
    {
        if (! $this->editingCategoryId && empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function toggleExpand(int $parentId): void
    {
        if (in_array($parentId, $this->expandedParents, true)) {
            $this->expandedParents = array_values(array_diff($this->expandedParents, [$parentId]));
        } else {
            $this->expandedParents[] = $parentId;
        }
    }

    public function expandAll(): void
    {
        $this->expandedParents = Category::roots()->pluck('id')->all();
    }

    public function collapseAll(): void
    {
        $this->expandedParents = [];
    }

    public function toggleFeatured(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->update(['is_featured' => ! $category->is_featured]);

        session()->flash('success', "Updated featured spotlight for '{$category->name}'.");
    }

    public function selectPreset(string $path): void
    {
        $this->selectedPreset = $path;
        $this->image = $path;
        $this->imageUpload = null;

        $preset = collect(self::PRESET_IMAGES)->firstWhere('path', $path);
        if ($preset && empty($this->emoji)) {
            $this->emoji = $preset['emoji'];
        }
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

    public function openCreateModal(?int $parentId = null): void
    {
        $this->resetValidation();
        $this->editingCategoryId = null;
        $this->name = '';
        $this->slug = '';
        $this->parent_id = $parentId;
        $this->square_id = '';
        $this->emoji = '';
        $this->image = '';
        $this->imageUpload = null;
        $this->selectedPreset = '';
        $this->imageSourceTab = 'presets';
        $this->showAdvanced = false;
        $this->is_featured = false;
        $this->sort_order = 0;

        $this->showModal = true;
    }

    public function openEditModal(int $categoryId): void
    {
        $this->resetValidation();
        $category = Category::findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->parent_id = $category->parent_id;
        $this->square_id = $category->square_id ?? '';
        $this->emoji = $category->emoji ?? '';
        $this->image = $category->image ?? '';
        $this->imageUpload = null;
        $this->selectedPreset = $category->image ?? '';
        $this->imageSourceTab = 'presets';
        $this->showAdvanced = false;
        $this->is_featured = (bool) $category->is_featured;
        $this->sort_order = (int) ($category->sort_order ?? 0);

        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,'.($this->editingCategoryId ?? 'NULL'),
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($this->editingCategoryId && (int) $value === (int) $this->editingCategoryId) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
            'square_id' => 'nullable|string|max:255|unique:categories,square_id,'.($this->editingCategoryId ?? 'NULL'),
            'emoji' => 'nullable|string|max:16',
            'image' => 'nullable|string|max:255',
            'imageUpload' => 'nullable|mimes:jpeg,jpg,png,webp,svg|max:5120',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];

        $this->validate($rules);

        $imagePath = $this->selectedPreset ?: ($this->image ?: null);

        if ($this->imageUpload) {
            $ext = $this->imageUpload->getClientOriginalExtension() ?: 'webp';
            $filename = 'category-'.Str::slug($this->name ?: 'item').'-'.time().'.'.$ext;
            $this->imageUpload->storeAs('categories/uploads', $filename, 'public');
            $imagePath = 'storage/categories/uploads/'.$filename;
        }

        $data = [
            'name' => trim($this->name),
            'slug' => Str::slug($this->slug ?: $this->name),
            'parent_id' => $this->parent_id ?: null,
            'square_id' => trim($this->square_id) ?: null,
            'emoji' => trim($this->emoji) ?: null,
            'image' => $imagePath,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $category->update($data);
            $message = "Category '{$category->name}' updated successfully.";
        } else {
            $category = Category::create($data);
            $message = "Category '{$category->name}' created successfully.";

            // If added under a parent, ensure that parent is expanded
            if ($category->parent_id && ! in_array($category->parent_id, $this->expandedParents, true)) {
                $this->expandedParents[] = $category->parent_id;
            }
        }

        $this->showModal = false;
        session()->flash('success', $message);
    }

    public function confirmDelete(int $categoryId): void
    {
        $category = Category::withCount(['children', 'products'])->findOrFail($categoryId);

        $this->deletingCategoryId = $category->id;
        $this->deletingCategoryName = $category->name;
        $this->deletingCategoryHasProducts = $category->products_count;
        $this->deletingCategoryHasChildren = $category->children_count;

        $this->showDeleteModal = true;
    }

    public function deleteCategory(): void
    {
        if (! $this->deletingCategoryId) {
            return;
        }

        $category = Category::withCount(['children', 'products'])->findOrFail($this->deletingCategoryId);

        if ($category->products_count > 0) {
            session()->flash('error', "Cannot delete '{$category->name}' because {$category->products_count} product(s) are assigned to it. Please reassign products first.");
            $this->showDeleteModal = false;

            return;
        }

        if ($category->children_count > 0) {
            session()->flash('error', "Cannot delete '{$category->name}' because it contains {$category->children_count} sub-category(ies). Please reassign or delete sub-categories first.");
            $this->showDeleteModal = false;

            return;
        }

        $name = $category->name;
        $category->delete();

        $this->showDeleteModal = false;
        session()->flash('success', "Category '{$name}' was deleted successfully.");
    }

    public function render()
    {
        // KPI statistics
        $counts = [
            'total' => Category::count(),
            'roots' => Category::whereNull('parent_id')->count(),
            'subcategories' => Category::whereNotNull('parent_id')->count(),
            'featured' => Category::where('is_featured', true)->count(),
            'synced' => Category::whereNotNull('square_id')->count(),
        ];

        // All root departments for dropdowns & selectors
        $rootDepartments = Category::roots()->get(['id', 'name', 'square_id']);

        $lastSync = Cache::get(CatalogSync::STATE_KEY);
        $term = '%'.trim($this->search).'%';
        $hasSearch = trim($this->search) !== '';

        if ($this->viewMode === 'hierarchy') {
            // Square POS Hierarchical Tree (Parent Categories -> nested Sub-Categories)
            $parentsQuery = Category::roots()
                ->withCount('products')
                ->with(['children' => function ($q) use ($hasSearch, $term) {
                    $q->withCount('products')->orderBy('sort_order');
                    if ($hasSearch) {
                        $q->where(function ($sub) use ($term) {
                            $sub->whereLike('name', $term)
                                ->orWhereLike('slug', $term)
                                ->orWhereLike('square_id', $term);
                        });
                    }
                    if ($this->featuredFilter === 'featured') {
                        $q->where('is_featured', true);
                    } elseif ($this->featuredFilter === 'standard') {
                        $q->where('is_featured', false);
                    }
                    if ($this->syncFilter === 'synced') {
                        $q->whereNotNull('square_id');
                    } elseif ($this->syncFilter === 'local') {
                        $q->whereNull('square_id');
                    }
                }])
                ->when($this->parentFilter, fn ($q) => $q->where('id', $this->parentFilter))
                ->when($this->featuredFilter === 'featured', fn ($q) => $q->where('is_featured', true))
                ->when($this->featuredFilter === 'standard', fn ($q) => $q->where('is_featured', false))
                ->when($this->syncFilter === 'synced', fn ($q) => $q->whereNotNull('square_id'))
                ->when($this->syncFilter === 'local', fn ($q) => $q->whereNull('square_id'))
                ->when($hasSearch, function ($q) use ($term) {
                    $q->where(function ($root) use ($term) {
                        $root->whereLike('name', $term)
                            ->orWhereLike('slug', $term)
                            ->orWhereLike('square_id', $term)
                            ->orWhereHas('children', function ($child) use ($term) {
                                $child->whereLike('name', $term)
                                    ->orWhereLike('slug', $term)
                                    ->orWhereLike('square_id', $term);
                            });
                    });
                })
                ->orderBy('sort_order');

            $categories = $parentsQuery->get();

            return view('livewire.admin.categories-manager', [
                'counts' => $counts,
                'rootDepartments' => $rootDepartments,
                'categories' => $categories,
                'lastSync' => $lastSync,
                'presets' => self::PRESET_IMAGES,
                'quickEmojis' => self::QUICK_EMOJIS,
            ])->layout('layouts.admin', ['title' => 'Categories & Sub-Categories']);
        }

        if ($this->viewMode === 'all_sub') {
            // Flat list of all sub-categories with their parent department
            $subQuery = Category::whereNotNull('parent_id')
                ->with(['parent'])
                ->withCount('products')
                ->when($hasSearch, function ($q) use ($term) {
                    $q->where(function ($sq) use ($term) {
                        $sq->whereLike('name', $term)
                            ->orWhereLike('slug', $term)
                            ->orWhereLike('square_id', $term)
                            ->orWhereHas('parent', fn ($p) => $p->whereLike('name', $term));
                    });
                })
                ->when($this->parentFilter, fn ($q) => $q->where('parent_id', $this->parentFilter))
                ->when($this->featuredFilter === 'featured', fn ($q) => $q->where('is_featured', true))
                ->when($this->featuredFilter === 'standard', fn ($q) => $q->where('is_featured', false))
                ->when($this->syncFilter === 'synced', fn ($q) => $q->whereNotNull('square_id'))
                ->when($this->syncFilter === 'local', fn ($q) => $q->whereNull('square_id'))
                ->orderBy('sort_order')
                ->paginate(20);

            return view('livewire.admin.categories-manager', [
                'counts' => $counts,
                'rootDepartments' => $rootDepartments,
                'subCategories' => $subQuery,
                'lastSync' => $lastSync,
                'presets' => self::PRESET_IMAGES,
                'quickEmojis' => self::QUICK_EMOJIS,
            ])->layout('layouts.admin', ['title' => 'Categories & Sub-Categories']);
        }

        // 'parents' mode: Top-level Parent Departments only
        $parentsQuery = Category::roots()
            ->withCount(['children', 'products'])
            ->with('children')
            ->when($hasSearch, function ($q) use ($term) {
                $q->where(function ($sq) use ($term) {
                    $sq->whereLike('name', $term)
                        ->orWhereLike('slug', $term)
                        ->orWhereLike('square_id', $term);
                });
            })
            ->when($this->parentFilter, fn ($q) => $q->where('id', $this->parentFilter))
            ->when($this->featuredFilter === 'featured', fn ($q) => $q->where('is_featured', true))
            ->when($this->featuredFilter === 'standard', fn ($q) => $q->where('is_featured', false))
            ->when($this->syncFilter === 'synced', fn ($q) => $q->whereNotNull('square_id'))
            ->when($this->syncFilter === 'local', fn ($q) => $q->whereNull('square_id'))
            ->orderBy('sort_order')
            ->paginate(20);

        return view('livewire.admin.categories-manager', [
            'counts' => $counts,
            'rootDepartments' => $rootDepartments,
            'parentCategories' => $parentsQuery,
            'lastSync' => $lastSync,
            'presets' => self::PRESET_IMAGES,
            'quickEmojis' => self::QUICK_EMOJIS,
        ])->layout('layouts.admin', ['title' => 'Categories & Sub-Categories']);
    }
}
