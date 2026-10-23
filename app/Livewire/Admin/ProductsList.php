<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Square\CatalogSync;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Products & inventory for staff. Names, prices and stock come from Square
 * (square:sync); here staff set what Square doesn't know: hidden online,
 * featured, and the item class used for returns.
 */
class ProductsList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $stockFilter = '';

    public string $classFilter = '';

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

    public function toggleFeatured(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_featured' => ! $product->is_featured]);
    }

    /**
     * Hide or show online. The sync never changes this, so it sticks.
     */
    public function toggleHidden(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_hidden' => ! $product->is_hidden]);
    }

    public function updateItemClass(int $productId, string $class): void
    {
        if (! array_key_exists($class, Product::ITEM_CLASSES)) {
            return;
        }

        Product::whereKey($productId)->update(['item_class' => $class]);
    }

    public function render()
    {
        $low = (int) Setting::get('low_stock_threshold', 5);

        $query = Product::with(['category.parent', 'variations'])
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($sq) => $sq
                    ->whereLike('name', $term)
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

        return view('livewire.admin.products-list', [
            'products' => $query->orderBy('name')->paginate(20),
            'categories' => Category::roots()->with('children')->get(),
            'counts' => $counts,
            'low' => $low,
            'lastSync' => cache(CatalogSync::STATE_KEY),
        ])->layout('layouts.admin', ['header' => 'Products & Inventory']);
    }
}
