<?php

namespace App\Services\Square;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Copies website orders into Square as real orders: the items (linked to
 * the Square catalogue, so Square's stock goes down), the customer, and the
 * pickup or delivery details. Card orders are created just before the
 * charge and paid against; e-Transfer orders are created and recorded as
 * paid outside Square once staff confirm the money arrived. After that,
 * staff moving an order along (preparing, ready, collected) moves the
 * Square fulfilment along too.
 *
 * Nothing here may break checkout or the staff queue: every failure is
 * logged and saved on the order as square_sync_error instead of thrown.
 */
class OrderSync
{
    /** Our fulfilment progress as Square fulfilment states. */
    private const STATES = [
        null => 'PROPOSED',
        Order::FULFILMENT_PREPARING => 'RESERVED',
        Order::FULFILMENT_READY => 'PREPARED',
        Order::FULFILMENT_COMPLETED => 'COMPLETED',
    ];

    private const FULFILMENT_UID = 'website-fulfilment';

    public function __construct(private SquareGateway $square) {}

    public static function enabled(): bool
    {
        // Test payments go to another (sandbox) account, so there is no real
        // payment to attach a live Square order to: keep test orders out of it.
        return (bool) config('services.square.sync_orders', true)
            && filled(config('services.square.token'))
            && ! SquareGateway::separatePayments();
    }

    /**
     * Create the Square order for a website order. Returns its id, or null
     * when it couldn't be created or its total doesn't match ours (the card
     * is then charged without an order, as before, so the shopper can still pay).
     */
    public function create(Order $order, string $attemptKey): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $order->loadMissing('items', 'user');

        try {
            $created = $this->square->createOrder($this->payload($order), $this->key($order, 'order-'.$attemptKey));
        } catch (Throwable $e) {
            $this->failed($order, 'create', $e);

            return null;
        }

        $total = $created['total_money']['amount'] ?? null;

        if ($total !== $order->total_cents) {
            $this->failed($order, 'create', null, "Square total {$total} doesn't match the order total {$order->total_cents}");
            $this->cancel($created);

            return null;
        }

        $order->forceFill(['square_order_id' => $created['id'], 'square_sync_error' => null])->saveQuietly();

        return $created['id'];
    }

    /**
     * A card payment against the Square order was declined: close it, so
     * the abandoned attempt doesn't linger in Square as an open order.
     */
    public function abandon(Order $order): void
    {
        if (! $order->square_order_id) {
            return;
        }

        try {
            $this->cancel($this->square->getOrder($order->square_order_id));
        } catch (Throwable $e) {
            Log::info('Could not cancel abandoned Square order '.$order->square_order_id, ['error' => $e->getMessage()]);
        }

        $order->forceFill(['square_order_id' => null])->saveQuietly();
    }

    public function markSynced(Order $order): void
    {
        $order->forceFill(['square_synced_at' => now(), 'square_sync_error' => null])->saveQuietly();
    }

    /**
     * Staff confirmed an Interac e-Transfer: put the order in Square and
     * record it as paid by bank transfer (no money moves through Square).
     */
    public function pushPaidOutsideSquare(Order $order): bool
    {
        if (! self::enabled() || $order->square_synced_at) {
            return false;
        }

        $squareOrderId = $order->square_order_id ?? $this->create($order, 'etransfer');

        if (! $squareOrderId) {
            return false;
        }

        try {
            $this->square->takePayment(
                sourceToken: 'EXTERNAL',
                squareOrderId: $squareOrderId,
                cents: $order->total_cents,
                currency: config('shop.currency'),
                idempotencyKey: $this->key($order, 'etransfer-payment'),
                referenceId: $order->number,
                note: 'Interac e-Transfer, confirmed by staff',
                external: ['type' => 'BANK_TRANSFER', 'source' => 'Interac e-Transfer'],
            );
        } catch (Throwable $e) {
            $this->failed($order, 'record e-Transfer payment', $e);

            return false;
        }

        $this->markSynced($order);
        $this->pushFulfilment($order->refresh());

        return true;
    }

    /**
     * Move the Square fulfilment to match ours. Square only shows an order
     * as completed once it is paid and its fulfilment is completed.
     */
    public function pushFulfilment(Order $order): void
    {
        if (! self::enabled() || ! $order->square_order_id || ! $order->square_synced_at) {
            return;
        }

        $state = in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_UNCOLLECTED, Order::STATUS_REFUNDED], true)
            ? 'CANCELED'
            : (self::STATES[$order->fulfilment_status] ?? 'PROPOSED');

        try {
            $current = $this->square->getOrder($order->square_order_id);
            $fulfilment = collect($current['fulfillments'] ?? [])->firstWhere('uid', self::FULFILMENT_UID);

            if (! $fulfilment || in_array($fulfilment['state'], [$state, 'COMPLETED', 'CANCELED', 'FAILED'], true)) {
                return;
            }

            // Square leaves a paid order open until it's told it's done.
            $this->square->updateOrder($order->square_order_id, array_filter([
                'version' => $current['version'],
                'state' => $state === 'COMPLETED' && ($current['state'] ?? null) === 'OPEN' ? 'COMPLETED' : null,
                'fulfillments' => [['uid' => self::FULFILMENT_UID, 'state' => $state]],
            ]), $this->key($order, 'state-'.$state));
        } catch (Throwable $e) {
            $this->failed($order, 'update fulfilment to '.$state, $e);
        }
    }

    /**
     * The order exactly as the shopper paid for it. Prices come from our
     * order lines, never from Square's catalogue, so the totals always agree.
     */
    public function payload(Order $order): array
    {
        $currency = config('shop.currency');
        $money = fn (int $cents) => ['amount' => $cents, 'currency' => $currency];

        $lines = $order->items->map(function ($item) use ($money) {
            $squareId = $item->variation?->square_id;

            return array_filter([
                'uid' => 'item-'.$item->id,
                'quantity' => (string) $item->quantity,
                'catalog_object_id' => $squareId,
                // Without a catalogue link Square needs the name itself.
                'name' => $squareId ? null : $item->name,
                'variation_name' => $squareId ? null : $item->variation_name,
                'base_price_money' => $money($item->unit_price_cents),
            ]);
        })->values()->all();

        $discounts = $order->discount_cents > 0 ? [[
            'uid' => 'website-discount',
            'name' => $this->discountName($order),
            'type' => 'FIXED_AMOUNT',
            'amount_money' => $money($order->discount_cents),
            'scope' => 'ORDER',
        ]] : [];

        $charges = $order->delivery_fee_cents > 0 ? [[
            'uid' => 'website-delivery',
            'name' => 'Delivery',
            'amount_money' => $money($order->delivery_fee_cents),
            'calculation_phase' => 'SUBTOTAL_PHASE',
            'taxable' => false,
        ]] : [];

        return array_filter([
            'reference_id' => $order->number,
            'source' => ['name' => 'Website'],
            'customer_id' => $this->customerId($order),
            'line_items' => $lines,
            'discounts' => $discounts,
            'service_charges' => $charges,
            'fulfillments' => [$this->fulfilment($order)],
            'metadata' => ['website_order' => $order->number, 'payment_method' => $order->payment_method],
        ]);
    }

    private function fulfilment(Order $order): array
    {
        $recipient = array_filter([
            'display_name' => $order->contact_name ?: $order->user?->name,
            'email_address' => $order->contact_email ?: $order->user?->email,
            'phone_number' => $order->contact_phone ?: $order->user?->phone,
        ]);

        $window = $order->pickup_starts_at && $order->pickup_ends_at
            ? 'PT'.max(1, (int) $order->pickup_starts_at->diffInMinutes($order->pickup_ends_at)).'M'
            : null;

        $note = "Website order {$order->number}";

        if ($order->fulfilment === 'delivery') {
            $address = $order->delivery_address ?? [];

            return [
                'uid' => self::FULFILMENT_UID,
                'type' => 'DELIVERY',
                'state' => 'PROPOSED',
                'delivery_details' => array_filter([
                    'recipient' => $recipient + ['address' => array_filter([
                        'address_line_1' => $address['street'] ?? null,
                        'locality' => $address['city'] ?? null,
                        'administrative_district_level_1' => $address['region'] ?? null,
                        'postal_code' => $address['postal_code'] ?? null,
                        'country' => $address['country'] ?? 'CA',
                    ])],
                    'schedule_type' => $order->pickup_starts_at ? 'SCHEDULED' : 'ASAP',
                    'deliver_at' => $order->pickup_starts_at?->toIso8601ZuluString(),
                    'delivery_window_duration' => $window,
                    'note' => $note,
                ]),
            ];
        }

        return [
            'uid' => self::FULFILMENT_UID,
            'type' => 'PICKUP',
            'state' => 'PROPOSED',
            'pickup_details' => array_filter([
                'recipient' => $recipient,
                'schedule_type' => $order->pickup_starts_at ? 'SCHEDULED' : 'ASAP',
                'pickup_at' => $order->pickup_starts_at?->toIso8601ZuluString(),
                'pickup_window_duration' => $window,
                'note' => $note,
            ]),
        ];
    }

    private function discountName(Order $order): string
    {
        return $order->discountLabel();
    }

    /**
     * The shopper's Square customer: found by email, created the first
     * time. Kept on the user so later orders reuse it. An order still goes
     * to Square without one if Square refuses the details.
     */
    private function customerId(Order $order): ?string
    {
        $user = $order->user;
        $email = $order->contact_email ?: $user?->email;

        if ($user?->square_customer_id) {
            return $user->square_customer_id;
        }

        if (! $email) {
            return null;
        }

        try {
            $id = $this->square->findCustomerByEmail($email);

            if (! $id) {
                [$given, $family] = $this->splitName($order->contact_name ?: $user?->name ?: '');
                $customer = array_filter([
                    'given_name' => $given,
                    'family_name' => $family,
                    'email_address' => $email,
                    'phone_number' => $order->contact_phone ?: $user?->phone,
                    'reference_id' => $user ? 'website-user-'.$user->id : null,
                    'note' => 'Created by the website',
                ]);

                try {
                    $id = $this->square->createCustomer($customer, 'customer-'.md5(mb_strtolower($email)));
                } catch (RequestException) {
                    // Most often a phone number Square won't accept; the email is enough.
                    unset($customer['phone_number']);
                    $id = $this->square->createCustomer($customer, 'customer-np-'.md5(mb_strtolower($email)));
                }
            }
        } catch (Throwable $e) {
            Log::warning('Square customer lookup failed for order '.$order->number, ['error' => $e->getMessage()]);

            return null;
        }

        if ($user instanceof User) {
            $user->forceFill(['square_customer_id' => $id])->saveQuietly();
        }

        return $id;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [($parts[0] ?? '') ?: null, ($parts[1] ?? '') ?: null];
    }

    private function cancel(array $squareOrder): void
    {
        if (($squareOrder['state'] ?? null) !== 'OPEN' || ! isset($squareOrder['id'], $squareOrder['version'])) {
            return;
        }

        try {
            $this->square->updateOrder($squareOrder['id'], ['version' => $squareOrder['version'], 'state' => 'CANCELED'], 'cancel-'.$squareOrder['id']);
        } catch (Throwable $e) {
            Log::info('Could not cancel Square order '.$squareOrder['id'], ['error' => $e->getMessage()]);
        }
    }

    private function key(Order $order, string $suffix): string
    {
        // Square idempotency keys are limited to 45 characters (192 for orders, but keep one rule).
        return substr($order->number.'-'.$suffix, 0, 45);
    }

    private function failed(Order $order, string $step, ?Throwable $e, ?string $reason = null): void
    {
        $detail = $reason ?? ($e instanceof RequestException
            ? ($e->response->json('errors.0.code') ?? 'HTTP '.$e->response->status()).': '.($e->response->json('errors.0.detail') ?? '')
            : $e?->getMessage());

        Log::warning("Square order sync ({$step}) failed for order {$order->number}", ['error' => $detail]);
        $order->forceFill(['square_sync_error' => mb_substr("{$step}: {$detail}", 0, 250)])->saveQuietly();
    }
}
