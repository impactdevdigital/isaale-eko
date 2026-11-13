<?php

namespace App\Services\Baskets;

use App\Models\IngredientProduct;
use App\Models\ProductVariation;
use App\Support\Cart;
use Illuminate\Support\Collection;

/**
 * Reprices a remembered basket (a past order, a saved basket or the
 * customer's usuals) at today's prices. Lines that can't be bought today
 * are flagged, with an approved substitute when there is one; nothing is
 * swapped until the customer says so.
 */
class BasketRepricer
{
    /**
     * @param  iterable<array{key: string, variation_id: ?int, quantity: int, then_cents?: ?int, name: string, variation_name?: ?string}>  $wanted
     * @return Collection<int, BasketLine>
     */
    public function reprice(iterable $wanted): Collection
    {
        $wanted = collect($wanted);

        $variations = ProductVariation::with('product')
            ->whereIn('id', $wanted->pluck('variation_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return $wanted->map(function (array $line) use ($variations) {
            $variation = $variations[$line['variation_id']] ?? null;
            $quantity = max(1, (int) $line['quantity']);
            $then = $line['then_cents'] ?? null;

            if (! $variation || ! $this->orderable($variation)) {
                return new BasketLine(
                    key: $line['key'],
                    name: $variation?->product?->name ?? $line['name'],
                    variationName: $variation?->name ?? ($line['variation_name'] ?? null),
                    variation: $variation,
                    wantedQuantity: $quantity,
                    quantity: 0,
                    thenUnitCents: $then,
                    status: BasketLine::UNAVAILABLE,
                    substitute: $line['variation_id'] ? $this->substituteFor((int) $line['variation_id'], $variation) : null,
                );
            }

            $limit = min(Cart::MAX_QUANTITY, $variation->stock_quantity ?? Cart::MAX_QUANTITY);
            $status = match (true) {
                $quantity > $limit => BasketLine::STOCK_LOWERED,
                $then !== null && $then !== $variation->price_cents => BasketLine::PRICE_CHANGED,
                default => BasketLine::OK,
            };

            return new BasketLine(
                key: $line['key'],
                name: $variation->product->name,
                variationName: $variation->name,
                variation: $variation,
                wantedQuantity: $quantity,
                quantity: min($quantity, $limit),
                thenUnitCents: $then,
                status: $status,
            );
        })->values();
    }

    /**
     * An approved stand-in for a variation that can't be bought: another
     * size of the same product first, then the other ranked options for the
     * ingredient it is mapped to, then options for that ingredient's
     * approved substitutes.
     */
    public function substituteFor(int $variationId, ?ProductVariation $variation = null): ?ProductVariation
    {
        if ($variation?->product) {
            $sibling = $variation->product->variations()
                ->whereKeyNot($variation->id)
                ->orderBy('sort_order')
                ->get()
                ->each->setRelation('product', $variation->product)
                ->first(fn (ProductVariation $other) => $this->orderable($other));

            if ($sibling) {
                return $sibling;
            }
        }

        $mappings = IngredientProduct::with([
            'ingredient.options.variation.product',
            'ingredient.substitutes.options.variation.product',
        ])->where('product_variation_id', $variationId)->get();

        $candidates = $mappings->flatMap(fn (IngredientProduct $mapping) => $mapping->ingredient->options)
            ->concat($mappings->flatMap(fn (IngredientProduct $mapping) => $mapping->ingredient->substitutes->flatMap->options));

        return $candidates
            ->pluck('variation')
            ->filter()
            ->first(fn (ProductVariation $option) => $option->id !== $variationId && $this->orderable($option));
    }

    private function orderable(ProductVariation $variation): bool
    {
        return $variation->product !== null
            && $variation->product->isAvailable()
            && $variation->isOrderable();
    }
}
