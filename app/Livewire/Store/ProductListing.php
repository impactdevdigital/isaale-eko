<?php

namespace App\Livewire\Store;

use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Shop listing for /shop (all products, category, search, sort presets) and
 * /shop/country/{country}. Every filter lives in the query string.
 */
#[Layout('layouts::store')]
class ProductListing extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    public const SORTS = [
        'recommended' => 'Recommended',
        'trending' => 'Trending',
        'new' => 'New Arrivals',
        'bestsellers' => 'Best Sellers',
        'discounted' => 'Discounted',
        'hot' => 'Hot',
    ];

    /**
     * Price bands in cents: [min, max).
     */
    public const PRICES = [
        'under-5' => ['Under $5', null, 500],
        '5-10' => ['$5 – $10', 500, 1000],
        '10-15' => ['$10 – $15', 1000, 1500],
        'over-15' => ['Above $15', 1500, null],
    ];

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $country = '';

    #[Url(except: '')]
    public string $price = '';

    #[Url(except: '')]
    public string $availability = '';

    #[Url(except: 'recommended')]
    public string $sort = 'recommended';

    /**
     * Set on /shop/country/{country}: the page is about this country.
     */
    #[Locked]
    public ?int $countryPageId = null;

    public function mount(?Country $countryPage = null): void
    {
        if ($countryPage?->exists) {
            $this->countryPageId = $countryPage->id;
            $this->country = $countryPage->slug;
        }

        $this->q = trim($this->q);
        $this->sort = array_key_exists($this->sort, self::SORTS) ? $this->sort : 'recommended';
    }

    public function updated(string $property): void
    {
        if ($property === 'country' && $this->countryPageId) {
            $this->redirect($this->country ? route('shop.country', $this->country) : route('shop'), navigate: false);

            return;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('category', 'price', 'availability', 'sort');

        if (! $this->countryPageId) {
            $this->reset('country');
        }

        $this->resetPage();
    }

    public function render()
    {
        $countryPage = $this->countryPageId ? Country::find($this->countryPageId) : null;
        $category = $this->category ? Category::where('slug', $this->category)->first() : null;
        $products = $this->query($category)->paginate(self::PER_PAGE);

        return view('livewire.store.product-listing', [
            'products' => $products,
            'selectedCategory' => $category,
            'countryPage' => $countryPage,
            'popular' => $countryPage ? $this->popularIn($countryPage) : collect(),
            'categoryTree' => Category::roots()->has('children')->with('children')->get(),
            'countries' => Country::orderBy('sort_order')->get(),
            'noSearchResults' => $this->q !== '' && $products->total() === 0 && ! $this->hasFilters(),
        ])->title($this->pageTitle($category, $countryPage));
    }

    /**
     * @return Builder<Product>
     */
    private function query(?Category $category): Builder
    {
        $query = Product::forCard()->with('category');

        if ($this->q !== '') {
            $query->search($this->q);
        }

        if ($category) {
            $query->whereIn('category_id', $category->children()->pluck('id')->push($category->id));
        } elseif ($this->category) {
            $query->whereRaw('1 = 0');
        }

        if ($this->country) {
            $query->whereHas('country', fn ($query) => $query->where('slug', $this->country));
        }

        if ($band = self::PRICES[$this->price] ?? null) {
            $query->priceBetween($band[1], $band[2]);
        }

        if ($this->availability === 'in-stock') {
            $query->available();
        } elseif ($this->availability === 'out-of-stock') {
            $query->available(false);
        }

        return match ($this->sort) {
            'trending' => $query->trending()->orderByDesc('is_trending')->orderBy('name'),
            'new' => $query->where('first_seen_at', '>=', now()->subDays((int) Setting::get('new_arrival_days', 21)))->latest('first_seen_at')->orderBy('id'),
            'bestsellers' => $query->whereNotNull('bestseller_rank')->orderBy('bestseller_rank'),
            'discounted' => $query->discounted()->orderByDesc('discount_estimate')->orderBy('name'),
            'hot' => $query->where('is_hot', true)->orderBy('name'),
            // Ranked bestsellers first, then everything else by name.
            default => $query->orderByRaw('bestseller_rank is null')->orderBy('bestseller_rank')->orderBy('name'),
        };
    }

    /**
     * Best-known products from the country, for the "Popular ... favourites" row.
     */
    private function popularIn(Country $country)
    {
        return Product::forCard()
            ->where('country_id', $country->id)
            ->where(fn ($query) => $query->whereNotNull('bestseller_rank')->orWhere('is_trending', true)->orWhere('is_hot', true)->orWhereNotNull('badge_label'))
            ->orderByRaw('bestseller_rank is null')
            ->orderBy('bestseller_rank')
            ->take(15)
            ->get();
    }

    public function hasFilters(): bool
    {
        return $this->category !== '' || $this->price !== '' || $this->availability !== ''
            || $this->sort !== 'recommended' || ($this->country !== '' && ! $this->countryPageId);
    }

    private function pageTitle(?Category $category, ?Country $countryPage): string
    {
        return match (true) {
            $this->q !== '' => "Search results for “{$this->q}”",
            $countryPage !== null => "Shop {$countryPage->name}",
            $category !== null => $category->name,
            default => 'All products',
        };
    }

    /**
     * "1–15 of 200" for the pager.
     */
    public static function rangeLabel(LengthAwarePaginator $page): string
    {
        return $page->total()
            ? "{$page->firstItem()}–{$page->lastItem()} of {$page->total()}"
            : '0 of 0';
    }
}
