<?php

namespace App\Livewire\Store\Account;

use App\Models\SavedBasket;
use App\Models\SavedBasketItem;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\BasketRepricer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::store')]
#[Title('Saved baskets')]
class SavedBaskets extends Component
{
    public function delete(int $id): void
    {
        Auth::user()->savedBaskets()->whereKey($id)->delete();
    }

    public function render(BasketRepricer $repricer)
    {
        $baskets = Auth::user()->savedBaskets()->with('items')->get()->map(function (SavedBasket $basket) use ($repricer) {
            $lines = $repricer->reprice($basket->items->map(fn (SavedBasketItem $item) => [
                'key' => 'i'.$item->id,
                'variation_id' => $item->product_variation_id,
                'quantity' => $item->quantity,
                'then_cents' => $item->unit_price_cents,
                'name' => $item->name,
            ]));

            return (object) [
                'basket' => $basket,
                'count' => $basket->items->sum('quantity'),
                'savedTotal' => $basket->savedTotalCents(),
                'nowTotal' => $lines->sum(fn (BasketLine $line) => $line->nowTotalCents()),
                'unavailable' => $lines->reject->available()->count(),
                'images' => $lines->map->image()->filter()->take(4),
            ];
        });

        return view('livewire.store.account.saved-baskets', ['baskets' => $baskets]);
    }
}
