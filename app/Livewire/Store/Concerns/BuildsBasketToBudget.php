<?php

namespace App\Livewire\Store\Concerns;

use App\Services\Baskets\BudgetBuilder;
use App\Support\Cart;
use Livewire\Attributes\On;

/**
 * "Build my basket" (budget engine Modes 1 and 3) for any component that
 * shows the basket: the cart page and the cart drawer. Both stay in step
 * through the budget-updated event.
 */
trait BuildsBasketToBudget
{
    /** The budget the shopper types, in dollars. */
    public string $budgetInput = '';

    public function mountBuildsBasketToBudget(): void
    {
        $this->syncBudgetInput();
    }

    public function setBudget(): void
    {
        $this->validate(['budgetInput' => ['required', 'numeric', 'min:1', 'max:1000']], [], ['budgetInput' => 'budget']);
        app(BudgetBuilder::class)->setBudget((int) round((float) $this->budgetInput * 100));
        $this->syncBudgetInput();
        $this->dispatch('budget-updated')->to($this->budgetPeer());
    }

    public function clearBudget(): void
    {
        app(BudgetBuilder::class)->setBudget(null);
        $this->reset('budgetInput');
        $this->resetErrorBag('budgetInput');
        $this->dispatch('budget-updated')->to($this->budgetPeer());
    }

    /**
     * Apply one "stay within my budget" change the shopper tapped.
     */
    public function applySuggestion(string $key): void
    {
        if (app(BudgetBuilder::class)->apply($key)) {
            $this->dispatch('cart-updated', count: app(Cart::class)->count());
        }
    }

    /**
     * The other basket component on the page, told when the budget changes.
     */
    abstract protected function budgetPeer(): string;

    #[On('budget-updated')]
    public function syncBudgetInput(): void
    {
        $budget = app(BudgetBuilder::class)->budget();
        $this->budgetInput = $budget ? number_format($budget / 100, 2, '.', '') : '';
    }
}
