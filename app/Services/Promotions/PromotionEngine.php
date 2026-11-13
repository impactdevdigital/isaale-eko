<?php

namespace App\Services\Promotions;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Analytics;
use Illuminate\Support\Collection;

/**
 * Decides, on our server, which live promotion a basket gets. One discount
 * per order: the best eligible promotion is weighed against a promo or
 * reward code in CartTotals, and the shopper gets the better one.
 */
class PromotionEngine
{
    /** @var Collection<int, Promotion>|null */
    private ?Collection $live = null;

    /**
     * The best promotion for these basket lines and this shopper, if any.
     *
     * @param  Collection<int, object>  $checkoutLines  ticked, available cart lines
     */
    public function best(Collection $checkoutLines, ?User $user = null, ?string $email = null): ?AppliedPromotion
    {
        if ($checkoutLines->isEmpty()) {
            return null;
        }

        $user ??= $email ? User::where('email', strtolower(trim($email)))->first() : null;
        $subtotal = $checkoutLines->sum(fn ($line) => $line->variation->price_cents * $line->quantity);

        return $this->live()
            ->map(fn (Promotion $promotion) => $this->eligible($promotion, $user, $email)
                ? $this->applyTo($promotion, $checkoutLines, $subtotal)
                : null)
            ->filter(fn (?AppliedPromotion $applied) => $applied && $applied->discount > 0)
            ->sortByDesc('discount')
            ->first();
    }

    /**
     * A live item discount covering this product, for the product page badge.
     */
    public function forProduct(Product $product): ?Promotion
    {
        return $this->live()
            ->filter(fn (Promotion $promotion) => $promotion->template === Promotion::TEMPLATE_ITEM_DISCOUNT
                && in_array($product->id, $promotion->productIds(), true))
            ->sortByDesc(fn (Promotion $promotion) => $promotion->unitDiscount($product->defaultVariation?->price_cents ?? 0))
            ->first();
    }

    /**
     * Whether this shopper may use this promotion: audience and the limit
     * per customer. Without an account or email we only know they're a
     * shopper, so only offers for everyone (with no per-customer limit) apply.
     */
    public function eligible(Promotion $promotion, ?User $user, ?string $email = null): bool
    {
        $audience = $promotion->template === Promotion::TEMPLATE_FIRST_ORDER ? 'first_time' : $promotion->audience;

        if (! $user && ! $email) {
            return $audience === 'everyone' && $promotion->per_customer_limit === null;
        }

        $orders = $this->pastOrders($user, $email);

        $inAudience = match ($audience) {
            'everyone' => true,
            'first_time' => $orders->doesntExist(),
            'lapsed' => $orders->exists() && $orders->max('placed_at') < now()->subDays(Promotion::LAPSED_AFTER_DAYS),
            'loyalty_members' => $user && PointTransaction::where('user_id', $user->id)->exists(),
            default => false,
        };

        if (! $inAudience) {
            return false;
        }

        return $promotion->per_customer_limit === null
            || ! $user
            || $promotion->redeemedCount($user) < $promotion->per_customer_limit;
    }

    /**
     * Customers a promotion would reach today, for the admin preview.
     */
    public function audienceSize(string $audience): int
    {
        $customers = User::query()->where('is_admin', false);
        $placed = fn ($query) => $query->whereNotIn('status', self::NOT_PLACED);

        return match ($audience) {
            'first_time' => (clone $customers)->whereDoesntHave('orders', $placed)->count(),
            'lapsed' => (clone $customers)->whereHas('orders', $placed)
                ->whereDoesntHave('orders', fn ($query) => $placed($query)->where('placed_at', '>=', now()->subDays(Promotion::LAPSED_AFTER_DAYS)))
                ->count(),
            'loyalty_members' => (clone $customers)->whereHas('pointTransactions')->count(),
            default => (clone $customers)->count(),
        };
    }

    /**
     * Record that a shopper was shown a promotion, once per session.
     */
    public function recordExposure(?AppliedPromotion $applied, string $where): void
    {
        if (! $applied) {
            return;
        }

        $key = 'promotions.seen.'.$applied->promotion->id;

        if (! session()->has($key)) {
            session()->put($key, true);
            Analytics::track('promotion_exposed', ['promotion_id' => $applied->promotion->id, 'where' => $where]);
        }
    }

    private const NOT_PLACED = [Order::STATUS_AWAITING_PAYMENT, Order::STATUS_PAYMENT_FAILED, Order::STATUS_CANCELLED];

    private function pastOrders(?User $user, ?string $email)
    {
        return Order::query()
            ->whereNotIn('status', self::NOT_PLACED)
            ->where(function ($query) use ($user, $email) {
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
                if ($email) {
                    $query->orWhere('contact_email', strtolower(trim($email)));
                }
            });
    }

    private function applyTo(Promotion $promotion, Collection $checkoutLines, int $subtotal): ?AppliedPromotion
    {
        if ($promotion->min_subtotal_cents && $subtotal < $promotion->min_subtotal_cents) {
            return null;
        }

        if ($promotion->template === Promotion::TEMPLATE_FIRST_ORDER) {
            return new AppliedPromotion($promotion, $promotion->basketDiscount($subtotal));
        }

        $ids = $promotion->productIds();
        $discount = $checkoutLines
            ->filter(fn ($line) => in_array($line->variation->product_id, $ids, true))
            ->sum(fn ($line) => $promotion->unitDiscount($line->variation->price_cents) * $line->quantity);

        return new AppliedPromotion($promotion, (int) $discount);
    }

    /**
     * @return Collection<int, Promotion>
     */
    private function live(): Collection
    {
        return $this->live ??= Promotion::live()
            ->with(['products:id', 'collection.products:id'])
            ->get()
            ->filter->hasRoom()
            ->values();
    }
}
