<?php

namespace App\Services\Kitchen;

use App\Models\Recipe;
use Illuminate\Support\Collection;

final class ResolvedRecipe
{
    /**
     * @param Recipe $recipe
     * @param int $people
     * @param Collection<int, ResolvedLine> $lines
     */
    public function __construct(
        public readonly Recipe $recipe,
        public readonly int $people,
        public readonly Collection $lines
    ) {}

    public function totalCents(bool $includeOptional = false): int
    {
        return $this->lines
            ->filter(fn ($l) => $l->available() && ($includeOptional || $l->role === 'required'))
            ->sum(fn ($l) => $l->totalCents());
    }

    public function missingRequired(): Collection
    {
        return $this->lines
            ->filter(fn ($l) => $l->role === 'required' && ! $l->available())
            ->values();
    }

    public function isBuyable(): bool
    {
        return $this->missingRequired()->isEmpty();
    }
}
