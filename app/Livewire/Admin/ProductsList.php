<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

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
        $product->update(['is_featured' => !$product->is_featured]);
    }

    public function toggleStatus(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $newStatus = $product->status === 'active' ? 'hidden' : 'active';
        $product->update(['status' => $newStatus]);
    }

    public function updateItemClass(int $productId, string $class): void
    {
        if (!in_array($class, ['perishable', 'non_perishable', 'non_food'], true)) {
            return;
        }

        Product::whereKey($productId)->update(['item_class' => $class]);
    }

    public function render()
    {
        $query = Product::with(['category', 'subCategory', 'variations.snapshot'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($sq) use ($term) {
                    $sq->where('name', 'like', "%{$term}%")
                      ->orWhereHas('variations', fn ($v) => $v->where('sku', 'like', "%{$term}%"));
                });
            })
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->classFilter, fn ($q) => $q->where('item_class', $this->classFilter))
            ->when($this->stockFilter, function ($q) {
                match ($this->stockFilter) {
                    'in_stock' => $q->whereHas('variations', fn ($v) => $v->where('tracks_inventory', true)->whereHas('snapshot', fn ($s) => $s->where('state', 'in'))),
                    'low_stock' => $q->whereHas('variations', fn ($v) => $v->where('tracks_inventory', true)->whereHas('snapshot', fn ($s) => $s->where('state', 'low'))),
                    'out_of_stock' => $q->whereDoesntHave('variations', fn ($v) => $v->where('tracks_inventory', true)->whereHas('snapshot', fn ($s) => $s->whereIn('state', ['in', 'low']))),
                    'untracked' => $q->whereHas('variations', fn ($v) => $v->where('tracks_inventory', false)),
                    default => null,
                };
            });

        $products = $query->orderBy('name')->paginate(20);

        $counts = [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'featured' => Product::where('is_featured', true)->count(),
            'untracked' => Product::whereHas('variations', fn ($v) => $v->where('tracks_inventory', false))->count(),
        ];

        return view('livewire.admin.products-list', [
            'products' => $products,
            'categories' => Category::where('status', 'active')->orderBy('name')->get(),
            'counts' => $counts,
        ])->layout('layouts.admin', ['header' => 'Products & Inventory']);
    }
}
