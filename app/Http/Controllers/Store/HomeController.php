<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Recipe;
use App\Support\StorefrontContent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('store.home', [
            'heroSlides' => StorefrontContent::heroSlides(),
            'promoSlides' => StorefrontContent::promoSlides(),
            'freshThisWeek' => $this->atLeast($this->collection('fresh-this-week'), 8),
            'moreFromTheShelves' => $this->collection('more-from-the-shelves'),
            'recipes' => Recipe::where('is_featured', true)
                ->orderBy('sort_order')
                ->with(['products' => fn ($query) => $query->forCard()])
                ->take(3)
                ->get(),
            'categories' => Category::featured()->get(),
            'countries' => Country::listed()->whereNotNull('image')->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * The Fresh This Week block is two grids of four around a banner, so top
     * a short collection up with the newest in-stock-ready products.
     *
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function atLeast($products, int $count)
    {
        if ($products->count() >= $count) {
            return $products;
        }

        return $products->concat(
            Product::forCard()->whereNotIn('id', $products->pluck('id'))->latest()->take($count - $products->count())->get()
        );
    }

    /**
     * @return Collection<int, Product>
     */
    private function collection(string $slug)
    {
        return ProductCollection::where('slug', $slug)->first()
            ?->products()->forCard()->get()
            ?? Product::forCard()->latest()->take(9)->get();
    }
}
