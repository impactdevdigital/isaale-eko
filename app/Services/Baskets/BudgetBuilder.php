<?php

namespace App\Services\Baskets;

use App\Models\IngredientProduct;
use App\Models\MealCombo;
use App\Models\ProductVariation;
use App\Models\Recipe;
use App\Services\Kitchen\RecipeResolver;
use App\Services\Kitchen\ResolvedLine;
use App\Services\Kitchen\ResolvedRecipe;
use App\Support\Cart;
use App\Support\CartTotals;
use Illuminate\Support\Collection;

/**
 * The basket and budget engine: the one place that does budget maths for
 * the website (and, later, Asha).
 *
 *  - Mode 1, build my basket: the shopper's budget and what's left of it.
 *  - Mode 2, what can I cook with $X: approved recipes and meal combos,
 *    scaled to the party, priced with real orderable variations, kept at
 *    or under budget and ranked.
 *  - Mode 3, stay within my budget: ways to close the gap (smaller pack,
 *    cheaper approved substitute, remove an item); each one is applied only
 *    when the shopper taps it.
 *  - Mode 4, rebuild my usual basket, lives in BasketRepricer.
 *
 * Prices are our synced catalogue prices: estimates until Square confirms
 * them (and adds tax) at checkout.
 */
class BudgetBuilder
{
    private const SESSION_KEY = 'cart.budget';

    public const MIN_BUDGET_CENTS = 100;

    public const MAX_BUDGET_CENTS = 100_000;

    public function __construct(
        private Cart $cart,
        private RecipeResolver $resolver,
    ) {}

    // ----- Mode 1: the shopper's budget --------------------------------------

    public function budget(): ?int
    {
        $budget = session(self::SESSION_KEY);

        return is_int($budget) ? $budget : null;
    }

    public function setBudget(?int $cents): void
    {
        if ($cents === null) {
            session()->forget(self::SESSION_KEY);

            return;
        }

        session([self::SESSION_KEY => max(self::MIN_BUDGET_CENTS, min(self::MAX_BUDGET_CENTS, $cents))]);
    }

    /**
     * The basket against the budget, or null when no budget is set.
     *
     * @return object{budget: int, spent: int, left: int, over: int}|null
     */
    public function status(): ?object
    {
        if (($budget = $this->budget()) === null) {
            return null;
        }

        $spent = $this->spent();

        return (object) [
            'budget' => $budget,
            'spent' => $spent,
            'left' => max(0, $budget - $spent),
            'over' => max(0, $spent - $budget),
        ];
    }

    /**
     * What the ticked, available basket lines cost after any promo code.
     */
    public function spent(): int
    {
        return CartTotals::for($this->cart->checkoutLines(), $this->cart->coupon(), 0)->total();
    }

    // ----- Mode 2: what can I cook with $X? ---------------------------------

    /**
     * @return object{options: Collection<int, MealOption>, closest: ?MealOption, dropped: Collection<int, array{name: string, reason: string}>}
     */
    public function meals(int $budgetCents, int $people, ?string $cuisine = null, ?string $diet = null, int $limit = 3): object
    {
        $people = max(1, min($people, (int) config('kitchen.max_people', 20)));
        $dropped = collect();
        $buyable = collect();

        foreach ($this->candidates($cuisine, $diet) as $candidate) {
            $option = $this->price($candidate, $people, $budgetCents, $reason);

            $option ? $buyable->push($option) : $dropped->push(['name' => $candidate['name'], 'reason' => $reason]);
        }

        $fitting = $buyable->filter(fn (MealOption $option) => $option->totalCents() <= $budgetCents);

        return (object) [
            'options' => $this->rank($fitting, $budgetCents, $limit),
            'closest' => $fitting->isEmpty() ? $buyable->sortBy(fn (MealOption $option) => $option->totalCents())->first() : null,
            'dropped' => $dropped->values(),
        ];
    }

    /**
     * Rebuild one option the shopper picked, e.g. "recipe:12" or "combo:3".
     */
    public function option(string $key, int $people, int $budgetCents): ?MealOption
    {
        $candidate = $this->candidates(null, null)->firstWhere('key', $key);

        return $candidate ? $this->price($candidate, max(1, $people), $budgetCents, $reason) : null;
    }

    /**
     * Put exactly the priced variations in the basket, so the basket total
     * matches the price the shopper was shown.
     */
    public function addToCart(MealOption $option): int
    {
        $added = 0;

        foreach ($option->lines as $line) {
            $added += $this->cart->add($line['variation']->id, $line['quantity']) ? 1 : 0;
        }

        return $added;
    }

    /**
     * Distinct diet tags on approved recipes, for the filter.
     *
     * @return array<int, string>
     */
    public function dietTags(): array
    {
        return Recipe::approved()->pluck('diet_tags')->flatten()->filter()->unique()->sort()->values()->all();
    }

