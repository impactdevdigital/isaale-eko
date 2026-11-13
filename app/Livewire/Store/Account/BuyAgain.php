<?php

namespace App\Livewire\Store\Account;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\BasketRepricer;
use App\Services\Baskets\Usuals;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The customer's usuals (one tap to reorder), a way back into their last
 * order at today's prices, and everything they've bought before.
 */
#[Layout('layouts::store')]
#[Title('Buy again')]
class BuyAgain extends Component
{
    /**
     * Add every usual that can be bought today, at its usual quantity.
     */
    public function reorderUsuals(): void
    {
        $cart = app(Cart::class);
        $added = 0;

        foreach ($this->usuals() as $line) {
            if ($line->available() && $cart->add($line->variation->id, $line->quantity)) {
                $added++;
            }
        }

        $this->dispatch('cart-updated', count: $cart->count());

        if ($added > 0) {
            $this->dispatch('cart-open');
        }
    }

    private function usuals()
    {
        return app(BasketRepricer::class)->reprice(app(Usuals::class)->linesFor(Auth::user()));
    }

    public function render()
    {
        $ids = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', Auth::id())
            ->whereIn('orders.status', [Order::STATUS_PLACED, Order::STATUS_PAID])
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->orderByRaw('MAX(orders.placed_at) DESC')
            ->limit(48)
            ->pluck('order_items.product_id');

        $products = Product::forCard()->whereIn('id', $ids)->get()
            ->sortBy(fn (Product $product) => $ids->search($product->id))
            ->values();

        $usuals = $this->usuals();

        return view('livewire.store.account.buy-again', [
            'products' => $products,
            'usuals' => $usuals,
            'usualsTotal' => $usuals->sum(fn (BasketLine $line) => $line->nowTotalCents()),
            'lastOrder' => Auth::user()->orders()->whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])->first(),
        ]);
    }
}
