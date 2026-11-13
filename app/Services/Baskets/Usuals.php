<?php

namespace App\Services\Baskets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * A customer's usuals: what they buy most often, counted by how many of
 * their recent orders it appeared in.
 */
class Usuals
{
    public const LIMIT = 8;

    public const MONTHS = 6;

    /**
     * Basket lines for BasketRepricer, each at the customer's usual quantity.
     *
     * @return Collection<int, array{key: string, variation_id: int, quantity: int, name: string, variation_name: ?string}>
     */
    public function linesFor(User $user): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereIn('orders.status', [Order::STATUS_PLACED, Order::STATUS_PAID])
            ->where('orders.placed_at', '>=', now()->subMonths(self::MONTHS))
            ->where('order_items.line_status', '!=', OrderItem::STATUS_REMOVED)
            ->whereNotNull('order_items.product_variation_id')
            ->groupBy('order_items.product_variation_id')
            ->selectRaw('order_items.product_variation_id as variation_id')
            ->selectRaw('MAX(order_items.name) as name, MAX(order_items.variation_name) as variation_name')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as times, SUM(order_items.quantity) as units, MAX(orders.placed_at) as last_at')
            ->orderByDesc('times')
            ->orderByDesc('units')
            ->orderByDesc('last_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($row) => [
                'key' => 'v'.$row->variation_id,
                'variation_id' => (int) $row->variation_id,
                'quantity' => max(1, (int) round($row->units / max(1, $row->times))),
                'name' => $row->name,
                'variation_name' => $row->variation_name,
            ])
            ->values();
    }
}
