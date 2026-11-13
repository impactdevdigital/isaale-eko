<?php

namespace App\Livewire\Store\Account;

use App\Models\Coupon;
use App\Services\Loyalty\LoyaltyProvider;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rewards (Figma 06.07): progress towards the next reward in dollars
 * ("$76 / $100"), unlocked rewards ready to use, and the history.
 */
#[Layout('layouts::store')]
#[Title('Rewards')]
class Rewards extends Component
{
    #[Url(except: 'all')]
    public string $tab = 'all';

    public ?string $appliedNotice = null;

    /**
     * Put one of the customer's unlocked rewards on their basket.
     */
    public function useReward(int $couponId): void
    {
        $coupon = Coupon::where('user_id', Auth::id())->whereKey($couponId)->firstOrFail();
        $cart = app(Cart::class);
        $result = $cart->applyCoupon($coupon->code, $cart->subtotalCents());

        $this->appliedNotice = $result === 'applied'
            ? money($coupon->value).' off is on your basket. It comes off at checkout on orders of '.money($coupon->min_subtotal_cents).' or more.'
            : 'Your basket needs to reach '.money($coupon->min_subtotal_cents).' first. Add a little more, then use it here or enter '.$coupon->code.' at checkout.';
    }

    public function render(LoyaltyProvider $loyalty)
    {
        $user = Auth::user();
        $history = $user->pointTransactions()->with('order:id,number');

        return view('livewire.store.account.rewards', [
            'progress' => $loyalty->progress($user),
            'transactions' => $history
                ->when($this->tab === 'earned', fn ($q) => $q->where('points', '>', 0))
                ->when($this->tab === 'used', fn ($q) => $q->where('points', '<', 0))
                ->limit(50)
                ->get(),
            'appliedCode' => app(Cart::class)->coupon()?->code,
            'perDollar' => max(1, (int) config('loyalty.points_per_dollar')),
        ]);
    }
}
