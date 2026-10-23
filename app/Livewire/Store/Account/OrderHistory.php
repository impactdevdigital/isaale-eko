<?php

namespace App\Livewire\Store\Account;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Order history (Figma 06.04): status tabs, search by order number or
 * product, Buy again and pay-at-pickup cancellation.
 */
#[Layout('layouts::store')]
#[Title('Order history')]
class OrderHistory extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $tab = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        if (! array_key_exists($this->tab, Order::TABS)) {
            $this->tab = 'all';
        }
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function buyAgain(int $orderId): void
    {
        $order = $this->orders()->with('items')->find($orderId);

        if ($order) {
            $this->dispatch('cart-add-many', variations: $order->items->pluck('product_variation_id')->filter()->values()->all());
        }
    }

    public function cancel(int $orderId): void
    {
        $order = $this->orders()->find($orderId);

        if ($order?->canBeCancelled()) {
            $order->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]);
        }
    }

    private function orders()
    {
        return Auth::user()->orders()->visibleToCustomer();
    }

    public function render()
    {
        $term = trim($this->search);

        $orders = $this->orders()
            ->inTab($this->tab)
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('number', 'like', '%'.$term.'%')
                ->orWhereHas('items', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))))
            ->with('items')
            ->paginate(10);

        return view('livewire.store.account.order-history', [
            'orders' => $orders,
            'unpaidCount' => $this->orders()->inTab('unpaid')->count(),
        ]);
    }
}
