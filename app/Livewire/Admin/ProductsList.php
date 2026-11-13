<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Square\CatalogSync;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Products & inventory for staff. Names, prices and stock come from Square
 * (square:sync); here staff inspect complete product details, set what Square
 * doesn't know (hidden online, featured, French name and description, and
 * the item class used for returns policy).
 */
class ProductsList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $stockFilter = '';

    public string $classFilter = '';

    // Product Inspection Drawer & Editing State
    public ?int $selectedProductId = null;

    public string $editNameFr = '';

    public string $editDescriptionFr = '';

    public string $editDescription = '';

    public string $editItemClass = 'perishable';

    public bool $editIsFeatured = false;

    public bool $editIsHidden = false;

    public ?int $editCategoryId = null;

    public ?int $editCountryId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'stockFilter' => ['except' => ''],
        'classFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClassFilter(): void
    {
        $this->resetPage();
    }

    public function viewProduct(int $productId): void
    {
        $this->selectedProductId = $productId;
        $product = Product::findOrFail($productId);

        $this->editNameFr = (string) ($product->name_fr ?? '');
        $this->editDescriptionFr = (string) ($product->description_fr ?? '');
        $this->editDescription = (string) ($product->description ?? '');
        $this->editItemClass = $product->item_class ?: 'perishable';
        $this->editIsFeatured = (bool) $product->is_featured;
        $this->editIsHidden = (bool) $product->is_hidden;
        $this->editCategoryId = $product->category_id;
        $this->editCountryId = $product->country_id;
    }

    public function closeDrawer(): void
    {
        $this->selectedProductId = null;
    }

    public function saveProduct(): void
    {
        if (! $this->selectedProductId) {
            return;
        }

        $this->validate([
            'editNameFr' => 'nullable|string|max:255',
            'editDescriptionFr' => 'nullable|string|max:5000',
            'editDescription' => 'nullable|string|max:5000',
            'editItemClass' => 'required|in:perishable,non_perishable,non_food',
            'editIsFeatured' => 'boolean',
            'editIsHidden' => 'boolean',
            'editCategoryId' => 'nullable|exists:categories,id',
            'editCountryId' => 'nullable|exists:countries,id',
        ]);

        $product = Product::findOrFail($this->selectedProductId);
        $product->update([
            'name_fr' => trim($this->editNameFr) ?: null,
            'description_fr' => trim($this->editDescriptionFr) ?: null,
            'description' => trim($this->editDescription) ?: null,
            'item_class' => $this->editItemClass,
            'is_featured' => $this->editIsFeatured,
            'is_hidden' => $this->editIsHidden,
            'category_id' => $this->editCategoryId ?: null,
            'country_id' => $this->editCountryId ?: null,
        ]);

        session()->flash('success', "Product '{$product->name}' details and French fields updated successfully.");
    }

    public function toggleFeatured(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_featured' => ! $product->is_featured]);
        if ($this->selectedProductId === $productId) {
            $this->editIsFeatured = (bool) $product->is_featured;
        }
    }

    /**
     * Hide or show online. The sync never changes this, so it sticks.
     */
    public function toggleHidden(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_hidden' => ! $product->is_hidden]);
        if ($this->selectedProductId === $productId) {
            $this->editIsHidden = (bool) $product->is_hidden;
        }
    }

    public function updateItemClass(int $productId, string $class): void
    {
        if (! array_key_exists($class, Product::ITEM_CLASSES)) {
            return;
        }

        Product::whereKey($productId)->update(['item_class' => $class]);
        if ($this->selectedProductId === $productId) {
            $this->editItemClass = $class;
        }
    }

    public function render()
    {
        $low = (int) Setting::get('low_stock_threshold', 5);

        $query = Product::with(['category.parent', 'country', 'variations'])
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($sq) => $sq
                    ->whereLike('name', $term)
                    ->orWhereLike('name_fr', $term)
                    ->orWhereHas('variations', fn ($v) => $v->whereLike('sku', $term)));
            })
            // A category includes its sub-categories.
            ->when($this->categoryFilter, fn ($q) => $q->whereIn('category_id',
                Category::where('id', $this->categoryFilter)->orWhere('parent_id', $this->categoryFilter)->pluck('id')))
            ->when($this->classFilter, fn ($q) => $q->where('item_class', $this->classFilter))
            ->when($this->stockFilter, function ($q) use ($low) {
                match ($this->stockFilter) {
                    'in_stock' => $q->whereHas('variations', fn ($v) => $v->where('stock_quantity', '>', $low)),
                    'low_stock' => $q->whereHas('variations', fn ($v) => $v->whereBetween('stock_quantity', [1, $low])),
                    'out_of_stock' => $q->whereDoesntHave('variations', fn ($v) => $v->where(fn ($w) => $w->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))),
                    'untracked' => $q->whereHas('variations', fn ($v) => $v->whereNull('stock_quantity')),
                    default => null,
                };
            });

        $counts = [
            'total' => Product::count(),
            'active' => Product::active()->count(),
            'featured' => Product::where('is_featured', true)->count(),
            'untracked' => Product::whereHas('variations', fn ($v) => $v->whereNull('stock_quantity'))->count(),
        ];

        $selectedProduct = $this->selectedProductId
            ? Product::with(['category.parent', 'country', 'variations', 'recipes'])->find($this->selectedProductId)
            : null;

        return view('livewire.admin.products-list', [
            'products' => $query->orderBy('name')->paginate(20),
            'categories' => Category::roots()->with('children')->get(),
            'countries' => Country::listed()->orderBy('name')->get(),
            'counts' => $counts,
            'low' => $low,
            'lastSync' => cache(CatalogSync::STATE_KEY),
            'selectedProduct' => $selectedProduct,
        ])->layout('layouts.admin', ['header' => 'Products & Inventory']);
    }
}
