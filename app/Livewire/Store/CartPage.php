<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Models\SavedBasket;
use App\Support\Cart;
use App\Support\CartTotals;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::store')]
#[Title('Your basket')]
class CartPage extends Component
{
    public string $couponCode = '';

    /**
     * applied, invalid, expired or minimum, after the shopper presses Apply.
     */
    public ?string $couponResult = null;

    public string $basketName = '';

    /**
     * Keep everything in the basket as a named list to rebuild later at
     * that day's prices. Guests are asked to sign in (accounts stay optional).
     */
    public function saveBasket(): void
    {
        if (! $user = Auth::user()) {
            $this->dispatch('sign-in-open');

            return;
        }

        $this->validate(['basketName' => ['required', 'string', 'max:60']], [], ['basketName' => 'basket name']);

        $lines = $this->cart()->lines();

        if ($lines->isEmpty()) {
            return;
        }

        if ($user->savedBaskets()->count() >= SavedBasket::MAX_PER_USER) {
            $this->addError('basketName', 'You can keep up to '.SavedBasket::MAX_PER_USER.' saved baskets. Delete one in your account first.');

            return;
        }

        $basket = $user->savedBaskets()->create(['name' => trim($this->basketName)]);

        foreach ($lines as $line) {
            $basket->items()->create([
                'product_variation_id' => $line->variation->id,
                'name' => $line->variation->product->name,
                'variation_name' => $line->variation->name,
                'quantity' => $line->quantity,
                'unit_price_cents' => $line->variation->price_cents,
            ]);
        }

        $this->reset('basketName');
        session()->flash('basket-saved', "Saved “{$basket->name}”. Find it under Saved baskets in your account.");
    }

    public function mount(): void
    {
        $this->couponCode = $this->cart()->coupon()?->code ?? '';
    }

    public function increment(int $variation): void
    {
        $this->cart()->setQuantity($variation, $this->cart()->quantity($variation) + 1);
        $this->changed();
    }

    public function decrement(int $variation): void
    {
        $this->cart()->setQuantity($variation, max(1, $this->cart()->quantity($variation) - 1));
        $this->changed();
    }

    public function toggle(int $variation): void
    {
        $this->cart()->toggle($variation);
    }

    public function toggleAll(): void
    {
        $this->cart()->selectAll(! $this->cart()->lines()->every->selected);
    }

    public function remove(int $variation): void
    {
        $this->cart()->remove($variation);
        $this->changed();
    }

    public function removeSelected(): void
    {
        $this->cart()->remove(...$this->cart()->lines()->filter->selected->keys()->all());
        $this->changed();
    }

    public function removeUnavailable(): void
    {
        $this->cart()->remove(...$this->cart()->lines()->reject->available->keys()->all());
        $this->changed();
    }

    public function applyCoupon(): void
    {
        $this->validate(['couponCode' => ['required', 'string', 'max:40']], ['couponCode.required' => 'Enter a code first.']);

        $this->couponResult = $this->cart()->applyCoupon($this->couponCode, $this->cart()->subtotalCents());
        $this->couponCode = strtoupper(trim($this->couponCode));
    }

    public function removeCoupon(): void
    {
        $this->cart()->removeCoupon();
        $this->reset('couponCode', 'couponResult');
    }

    /**
     * Keep the header count and drawer in step with this page.
     */
    private function changed(): void
    {
        $this->dispatch('cart-updated', count: $this->cart()->count());
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-render when the drawer or another tab changes the basket.
    }

    private function cart(): Cart
    {
        return app(Cart::class);
    }

    public function render()
    {
        $cart = $this->cart();
        $lines = $cart->lines();
        $checkoutLines = $cart->checkoutLines($lines);

        return view('livewire.store.cart-page', [
            'available' => $lines->filter->available,
            'unavailable' => $lines->reject->available,
            'checkoutLines' => $checkoutLines,
            'allSelected' => $lines->isNotEmpty() && $lines->every->selected,
            'totals' => CartTotals::for($checkoutLines, $cart->coupon(), deliveryFee: null),
            'popular' => Product::forCard()
                ->available()
                ->orderByRaw('bestseller_rank is null')
                ->orderBy('bestseller_rank')
                ->orderByDesc('is_hot')
                ->take(7)
                ->get(),
        ]);
    }
}