    /**
     * Approved recipes and active meal combos matching the filters.
     *
     * @return Collection<int, array{key: string, name: string, recipes: Collection<int, Recipe>, combo: ?MealCombo, popularity: float}>
     */
    private function candidates(?string $cuisine, ?string $diet): Collection
    {
        $matches = fn (Recipe $recipe) => (! $cuisine || $recipe->cuisine === $cuisine)
            && (! $diet || in_array($diet, $recipe->diet_tags ?? [], true));

        $recipes = Recipe::approved()
            ->with('ingredients.ingredient.options.variation.product')
            ->orderBy('sort_order')
            ->get();

        $count = max(1, $recipes->count());

        $singles = $recipes->filter($matches)->map(fn (Recipe $recipe) => [
            'key' => 'recipe:'.$recipe->id,
            'name' => $recipe->title,
            'recipes' => collect([$recipe]),
            'combo' => null,
            // Featured first, then the kitchen's own order.
            'popularity' => ($recipe->is_featured ? 0.5 : 0) + 0.5 * (1 - $recipes->search($recipe) / $count),
        ]);

        $combos = MealCombo::where('status', 'active')->with('recipes')->get()
            ->map(function (MealCombo $combo) use ($recipes) {
                $members = $combo->recipes->map(fn (Recipe $recipe) => $recipes->firstWhere('id', $recipe->id));

                return $members->isNotEmpty() && $members->every(fn ($recipe) => $recipe !== null) ? [
                    'key' => 'combo:'.$combo->id,
                    'name' => $combo->name,
                    'recipes' => $members,
                    'combo' => $combo,
                    'popularity' => 0.75,
                ] : null;
            })
            ->filter(fn ($combo) => $combo && $combo['recipes']->every($matches));

        return $singles->concat($combos)->values();
    }

    /**
     * Scale and price a candidate. Uses the best-ranked products first and
     * falls back to the cheapest approved options if that's over budget.
     * Returns null (with $reason) when a required ingredient can't be bought.
     */
    private function price(array $candidate, int $people, int $budgetCents, ?string &$reason = null): ?MealOption
    {
        $best = null;

        foreach (['preferred', 'cheapest'] as $strategy) {
            $resolved = $candidate['recipes']->map(fn (Recipe $recipe) => $this->resolver->resolve($recipe, $people, $strategy));

            if ($problem = $this->problemWith($resolved)) {
                $reason = $problem;

                continue;
            }

            $option = new MealOption(
                key: $candidate['key'],
                name: $candidate['name'],
                recipes: $candidate['recipes'],
                combo: $candidate['combo'],
                people: $people,
                lines: $this->merge($resolved),
                popularity: $candidate['popularity'],
            );

            if (! $best || $option->totalCents() < $best->totalCents()) {
                $best = $option;
            }

            if ($best->totalCents() <= $budgetCents) {
                break;
            }
        }

        if ($best) {
            $reason = null;
        }

        return $best;
    }

    /**
     * Why these resolved recipes can't be bought today, if they can't.
     *
     * @param  Collection<int, ResolvedRecipe>  $resolved
     */
    private function problemWith(Collection $resolved): ?string
    {
        $missing = $resolved->flatMap(fn (ResolvedRecipe $recipe) => $recipe->missingRequired());

        if ($missing->isNotEmpty()) {
            return $missing->map(fn (ResolvedLine $line) => $line->ingredient->name)->unique()->join(', ', ' and ').' '.($missing->count() === 1 ? 'isn’t' : 'aren’t').' available today';
        }

        $short = $this->merge($resolved)->first(fn (array $line) => $line['variation']->stock_quantity !== null && $line['quantity'] > $line['variation']->stock_quantity);

        return $short ? "Only {$short['variation']->stock_quantity} {$short['variation']->product->name} left" : null;
    }

    /**
     * The required lines of the resolved recipes, one line per variation.
     *
     * @param  Collection<int, ResolvedRecipe>  $resolved
     * @return Collection<int, array{variation: ProductVariation, quantity: int}>
     */
    private function merge(Collection $resolved): Collection
    {
        return $resolved
            ->flatMap(fn (ResolvedRecipe $recipe) => $recipe->lines)
            ->filter(fn (ResolvedLine $line) => $line->role === 'required' && $line->available())
            ->groupBy(fn (ResolvedLine $line) => $line->variation->id)
            ->map(fn (Collection $lines) => [
                'variation' => $lines->first()->variation,
                'quantity' => min(Cart::MAX_QUANTITY, $lines->sum('quantity')),
            ])
            ->values();
    }

    /**
     * Best first: uses the budget well (closest to it without going over),
     * then popularity; then keep the top picks varied by cuisine.
     *
     * @param  Collection<int, MealOption>  $options
     * @return Collection<int, MealOption>
     */
    private function rank(Collection $options, int $budgetCents, int $limit): Collection
    {
        $sorted = $options->sortByDesc(fn (MealOption $option) => 0.7 * ($option->totalCents() / max(1, $budgetCents)) + 0.3 * $option->popularity)->values();

        $picked = collect();

        foreach ($sorted as $option) {
            if ($picked->count() < $limit && ! $picked->contains(fn (MealOption $chosen) => $chosen->cuisine() === $option->cuisine())) {
                $picked->push($option);
            }
        }

        foreach ($sorted as $option) {
            if ($picked->count() < $limit && ! $picked->contains($option)) {
                $picked->push($option);
            }
        }

        return $picked->sortByDesc(fn (MealOption $option) => 0.7 * ($option->totalCents() / max(1, $budgetCents)) + 0.3 * $option->popularity)->values();
    }

