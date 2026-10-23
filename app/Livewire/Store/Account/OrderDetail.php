<?php

namespace App\Livewire\Store\Account;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Order details (Figma 06.05): status tracker, items, fulfilment and payment.
 * Only the owner can open it; anyone else gets a 404.
 */
#[Layout('layouts::store')]
class OrderDetail extends Component
{
    #[Locked]
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless(Auth::user()?->is($order->user) && $order->status !== Order::STATUS_PAYMENT_FAILED, 404);

        $this->order = $order;
    }

    public function buyAgain(): void
    {
        $this->dispatch('cart-add-many', variations: $this->order->items->pluck('product_variation_id')->filter()->values()->all());
    }

    public function cancel(): void
    {
        if ($this->order->canBeCancelled()) {
            $this->order->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]);
        }
    }

    public function render()
    {
        return view('livewire.store.account.order-detail', ['order' => $this->order->load('items')])
            ->title('Order '.$this->order->number);
    }
}
