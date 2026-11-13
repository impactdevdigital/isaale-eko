<?php

namespace App\Services\Baskets;

use App\Models\MealCombo;
use App\Models\ProductVariation;
use App\Models\Recipe;
use Illuminate\Support\Collection;

/**
 * One meal the budget engine can offer: a recipe or a curated meal combo,
 * scaled to the party and priced with the exact variations it would put
 * in the basket.
 */
final class MealOption
{
    /**
     * @param  Collection<int, Recipe>  $recipes
     * @param  Collection<int, array{variation: ProductVariation, quantity: int}>  $lines
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly Collection $recipes,
        public readonly ?MealCombo $combo,
        public readonly int $people,
        public readonly Collection $lines,
        public readonly float $popularity,
    ) {}

    public function totalCents(): int
    {
        return (int) $this->lines->sum(fn (array $line) => $line['variation']->price_cents * $line['quantity']);
    }

    public function itemCount(): int
    {
        return (int) $this->lines->sum('quantity');
    }

    public function image(): ?string
    {
        return $this->recipes->first()?->image;
    }

    /**
     * The cuisine used to keep the top picks varied.
     */
    public function cuisine(): ?string
    {
        return $this->recipes->first()?->cuisine;
    }
}
