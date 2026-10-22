<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Models\StockNotification;
use App\Support\Cart;
use Illuminate\Support\Facades\RateLimiter;
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

        StockNotification::firstOrCreate([
            'product_id' => $this->product->id,
            'email' => strtolower($this->email),
        ]);

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
            $base()->whereKeyNot($related->modelKeys())->take(4 - $related->count())->get()
        );
    }

    public function render()
    {
        $product = $this->product;

        return view('livewire.store.product-page', [
            'variation' => $product->defaultVariation,
            'state' => $product->stockState(),
            'recipes' => $product->recipes()
                ->with(['products' => fn ($query) => $query->forCard()])
                ->orderBy('sort_order')
                ->take(3)
                ->get(),
            'related' => $this->related(),
        ])->title($product->name);
    }
}
