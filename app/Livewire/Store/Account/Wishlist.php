<?php

namespace App\Livewire\Store\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::store')]
#[Title('Wishlist')]
class Wishlist extends Component
{
    public function remove(int $productId): void
    {
        Auth::user()->wishlist()->detach($productId);
    }

    public function addAll(): void
    {
        $this->dispatch('cart-add-many', variations: $this->products()->filter->inStock()->map->defaultVariation->pluck('id')->all());
    }

    private function products()
    {
        return Auth::user()->wishlist()->forCard()->get();
    }

    public function render()
    {
        return view('livewire.store.account.wishlist', ['products' => $this->products()]);
    }
}
