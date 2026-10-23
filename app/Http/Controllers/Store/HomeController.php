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
            'freshThisWeek' => $this->collection('fresh-this-week'),
            'moreFromTheShelves' => $this->collection('more-from-the-shelves'),
            'recipes' => Recipe::where('is_featured', true)
                ->orderBy('sort_order')
                ->with(['products' => fn ($query) => $query->forCard()])
                ->take(3)
                ->get(),
            'categories' => Category::featured()->get(),
            'countries' => Country::whereNotNull('image')->orderBy('sort_order')->get(),
        ]);
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
