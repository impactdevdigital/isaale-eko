<?php

namespace App\Services\Kitchen;

use App\Models\Ingredient;
use App\Models\ProductVariation;

final class ResolvedLine
{
    public function __construct(
        public readonly Ingredient $ingredient,
        public readonly string $role,                 // required | optional
        public readonly ?ProductVariation $variation, // null = nothing orderable right now
        public readonly int $quantity,                // how many of that variation
        public readonly int $unitPriceCents,
    ) {}

    public function available(): bool
    {
        return $this->variation !== null;
    }

    public function totalCents(): int
    {
        return $this->variation ? $this->quantity * $this->unitPriceCents : 0;
    }
}
