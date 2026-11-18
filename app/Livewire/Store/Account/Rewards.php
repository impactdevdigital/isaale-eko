<?php

namespace App\Livewire\Store\Account;

use App\Services\Loyalty\SquareLoyalty;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Rewards (Figma 06.07), from Square Loyalty: the customer's points, how
 * close they are to the next reward, and their recent points activity,
 * online and in store. A ready reward can be put on the basket here.
 */
#[Layout('layouts::store')]
#[Title('Rewards')]
class Rewards extends Component
{
    public ?string $appliedNotice = null;

    public function useReward(SquareLoyalty $loyalty): void
    {
        if (! $loyalty->availableReward(Auth::user())) {
            return;
        }

        app(Cart::class)->useReward();
        $this->appliedNotice = 'Your reward is on your basket. It comes off at checkout, and the points are only used once your order is paid.';
    }

    public function dropReward(): void
    {
        app(Cart::class)->dropReward();
        $this->appliedNotice = null;
    }

    public function render(SquareLoyalty $loyalty)
    {
        return view('livewire.store.account.rewards', [
            'progress' => $loyalty->progress(Auth::user()),
            'onBasket' => app(Cart::class)->usingReward(),
        ]);
    }
}
