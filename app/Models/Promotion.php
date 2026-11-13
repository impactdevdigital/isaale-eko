<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A time-boxed offer built from one of a few templates. Who qualifies is
 * decided on our server (PromotionEngine), never by Asha.
 */
class Promotion extends Model
{
    public const TEMPLATE_ITEM_DISCOUNT = 'item_discount';

    public const TEMPLATE_FIRST_ORDER = 'first_order';

    public const TEMPLATES = [
        self::TEMPLATE_ITEM_DISCOUNT => 'Item discount',
        self::TEMPLATE_FIRST_ORDER => 'First online order offer',
    ];

    public const OBJECTIVES = [
        'acquisition' => 'Acquisition',
        'reactivation' => 'Reactivation',
        'basket_growth' => 'Basket growth',
        'discovery' => 'Discovery',
    ];

    public const AUDIENCES = [
        'everyone' => 'Everyone',
        'first_time' => 'First-time customers',
        'lapsed' => 'Lapsed customers (no order in 90+ days)',
        'loyalty_members' => 'Loyalty members',
    ];

    public const LAPSED_AFTER_DAYS = 90;

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'integer',
        'min_subtotal_cents' => 'integer',
        'per_customer_limit' => 'integer',
        'max_redemptions' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'collection_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    /**
     * Active and inside its dates. (The redemption cap is checked with hasRoom().)
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function hasRoom(): bool
    {
        return $this->max_redemptions === null || $this->redeemedCount() < $this->max_redemptions;
    }

    /**
     * Redemptions on orders that went through (failed or cancelled payments don't count).
     */
    public function redeemedCount(?User $user = null): int
    {
        return PromotionRedemption::counted()
            ->where('promotion_redemptions.promotion_id', $this->id)
            ->when($user, fn ($query) => $query->where('promotion_redemptions.user_id', $user->id))
            ->count();
    }

    /**
     * What staff see in the list: draft, scheduled, live, paused, capped or ended.
     */
    public function state(): string
    {
        return match (true) {
            ! $this->ends_at->isFuture() => 'ended',
            $this->status === 'draft' => 'draft',
            $this->status === 'paused' => 'paused',
            $this->max_redemptions !== null && $this->redeemedCount() >= $this->max_redemptions => 'capped',
            $this->starts_at->isFuture() => 'scheduled',
            default => 'live',
        };
    }

    /**
     * The ids of the products this item discount covers.
     *
     * @return array<int, int>
     */
    public function productIds(): array
    {
        $ids = $this->products->pluck('id')->all();

        if ($this->collection) {
            $ids = array_merge($ids, $this->collection->products->pluck('id')->all());
        }

        return array_values(array_unique($ids));
    }

    /**
     * Cents off one unit priced $unitCents (item discounts).
     */
    public function unitDiscount(int $unitCents): int
    {
        return min($unitCents, $this->discount_type === 'percent'
            ? (int) round($unitCents * $this->discount_value / 100)
            : $this->discount_value);
    }

    /**
     * Cents off a whole basket (order-level offers), never more than it.
     */
    public function basketDiscount(int $subtotalCents): int
    {
        return min($subtotalCents, $this->discount_type === 'percent'
            ? (int) round($subtotalCents * $this->discount_value / 100)
            : $this->discount_value);
    }

    /**
     * "10% off" or "$5 off".
     */
    public function offerLabel(): string
    {
        return $this->discount_type === 'percent' ? "{$this->discount_value}% off" : money($this->discount_value).' off';
    }
}
