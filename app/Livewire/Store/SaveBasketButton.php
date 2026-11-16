<?php

namespace App\Livewire\Store;

use App\Models\Order;
use App\Models\SavedBasket;
use App\Services\Baskets\SaveBasket;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Save this basket": a clear way to keep a basket (from the cart or a past
 * order) as a named list to rebuild later. Guests are asked to sign in;
 * accounts stay optional for checkout.
 */
class SaveBasketButton extends Component
{
    /** Save this order's items instead of the cart. */
    #[Locked]
    public ?int $orderId = null;

    /** card (cart page) or inline (order pages). */
    #[Locked]
    public string $variant = 'card';

    public string $basketName = '';

    /** The name of the basket just saved, to confirm it. */
    public ?string $savedAs = null;

    public function mount(?int $orderId = null, string $variant = 'card'): void
    {
        $this->orderId = $orderId;
        $this->variant = $variant;
        $this->basketName = $this->order() ? 'Order '.$this->order()->number : '';
    }

    public function save(): void
    {
        if (! $user = Auth::user()) {
            $this->dispatch('sign-in-open');

            return;
        }

        $this->validate(['basketName' => ['required', 'string', 'max:60']], [], ['basketName' => 'basket name']);

        $saver = app(SaveBasket::class);

        if ($saver->atLimit($user)) {
            $this->addError('basketName', 'You can keep up to '.SavedBasket::MAX_PER_USER.' saved baskets. Delete one in your account first.');

            return;
        }

        $order = $this->order();

        $basket = match (true) {
            $order !== null => $saver->fromOrder($user, $order, $this->basketName),
            $this->orderId !== null => null, // not this customer's order
            default => $saver->fromCart($user, $this->basketName),
        };

        if (! $basket) {
            $this->addError('basketName', 'There’s nothing to save yet.');

            return;
        }

        $this->savedAs = $basket->name;
        $this->basketName = '';
    }

    public function again(): void
    {
        $this->savedAs = null;
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-render when the basket changes (empty baskets hide the card).
    }

    private function order(): ?Order
    {
        if (! $this->orderId || ! Auth::id()) {
            return null;
        }

        return Order::with('items')->where('user_id', Auth::id())->find($this->orderId);
    }

    public function render()
    {
        return view('livewire.store.save-basket-button', [
            'hasItems' => $this->orderId ? (bool) $this->order()?->items->isNotEmpty() : app(Cart::class)->lines()->isNotEmpty(),
        ]);
    }
}
