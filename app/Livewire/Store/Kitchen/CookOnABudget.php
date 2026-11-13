<?php

namespace App\Livewire\Store\Kitchen;

use App\Services\Baskets\BudgetBuilder;
use App\Support\Cart;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "What can I cook with $30?" (budget engine, Mode 2): the shopper gives a
 * budget, party size, cuisine and optional diet; we show the top three
 * meals that fit, each priced with the exact items "Shop this meal" adds.
 */
#[Layout('layouts::store')]
#[Title('Cook on a budget')]
class CookOnABudget extends Component
{
    #[Url]
    public string $budget = '30';

    #[Url]
    public int $people = 4;

    #[Url]
    public string $cuisine = '';

    #[Url]
    public string $diet = '';

    public ?string $added = null;

    public function mount(): void
    {
        // Coming from the basket with a budget set: start from what's left.
        if (! request()->has('budget') && ($status = app(BudgetBuilder::class)->status())) {
            $this->budget = number_format(max($status->left, BudgetBuilder::MIN_BUDGET_CENTS) / 100, 2, '.', '');
        }
    }

    public function updated(string $property): void
    {
        $this->added = null;

        if ($property === 'people') {
            $this->people = max(1, min($this->people, (int) config('kitchen.max_people', 20)));
        }
    }

    public function incrementPeople(): void
    {
        $this->people = min($this->people + 1, (int) config('kitchen.max_people', 20));
    }

    public function decrementPeople(): void
    {
        $this->people = max(1, $this->people - 1);
    }

    public function shop(string $key): void
    {
        $engine = app(BudgetBuilder::class);
        $option = $engine->option($key, $this->people, $this->budgetCents());

        if (! $option) {
            $this->added = null;

            return;
        }

        $engine->addToCart($option);

        // Shopping for a meal under a budget sets that budget on the basket too.
        if ($engine->budget() === null) {
            $engine->setBudget($this->budgetCents());
        }

        $this->added = $option->name;
        $this->dispatch('cart-updated', count: app(Cart::class)->count());
        $this->dispatch('cart-open');
    }

    private function budgetCents(): int
    {
        $dollars = (float) preg_replace('/[^0-9.]/', '', $this->budget);

        return max(BudgetBuilder::MIN_BUDGET_CENTS, min(BudgetBuilder::MAX_BUDGET_CENTS, (int) round($dollars * 100)));
    }

    public function render(BudgetBuilder $engine)
    {
        $budget = $this->budgetCents();

        return view('livewire.store.kitchen.cook-on-a-budget', [
            'budgetCents' => $budget,
            'plan' => $engine->meals($budget, $this->people, $this->cuisine ?: null, $this->diet ?: null),
            'cuisines' => config('kitchen.cuisines', []),
            'diets' => $engine->dietTags(),
        ]);
    }
}
