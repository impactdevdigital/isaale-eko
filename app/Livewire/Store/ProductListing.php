<?php

namespace App\Livewire\Store;

use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Analytics;
use App\Support\Seo;
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

    /** Extra bands for links such as homepage banners; not shown as filters. */
    public const LINK_PRICES = [
        'under-10' => ['Under $10', null, 1000],
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

    /** What was last sent to analytics, so re-renders don't repeat it. */
    #[Locked]
    public array $tracked = ['q' => '', 'category' => '', 'country' => ''];

    public function mount(?Country $countryPage = null): void
    {
        abort_if($countryPage?->exists && ! $countryPage->is_listed, 404);

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
        $this->trackDiscovery($category, $products->total());

        if ($countryPage) {
            Seo::description($countryPage->banner_description
                ?: "Shop {$countryPage->name} groceries online at Ìsàlèè Èkó: familiar staples, pantry favourites and flavours from home, in Saint John, NB.")
                ->image($countryPage->banner_image);
        } elseif ($category) {
            Seo::description("Shop {$category->name} online at Ìsàlèè Èkó: African and Caribbean groceries in Saint John, NB, ready for pickup.")
                ->image($category->image);
        }

        return view('livewire.store.product-listing', [
            'products' => $products,
            'selectedCategory' => $category,
            'countryPage' => $countryPage,
            'filterCountry' => ! $countryPage && $this->country !== '' ? Country::where('slug', $this->country)->first() : null,
            'popular' => $countryPage ? $this->popularIn($countryPage) : collect(),
            // Menu groups, plus top-level Square categories that aren't in the menu.
            'categoryTree' => Category::roots()
                ->where(fn ($query) => $query->has('children')->orWhereHas('products', fn ($p) => $p->active()))
                ->with('children')->orderBy('name')->get(),
            'countries' => Country::listed()->whereHas('products', fn ($p) => $p->active())->orderBy('sort_order')->get(),
            'noSearchResults' => $this->q !== '' && $products->total() === 0 && ! $this->hasFilters(),
        ])->title($this->pageTitle($category, $countryPage));
    }

    /**
     * Search, search with no results, category and country choices: each
     * sent once when it changes.
     */
    private function trackDiscovery(?Category $category, int $results): void
    {
        if ($this->q !== '' && $this->q !== $this->tracked['q']) {
            Analytics::track('search', ['search_term' => $this->q, 'results' => $results]);

            if ($results === 0) {
                Analytics::track('search_no_results', ['search_term' => $this->q]);
            }
        }

        if ($category && $category->slug !== $this->tracked['category']) {
            Analytics::track('select_category', ['category' => $category->name]);
        }

        if ($this->country !== '' && $this->country !== $this->tracked['country']) {
            Analytics::track('select_country', ['country' => $this->country, 'source' => $this->countryPageId ? 'country_page' : 'filter']);
        }

        $this->tracked = ['q' => $this->q, 'category' => (string) $category?->slug, 'country' => $this->country];
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

        if ($band = self::PRICES[$this->price] ?? self::LINK_PRICES[$this->price] ?? null) {
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
