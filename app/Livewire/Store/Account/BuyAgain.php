<?php

namespace App\Livewire\Store\Account;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Products from the customer's confirmed orders, most recently bought first.
 */
#[Layout('layouts::store')]
#[Title('Buy again')]
class BuyAgain extends Component
{
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

        return view('livewire.store.account.buy-again', ['products' => $products]);
    }
}
