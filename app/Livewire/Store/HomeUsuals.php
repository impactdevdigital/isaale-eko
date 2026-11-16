<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\Usuals;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * "Your usuals" on the home page, straight after the banner: what a
 * returning customer buys most often, with one tap to reorder the lot.
 * Guests and customers without recent orders see nothing.
 */
class HomeUsuals extends Component
{
    public function reorderUsuals(): void
    {
        if (! $user = Auth::user()) {
            return;
        }

        $added = app(Usuals::class)->addToCart($user);

        $this->dispatch('cart-updated', count: app(Cart::class)->count());

        if ($added > 0) {
            $this->dispatch('cart-open');
        }
    }

    public function render()
    {
        $user = Auth::user();
        $usuals = $user ? app(Usuals::class)->repricedFor($user) : collect();

        $ids = $usuals->map(fn (BasketLine $line) => $line->variation?->product_id)->filter()->unique()->values();

        $products = Product::forCard()->whereIn('id', $ids)->get()
            ->sortBy(fn (Product $product) => $ids->search($product->id))
            ->values();

        return view('livewire.store.home-usuals', [
            'firstName' => $user ? str($user->name)->before(' ')->toString() : null,
            'products' => $products,
            'canReorder' => $usuals->contains->available(),
            'usualsTotal' => $usuals->sum(fn (BasketLine $line) => $line->nowTotalCents()),
        ]);
    }
}
