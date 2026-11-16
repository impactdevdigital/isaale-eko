<?php

namespace App\Services\Baskets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SavedBasket;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Support\Collection;

/**
 * Keeps a basket as a named list the customer can rebuild later at that
 * day's prices (BasketRepricer). Saved from the cart or from a past order.
 */
class SaveBasket
{
    public function atLimit(User $user): bool
    {
        return $user->savedBaskets()->count() >= SavedBasket::MAX_PER_USER;
    }

    /**
     * Everything in the basket, ticked or not.
     */
    public function fromCart(User $user, string $name): ?SavedBasket
    {
        return $this->save($user, $name, app(Cart::class)->lines()->map(fn ($line) => [
            'product_variation_id' => $line->variation->id,
            'name' => $line->variation->product->name,
            'variation_name' => $line->variation->name,
            'quantity' => $line->quantity,
            'unit_price_cents' => $line->variation->price_cents,
        ]));
    }

    /**
     * The lines of a past order that were actually sent.
     */
    public function fromOrder(User $user, Order $order, string $name): ?SavedBasket
    {
        return $this->save($user, $name, $order->items
            ->reject(fn (OrderItem $item) => $item->line_status === OrderItem::STATUS_REMOVED || ! $item->product_variation_id)
            ->map(fn (OrderItem $item) => [
                'product_variation_id' => $item->product_variation_id,
                'name' => $item->name,
                'variation_name' => $item->variation_name,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
            ]));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function save(User $user, string $name, Collection $items): ?SavedBasket
    {
        if ($items->isEmpty() || $this->atLimit($user)) {
            return null;
        }

        $basket = $user->savedBaskets()->create(['name' => trim($name)]);
        $basket->items()->createMany($items->values()->all());

        return $basket;
    }
}
