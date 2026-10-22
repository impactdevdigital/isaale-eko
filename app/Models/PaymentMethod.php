<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function label(): string
    {
        return config("payments.methods.{$this->code}.label", $this->code);
    }

    public function description(): string
    {
        return config("payments.methods.{$this->code}.description", '');
    }

    public function allowedFulfilments(): array
    {
        return config("payments.methods.{$this->code}.fulfilment", ['pickup', 'delivery']);
    }

    /**
     * Methods that are enabled and allowed for the requested fulfilment type (pickup or delivery)
     */
    public function scopeAvailable(Builder $query, string $fulfilment): Builder
    {
        $allowedCodes = collect(config('payments.methods', []))
            ->filter(fn ($m) => in_array($fulfilment, $m['fulfilment'] ?? []))
            ->keys()
            ->all();

        return $query->where('is_enabled', true)
            ->whereIn('code', $allowedCodes)
            ->orderBy('sort_order');
    }
}
