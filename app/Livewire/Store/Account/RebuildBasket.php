<?php

namespace App\Livewire\Store\Account;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SavedBasket;
use App\Models\SavedBasketItem;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\BasketRepricer;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Rebuild my usual basket": a saved basket or a past order repriced at
 * today's prices. Unavailable items are flagged with an approved
 * substitute the customer can tap to use; they edit, then add it all to
 * their basket. Nothing is swapped or dropped without them seeing it.
 */
#[Layout('layouts::store')]
#[Title('Rebuild your basket')]
class RebuildBasket extends Component
{
    #[Locked]
    public string $title = '';

    #[Locked]
    public string $thenLabel = '';

    #[Locked]
    public ?int $basketId = null;

    /** @var array<string, array{variation_id: ?int, quantity: int, then_cents: int, name: string, variation_name: ?string, swap: bool, removed: bool}> */
    public array $lines = [];

    #[Locked]
    public int $thenTotal = 0;

    public function mount(?SavedBasket $basket = null, ?Order $order = null): void
    {
        if ($basket?->exists) {
            abort_unless($basket->user_id === Auth::id(), 404);

            $this->basketId = $basket->id;
            $this->title = $basket->name;
            $this->thenLabel = 'When you saved it';
            $items = $basket->items->map(fn (SavedBasketItem $item) => [
                'variation_id' => $item->product_variation_id,
                'quantity' => $item->quantity,
                'then_cents' => $item->unit_price_cents,
                'name' => $item->name,
                'variation_name' => $item->variation_name,
            ]);
        } else {
            abort_unless($order?->exists && $order->user_id === Auth::id() && $order->status !== Order::STATUS_PAYMENT_FAILED, 404);

            $this->title = 'Order '.$order->number;
            $this->thenLabel = 'Your last basket';
            $items = $order->items
                ->reject(fn (OrderItem $item) => $item->isRemoved())
                ->map(fn (OrderItem $item) => [
                    'variation_id' => $item->product_variation_id,
                    'quantity' => $item->quantity,
                    'then_cents' => $item->unit_price_cents,
                    'name' => $item->name,
                    'variation_name' => $item->variation_name,
                ]);
        }

        foreach ($items->values() as $i => $item) {
            $this->lines['l'.$i] = $item + ['swap' => false, 'removed' => false];
        }

        $this->thenTotal = (int) collect($this->lines)->sum(fn ($line) => $line['then_cents'] * $line['quantity']);
    }

    public function increment(string $key): void
    {
        if (isset($this->lines[$key])) {
            $this->lines[$key]['quantity'] = min(Cart::MAX_QUANTITY, $this->lines[$key]['quantity'] + 1);
        }
    }

    public function decrement(string $key): void
    {
        if (isset($this->lines[$key]) && $this->lines[$key]['quantity'] > 1) {
            $this->lines[$key]['quantity']--;
        }
    }

    public function remove(string $key): void
    {
        if (isset($this->lines[$key])) {
            $this->lines[$key]['removed'] = true;
        }
    }

    public function restore(string $key): void
    {
        if (isset($this->lines[$key])) {
            $this->lines[$key]['removed'] = false;
        }
    }

    /**
     * The customer taps to use (or stop using) the suggested substitute.
     */
    public function toggleSubstitute(string $key): void
    {
        if (isset($this->lines[$key])) {
            $this->lines[$key]['swap'] = ! $this->lines[$key]['swap'];
        }
    }

    public function addToBasket(): void
    {
        $cart = app(Cart::class);
        $added = 0;

        foreach ($this->repriced() as $line) {
            if ($this->lines[$line->key]['removed']) {
                continue;
            }

            $variation = $line->available() ? $line->variation : ($this->lines[$line->key]['swap'] ? $line->substitute : null);
            $quantity = $line->available() ? $line->quantity : $line->wantedQuantity;

            if ($variation && $cart->add($variation->id, $quantity)) {
                $added++;
            }
        }

        if ($added === 0) {
            $this->addError('basket', 'Nothing here can be added right now.');

            return;
        }

        $this->dispatch('cart-updated', count: $cart->count());
        session()->flash('rebuilt', $added === 1 ? 'Added 1 item to your basket.' : "Added {$added} items to your basket.");
        $this->redirectRoute('cart', navigate: true);
    }

    private function repriced()
    {
        return app(BasketRepricer::class)->reprice(
            collect($this->lines)->map(fn ($line, $key) => ['key' => $key] + $line)->values()
        );
    }

    public function render()
    {
        $lines = $this->repriced();

        $nowTotal = $lines->sum(function (BasketLine $line) {
            $state = $this->lines[$line->key];

            return match (true) {
                $state['removed'] => 0,
                $line->available() => $line->nowTotalCents(),
                $state['swap'] && $line->substitute => $line->substitute->price_cents * $line->wantedQuantity,
                default => 0,
            };
        });

        return view('livewire.store.account.rebuild-basket', [
            'repriced' => $lines,
            'nowTotal' => $nowTotal,
            'unavailableCount' => $lines->reject->available()->count(),
        ]);
    }
}
