<?php

namespace App\Services\Baskets;

use App\Models\ProductVariation;

/**
 * One line of a basket being rebuilt, repriced at today's prices.
 */
final class BasketLine
{
    public const OK = 'ok';

    /** Still on sale, but at a different price from when it was bought or saved. */
    public const PRICE_CHANGED = 'price_changed';

    /** Fewer left than wanted; the quantity was lowered to what's in stock. */
    public const STOCK_LOWERED = 'stock_lowered';

    public const UNAVAILABLE = 'unavailable';

    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly ?string $variationName,
        public readonly ?ProductVariation $variation,
        public readonly int $wantedQuantity,
        public readonly int $quantity,
        public readonly ?int $thenUnitCents,
        public readonly string $status,
        public readonly ?ProductVariation $substitute = null,
    ) {}

    public function available(): bool
    {
        return $this->status !== self::UNAVAILABLE;
    }

    public function nowTotalCents(): int
    {
        return $this->available() ? $this->variation->price_cents * $this->quantity : 0;
    }

    public function thenTotalCents(): ?int
    {
        return $this->thenUnitCents === null ? null : $this->thenUnitCents * $this->wantedQuantity;
    }

    public function image(): ?string
    {
        $variation = $this->variation ?? $this->substitute;

        return $variation?->image ?: $variation?->product?->image;
    }
}
