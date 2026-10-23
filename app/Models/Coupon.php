<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'user_id' => 'integer',
        'value' => 'integer',
        'min_subtotal_cents' => 'integer',
        'max_uses' => 'integer',
        'times_used' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }

    public function isExpired(): bool
    {
        return ($this->expires_at && $this->expires_at->isPast())
            || ($this->max_uses !== null && $this->times_used >= $this->max_uses);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reward codes belong to one customer; other codes are for anyone.
     */
    public function usableBy(?User $user): bool
    {
        return $this->user_id === null || $this->user_id === $user?->id;
    }

    public function isLive(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->isPast());
    }

    /**
     * Discount in cents for a subtotal, never more than the subtotal itself.
     */
    public function discountFor(int $subtotalCents): int
    {
        $discount = $this->type === 'percent'
            ? (int) round($subtotalCents * $this->value / 100)
            : $this->value;

        return min($discount, $subtotalCents);
    }
}
