<?php

namespace App\Livewire\Store;

use App\Support\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    #[On('cart-add')]
    public function add(int $variation, int $quantity = 1): void
    {
        if (app(Cart::class)->add($variation, $quantity)) {
            $this->dispatch('cart-open');
        }

        $this->broadcastCount();
    }

    /**
     * @param  array<int, int>  $variations
     */
    #[On('cart-add-many')]
    public function addMany(array $variations): void
    {
        $cart = app(Cart::class);
        $added = false;

        foreach (array_slice(array_unique($variations), 0, 50) as $id) {
            $added = $cart->add((int) $id) || $added;
        }

        if ($added) {
            $this->dispatch('cart-open');
        }

        $this->broadcastCount();
    }

    public function increment(int $variation): void
    {
        $cart = app(Cart::class);
        $cart->setQuantity($variation, $cart->quantity($variation) + 1);
        $this->broadcastCount();
    }

    public function decrement(int $variation): void
    {
        $cart = app(Cart::class);
        $cart->setQuantity($variation, $cart->quantity($variation) - 1);
        $this->broadcastCount();
    }

    public function toggle(int $variation): void
    {
        app(Cart::class)->toggle($variation);
    }

    public function remove(int $variation): void
    {
        app(Cart::class)->remove($variation);
        $this->broadcastCount();
    }

    private function broadcastCount(): void
    {
        $this->dispatch('cart-updated', count: app(Cart::class)->count());
    }

    public function render()
    {
        $cart = app(Cart::class);
        $lines = $cart->lines();

        return view('livewire.store.cart-drawer', [
            'lines' => $lines,
            'subtotal' => $cart->subtotalCents($lines),
            'canCheckout' => $lines->contains('selected', true),
        ]);
    }
}
