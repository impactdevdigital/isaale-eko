<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Models\StockNotification;
use App\Services\Analytics;
use App\Support\Cart;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts::store')]
class ProductPage extends Component
{
    #[Locked]
    public Product $product;

    public int $quantity = 1;

    public string $email = '';

    public bool $subscribed = false;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['defaultVariation', 'category', 'country']);

        if ($variation = $this->product->defaultVariation) {
            Analytics::track('view_item', [
                'currency' => config('shop.currency', 'CAD'),
                'value' => round($variation->price_cents / 100, 2),
                'items' => [Analytics::item($variation->setRelation('product', $this->product))],
            ]);
        }
    }

    public function increment(): void
    {
        $this->quantity = min($this->maxQuantity(), $this->quantity + 1);
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addToBasket(): void
    {
        if (! $this->product->inStock()) {
            return;
        }

        $this->dispatch('cart-add', variation: $this->product->defaultVariation->id, quantity: $this->quantity);
    }

    /**
     * Save or unsave the product; guests are asked to sign in first.
     */
    public function toggleWishlist(): void
    {
        if (! $user = Auth::user()) {
            $this->dispatch('sign-in-open');

            return;
        }

        $user->wishlist()->toggle($this->product->id);
    }

    /**
     * Sign up for a back-in-stock email.
     */
    public function notify(): void
    {
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        $key = 'stock-notify:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('email', 'Too many requests. Please try again in a minute.');

            return;
        }

        RateLimiter::hit($key);

        // Asking again after an earlier "back in stock" email starts a fresh request.
        StockNotification::updateOrCreate(
            ['product_id' => $this->product->id, 'email' => strtolower($this->email)],
            ['notified_at' => null],
        );

        $this->subscribed = true;
    }

    private function maxQuantity(): int
    {
        $stock = $this->product->defaultVariation?->stock_quantity;

        return min(Cart::MAX_QUANTITY, $stock ?? Cart::MAX_QUANTITY);
    }

    /**
     * Up to four in-stock products from the same category, topped up with bestsellers.
     */
    private function related()
    {
        $base = fn () => Product::forCard()
            ->whereKeyNot($this->product->id)
            ->available()
            ->orderByRaw('bestseller_rank is null')
            ->orderBy('bestseller_rank');

        $related = $this->product->category_id
            ? $base()->where('category_id', $this->product->category_id)->take(4)->get()
            : collect();

        return $related->concat(
            $base()->whereKeyNot($related->pluck('id')->all())->take(4 - $related->count())->get()
        );
    }

    /**
     * Description, sharing image and Product structured data, so the item
     * can show with its price and stock in Google results.
     */
    private function seo(Product $product): void
    {
        $variation = $product->defaultVariation;
        $images = array_values(array_filter(array_map([Seo::class, 'absolute'], $product->images())));
        $description = $product->localized_description
            ?: "Buy {$product->localized_name} online at Ìsàlèè Èkó, African and Caribbean groceries in Saint John, NB.";

        Seo::description($description)->type('product');
        if ($images) {
            Seo::image($images[0]);
        }

        $availability = match ($product->stockState()) {
            'in_stock' => 'https://schema.org/InStock',
            'low_stock' => 'https://schema.org/LimitedAvailability',
            default => 'https://schema.org/OutOfStock',
        };

        Seo::schema(array_filter([
            '@type' => 'Product',
            'name' => $product->localized_name,
            'description' => Str::limit(strip_tags((string) $description), 500),
            'image' => $images ?: null,
            'sku' => $variation?->sku ?: (string) $product->id,
            'category' => $product->category?->name,
            'countryOfOrigin' => $product->country?->name,
            'url' => route('product', $product),
            'offers' => $variation && $variation->price_cents > 0 ? [
                '@type' => 'Offer',
                'url' => route('product', $product),
                'priceCurrency' => config('shop.currency', 'CAD'),
                'price' => number_format($variation->price_cents / 100, 2, '.', ''),
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@type' => 'Organization', 'name' => Seo::SITE_NAME],
            ] : null,
        ]));

        Seo::breadcrumbs(array_values(array_filter([
            ['Home', route('home')],
            ['Shop', route('shop')],
            $product->category ? [$product->category->name, route('shop', ['category' => $product->category->slug])] : null,
            [$product->localized_name, null],
        ])));
    }

    public function render()
    {
        $product = $this->product;
        $this->seo($product);

        return view('livewire.store.product-page', [
            'variation' => $product->defaultVariation,
            'state' => $product->stockState(),
            'recipes' => $product->recipes()
                ->with(['products' => fn ($query) => $query->forCard()])
                ->orderBy('sort_order')
                ->take(3)
                ->get(),
            'related' => $this->related(),
            'stories' => $product->stories()->published()->latest('published_at')->take(3)->get(),
        ])->title($product->name);
    }
}
