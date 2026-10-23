<?php

namespace App\Livewire\Store\Account;

use App\Models\Coupon;
use App\Services\Loyalty\LoyaltyPoints;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rewards & points (Figma 06.07): balance, the reward on offer and the
 * points history.
 */
#[Layout('layouts::store')]
#[Title('Rewards & points')]
class Rewards extends Component
{
    #[Url(except: 'all')]
    public string $tab = 'all';

    public ?string $redeemedCode = null;

    public ?string $problem = null;

    public function redeem(): void
    {
        $coupon = app(LoyaltyPoints::class)->redeem(Auth::user());

        if (! $coupon) {
            $this->problem = 'You need '.number_format(config('loyalty.reward_points')).' points for this reward.';

            return;
        }

        // Ready to use: put it on the basket straight away.
        $cart = app(Cart::class);
        $cart->applyCoupon($coupon->code, $cart->subtotalCents());

        $this->problem = null;
        $this->redeemedCode = $coupon->code;
    }

    public function render()
    {
        $user = Auth::user();
        $history = $user->pointTransactions()->with('order:id,number');

        return view('livewire.store.account.rewards', [
            'balance' => $user->pointsBalance(),
            'earned' => (int) (clone $history)->where('points', '>', 0)->sum('points'),
            'used' => (int) -(clone $history)->where('points', '<', 0)->sum('points'),
            'transactions' => $history
                ->when($this->tab === 'earned', fn ($q) => $q->where('points', '>', 0))
                ->when($this->tab === 'used', fn ($q) => $q->where('points', '<', 0))
                ->limit(50)
                ->get(),
            'codes' => Coupon::where('user_id', $user->id)
                ->where('times_used', 0)
                ->where('expires_at', '>', now())
                ->latest()
                ->get(),
            'cost' => config('loyalty.reward_points'),
            'rewardValue' => config('loyalty.reward_value_cents'),
            'appliedCode' => app(Cart::class)->coupon()?->code,
        ]);
    }
}