    // ----- Mode 3: stay within my budget ------------------------------------

    /**
     * Ways to get back under budget, most useful first. Empty unless the
     * basket is over. Nothing changes until the shopper applies one.
     *
     * @return Collection<int, object{key: string, type: string, label: string, saving: int, closesGap: bool}>
     */
    public function suggestions(int $limit = 5): Collection
    {
        $status = $this->status();

        if (! $status || $status->over === 0) {
            return collect();
        }

        $suggestions = collect();

        foreach ($this->cart->checkoutLines() as $line) {
            $variation = $line->variation;
            $product = $variation->product;
            $lineCost = $variation->price_cents * $line->quantity;

            // A smaller (cheaper) pack of the same product.
            $smaller = $product->variations()
                ->whereKeyNot($variation->id)
                ->where('price_cents', '<', $variation->price_cents)
                ->orderByDesc('price_cents')
                ->get()
                ->each->setRelation('product', $product)
                ->first(fn (ProductVariation $other) => $this->orderable($other, $line->quantity));

            if ($smaller) {
                $suggestions->push($this->suggestion("pack:{$variation->id}:{$smaller->id}", 'pack',
                    "Choose {$product->name} {$smaller->name} instead of {$variation->name}",
                    ($variation->price_cents - $smaller->price_cents) * $line->quantity, $status->over));
            }

            // A cheaper approved substitute (another product for the same ingredient).
            if ($substitute = $this->cheaperSubstitute($variation, $line->quantity)) {
                $suggestions->push($this->suggestion("swap:{$variation->id}:{$substitute->id}", 'swap',
                    "Swap {$product->name} for {$substitute->product->name} {$substitute->name}",
                    ($variation->price_cents - $substitute->price_cents) * $line->quantity, $status->over));
            }

            if ($line->quantity > 1) {
                $suggestions->push($this->suggestion("less:{$variation->id}", 'less',
                    "Buy one less {$product->name}", $variation->price_cents, $status->over));
            }

            $suggestions->push($this->suggestion("remove:{$variation->id}", 'remove',
                "Remove {$product->name}", $lineCost, $status->over));
        }

        // Prefer the smallest change that closes the gap on its own; then the biggest savings.
        return $suggestions
            ->sortBy(fn ($suggestion) => $suggestion->closesGap ? [0, $suggestion->type === 'remove' ? 1 : 0, $suggestion->saving] : [1, 0, -$suggestion->saving])
            ->take($limit)
            ->values();
    }

    /**
     * Apply one suggestion the shopper tapped. The key is checked against a
     * fresh list, so only a change we'd actually offer goes through.
     */
    public function apply(string $key): bool
    {
        $suggestion = $this->suggestions(PHP_INT_MAX)->firstWhere('key', $key);

        if (! $suggestion) {
            return false;
        }

        $parts = explode(':', $key);
        $from = (int) $parts[1];
        $quantity = $this->cart->quantity($from);

        match ($suggestion->type) {
            'pack', 'swap' => $this->replace($from, (int) $parts[2], $quantity),
            'less' => $this->cart->setQuantity($from, $quantity - 1),
            'remove' => $this->cart->remove($from),
        };

        return true;
    }

    private function replace(int $from, int $to, int $quantity): void
    {
        $this->cart->remove($from);
        $this->cart->add($to, $quantity);
    }

    private function suggestion(string $key, string $type, string $label, int $saving, int $gap): object
    {
        return (object) ['key' => $key, 'type' => $type, 'label' => $label, 'saving' => $saving, 'closesGap' => $saving >= $gap];
    }

    private function cheaperSubstitute(ProductVariation $variation, int $quantity): ?ProductVariation
    {
        $mappings = IngredientProduct::with([
            'ingredient.options.variation.product',
            'ingredient.substitutes.options.variation.product',
        ])->where('product_variation_id', $variation->id)->get();

        return $mappings->flatMap(fn (IngredientProduct $mapping) => $mapping->ingredient->options)
            ->concat($mappings->flatMap(fn (IngredientProduct $mapping) => $mapping->ingredient->substitutes->flatMap->options))
            ->pluck('variation')
            ->filter(fn (?ProductVariation $option) => $option
                && $option->product_id !== $variation->product_id
                && $option->price_cents < $variation->price_cents
                && $this->orderable($option, $quantity))
            ->sortBy('price_cents')
            ->first();
    }

    private function orderable(ProductVariation $variation, int $quantity): bool
    {
        return $variation->product?->isAvailable()
            && $variation->isOrderable()
            && ($variation->stock_quantity === null || $variation->stock_quantity >= $quantity);
    }
}
