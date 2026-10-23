<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\ProductVariation;
use Illuminate\Support\Collection;

/**
 * Session-backed shopping basket. The session only holds variation ids,
 * quantities, checkout ticks and a promo code; prices and stock always
 * come from the database.
 */
class Cart
{
    private const SESSION_KEY = 'cart.lines';

    private const COUPON_KEY = 'cart.coupon';

    public const MAX_QUANTITY = 99;

    /**
     * Current lines with their variation and product loaded. Lines whose
     * variation no longer exists are dropped; lines that can't be bought
     * right now stay, flagged as unavailable. Quantities above the stock
     * left are lowered and explained in `notice`.
     *
     * @return Collection<int, object{variation: ProductVariation, quantity: int, selected: bool, available: bool, notice: ?string}>
     */
    public function lines(): Collection
    {
        $stored = $this->stored();

        if (! $stored) {
            return collect();
        }

        $variations = ProductVariation::with('product')
            ->whereIn('id', array_keys($stored))
            ->get()
            ->keyBy('id');

        $lines = collect();
        $changed = false;

        foreach ($stored as $id => $line) {
            $variation = $variations[$id] ?? null;

            if (! $variation || ! $variation->product) {
                unset($stored[$id]);
                $changed = true;

                continue;
            }

            $available = $variation->product->is_active && $variation->inStock();
            $notice = null;

            if ($available && $variation->stock_quantity !== null && $line['quantity'] > $variation->stock_quantity) {
                $stored[$id]['quantity'] = $line['quantity'] = $variation->stock_quantity;
                $notice = "Only {$variation->stock_quantity} left — we updated your quantity.";
                $changed = true;
            }

            $lines[$id] = (object) [
                'variation' => $variation,
                'quantity' => $line['quantity'],
                'selected' => $line['selected'],
                'available' => $available,
                'notice' => $notice,
            ];
        }

        if ($changed) {
            $this->save($stored);
        }

        return $lines;
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
        $limit = min(self::MAX_QUANTITY, $variation->stock_quantity ?? self::MAX_QUANTITY);

        $stored[$variationId] = [
            'quantity' => min($limit, ($stored[$variationId]['quantity'] ?? 0) + max(1, $quantity)),
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

        $stock = ProductVariation::whereKey($variationId)->value('stock_quantity');
        $stored[$variationId]['quantity'] = min(self::MAX_QUANTITY, $stock ?? self::MAX_QUANTITY, $quantity);
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

    public function selectAll(bool $selected): void
    {
        $this->save(array_map(fn (array $line) => [...$line, 'selected' => $selected], $this->stored()));
    }

    public function remove(int ...$variationIds): void
    {
        $this->save(array_diff_key($this->stored(), array_flip($variationIds)));
    }

    public function count(): int
    {
        return array_sum(array_column($this->stored(), 'quantity'));
    }

    /**
     * Total of the available lines ticked for checkout, in cents. Pass
     * already-loaded lines to avoid querying them again.
     */
    public function subtotalCents(?Collection $lines = null): int
    {
        return $this->checkoutLines($lines)->sum(fn ($line) => $line->variation->price_cents * $line->quantity);
    }

    /**
     * Lines that will be ordered: ticked and available.
     */
    public function checkoutLines(?Collection $lines = null): Collection
    {
        return ($lines ?? $this->lines())->filter(fn ($line) => $line->selected && $line->available);
    }

    public function coupon(): ?Coupon
    {
        $code = session(self::COUPON_KEY);

        return $code ? Coupon::findByCode($code) : null;
    }

    /**
     * Try a promo code against the current subtotal. Returns applied,
     * invalid, expired or minimum; only "applied" keeps the code.
     */
    public function applyCoupon(string $code, int $subtotalCents): string
    {
        $coupon = Coupon::findByCode($code);

        $result = match (true) {
            ! $coupon || ! $coupon->isLive() || ! $coupon->usableBy(auth()->user()) => 'invalid',
            $coupon->isExpired() => 'expired',
            $coupon->min_subtotal_cents && $subtotalCents < $coupon->min_subtotal_cents => 'minimum',
            default => 'applied',
        };

        if ($result === 'applied') {
            session([self::COUPON_KEY => $coupon->code]);
        }

        return $result;
    }

    public function removeCoupon(): void
    {
        session()->forget(self::COUPON_KEY);
    }

    /**
     * Forget everything, e.g. after the order is placed.
     */
    public function clear(int ...$variationIds): void
    {
        $this->remove(...$variationIds);
        $this->removeCoupon();
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
