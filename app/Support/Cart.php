<?php

namespace App\Support;

use App\Models\ProductVariation;
use Illuminate\Support\Collection;

/**
 * Session-backed shopping basket. The session only holds variation ids,
 * quantities and checkout ticks; prices always come from the database.
 */
class Cart
{
    private const SESSION_KEY = 'cart.lines';

    public const MAX_QUANTITY = 99;

    /**
     * Current lines with their variation and product loaded. Lines whose
     * product was removed or deactivated are dropped.
     *
     * @return Collection<int, object{variation: ProductVariation, quantity: int, selected: bool}>
     */
    public function lines(): Collection
    {
        $stored = $this->stored();

        if (! $stored) {
            return collect();
        }

        $variations = ProductVariation::with('product')
            ->whereIn('id', array_keys($stored))
            ->whereHas('product', fn ($query) => $query->active())
            ->get()
            ->keyBy('id');

        return collect($stored)
            ->filter(fn ($line, $id) => $variations->has($id))
            ->map(fn ($line, $id) => (object) [
                'variation' => $variations[$id],
                'quantity' => $line['quantity'],
                'selected' => $line['selected'],
            ]);
    }

    public function add(int $variationId, int $quantity = 1): bool
    {
        $variation = ProductVariation::whereKey($variationId)
            ->whereHas('product', fn ($query) => $query->active())
            ->first();

        if (! $variation || ! $variation->inStock()) {
            return false;
        }

        $stored = $this->stored();
        $current = $stored[$variationId]['quantity'] ?? 0;

        $stored[$variationId] = [
            'quantity' => min(self::MAX_QUANTITY, $current + max(1, $quantity)),
            'selected' => $stored[$variationId]['selected'] ?? true,
        ];

        $this->save($stored);

        return true;
    }

    public function setQuantity(int $variationId, int $quantity): void
    {
        $stored = $this->stored();

        if (! isset($stored[$variationId])) {
            return;
        }

        if ($quantity < 1) {
            $this->remove($variationId);

            return;
        }

        $stored[$variationId]['quantity'] = min(self::MAX_QUANTITY, $quantity);
        $this->save($stored);
    }

    public function quantity(int $variationId): int
    {
        return $this->stored()[$variationId]['quantity'] ?? 0;
    }

    public function toggle(int $variationId): void
    {
        $stored = $this->stored();

        if (isset($stored[$variationId])) {
            $stored[$variationId]['selected'] = ! $stored[$variationId]['selected'];
            $this->save($stored);
        }
    }

    public function remove(int $variationId): void
    {
        $stored = $this->stored();
        unset($stored[$variationId]);
        $this->save($stored);
    }

    public function count(): int
    {
        return array_sum(array_column($this->stored(), 'quantity'));
    }

    /**
     * Total of the lines ticked for checkout, in cents. Pass already-loaded
     * lines to avoid querying them again.
     */
    public function subtotalCents(?Collection $lines = null): int
    {
        return ($lines ?? $this->lines())
            ->filter->selected
            ->sum(fn ($line) => $line->variation->price_cents * $line->quantity);
    }

    /**
     * @return array<int, array{quantity: int, selected: bool}>
     */
    private function stored(): array
    {
        return session(self::SESSION_KEY, []);
    }

    /**
     * @param  array<int, array{quantity: int, selected: bool}>  $stored
     */
    private function save(array $stored): void
    {
        session([self::SESSION_KEY => $stored]);
    }
}
