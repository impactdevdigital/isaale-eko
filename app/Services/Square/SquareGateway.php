<?php

// app/Services/Square/SquareGateway.php

namespace App\Services\Square;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SquareGateway
{
    /**
     * The Square account card payments and refunds go to: the SQUARE_PAYMENTS_*
     * account when one is set (e.g. sandbox, for test payments on a live
     * catalogue), otherwise the main one.
     *
     * @return array{env: string, token: ?string, location_id: ?string, app_id: ?string}
     */
    public static function payments(): array
    {
        $own = config('services.square.payments', []);

        if (! filled($own['token'] ?? null)) {
            return [
                'env' => (string) config('services.square.env'),
                'token' => config('services.square.token'),
                'location_id' => config('services.square.location_id'),
                'app_id' => config('services.square.app_id'),
            ];
        }

        return [
            'env' => (string) ($own['env'] ?: 'sandbox'),
            'token' => $own['token'],
            'location_id' => $own['location_id'] ?? null,
            'app_id' => $own['app_id'] ?? null,
        ];
    }

    /**
     * True when payments go to a different Square account from the catalogue
     * (test payments on a live shop).
     */
    public static function separatePayments(): bool
    {
        return filled(config('services.square.payments.token'))
            && config('services.square.payments.token') !== config('services.square.token');
    }

    private function http(): PendingRequest
    {
        return $this->request()->asJson();
    }

    private function payHttp(): PendingRequest
    {
        $pay = self::payments();

        return $this->request($pay['env'], $pay['token'])->asJson();
    }

    private function request(?string $env = null, ?string $token = null): PendingRequest
    {
        $base = ($env ?? config('services.square.env')) === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';

        return Http::baseUrl($base)
            ->withToken($token ?? config('services.square.token'))
            ->withHeaders(['Square-Version' => config('services.square.version')])
            ->acceptJson()->timeout(20)
            ->retry(2, 500, fn ($e) => $e instanceof ConnectionException, throw: false);
    }

    // ---- reading ----
    public function listLocations(): array
    {
        return $this->http()->get('/v2/locations')->throw()->json('locations', []);
    }

    public function catalogPage(?string $cursor = null, string $types = 'ITEM,CATEGORY,IMAGE'): array   // ['objects' => [...], 'cursor' => '...']
    {
        return $this->http()->get('/v2/catalog/list', array_filter([
            'types' => $types, 'cursor' => $cursor,
        ]))->throw()->json();
    }

    public function catalogChangesSince(string $isoTime, ?string $cursor = null): array
    {
        return $this->http()->post('/v2/catalog/search', array_filter([
            'begin_time' => $isoTime,
            'include_deleted_objects' => true,
            'object_types' => ['ITEM', 'CATEGORY', 'IMAGE'],
            'cursor' => $cursor,
        ]))->throw()->json();
    }

    public function catalogObject(string $id): array
    {
        return $this->http()->get("/v2/catalog/object/{$id}")->throw()->json('object', []);
    }

    public function inventoryCounts(array $variationIds = [], ?string $cursor = null, ?string $updatedAfter = null): array   // ['counts' => [...]]
    {
        return $this->http()->post('/v2/inventory/counts/batch-retrieve', array_filter([
            'catalog_object_ids' => $variationIds,            // empty list = everything
            'location_ids' => [config('services.square.location_id')],
            'updated_after' => $updatedAfter,
            'cursor' => $cursor,
        ]))->throw()->json();
    }

    /**
     * Several catalogue objects by id (e.g. the images and categories an
     * item points at). Deleted ones are simply missing from the result.
     */
    public function catalogObjects(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->http()->post('/v2/catalog/batch-retrieve', ['object_ids' => array_values($ids)])
            ->throw()->json('objects', []);
    }

    // ---- sandbox test data (see square:seed-sandbox) ----
    public function upsertCatalog(array $objects, string $idempotencyKey): array
    {
        return $this->http()->post('/v2/catalog/batch-upsert', [
            'idempotency_key' => $idempotencyKey,
            'batches' => [['objects' => $objects]],
        ])->throw()->json();
    }

    public function uploadCatalogImage(string $itemId, string $path, string $name, string $idempotencyKey): array
    {
        $request = json_encode([
            'idempotency_key' => $idempotencyKey,
            'object_id' => $itemId,
            'is_primary' => true,
            'image' => ['type' => 'IMAGE', 'id' => '#image', 'image_data' => ['name' => $name]],
        ]);

        return $this->request()->asMultipart()
            ->attach('request', $request, null, ['Content-Type' => 'application/json'])
            ->attach('image_file', file_get_contents($path), basename($path), ['Content-Type' => mime_content_type($path)])
            ->post('/v2/catalog/images')
            ->throw()->json('image', []);
    }

    public function setInventory(array $counts, string $idempotencyKey): array   // [variationId => quantity]
    {
        $now = now()->toIso8601ZuluString();

        return $this->http()->post('/v2/inventory/changes/batch-create', [
            'idempotency_key' => $idempotencyKey,
            'changes' => collect($counts)->map(fn ($quantity, $id) => [
                'type' => 'PHYSICAL_COUNT',
                'physical_count' => [
                    'catalog_object_id' => $id,
                    'location_id' => config('services.square.location_id'),
                    'state' => 'IN_STOCK',
                    'quantity' => (string) $quantity,
                    'occurred_at' => $now,
                ],
            ])->values()->all(),
        ])->throw()->json();
    }

    public function getOrder(string $orderId): array
    {
        return $this->http()->get("/v2/orders/{$orderId}")->throw()->json('order', []);
    }

    /**
     * Register a domain for Apple Pay on the Web. Square fetches
     * https://{domain}/.well-known/apple-developer-merchantid-domain-association
     * to verify it, so deploy that file first. Returns VERIFIED or VERIFICATION_PENDING.
     */
    public function registerApplePayDomain(string $domain): string
    {
        return $this->http()->post('/v2/apple-pay/domains', ['domain_name' => $domain])->throw()->json('status', 'UNKNOWN');
    }

    // ---- writing (always with an idempotency key) ----
    public function calculateOrder(array $lineItems): array
    {
        return $this->http()->post('/v2/orders/calculate', ['order' => [
            'location_id' => config('services.square.location_id'),
            'line_items' => $lineItems,
        ]])->throw()->json('order');
    }

    public function createOrder(array $order, string $idempotencyKey): array
    {
        $order['location_id'] = config('services.square.location_id');

        return $this->http()->post('/v2/orders', [
            'idempotency_key' => $idempotencyKey, 'order' => $order,
        ])->throw()->json('order');
    }

    /**
     * Pay a Square order. $sourceToken is a Web Payments SDK token, or
     * 'EXTERNAL' with $external details for money taken outside Square
     * (an Interac e-Transfer).
     */
    public function takePayment(string $sourceToken, string $squareOrderId, int $cents, string $currency, string $idempotencyKey, ?string $referenceId = null, ?string $note = null, ?string $buyerEmail = null, ?array $external = null): array
    {
        return $this->http()->post('/v2/payments', array_filter([
            'idempotency_key' => $idempotencyKey,
            'source_id' => $sourceToken,
            'order_id' => $squareOrderId,
            'location_id' => config('services.square.location_id'),
            'amount_money' => ['amount' => $cents, 'currency' => $currency],
            'reference_id' => $referenceId,
            'note' => $note,
            'buyer_email_address' => $buyerEmail,
            'external_details' => $external,
            'autocomplete' => true,
        ]))->throw()->json('payment');
    }

    /**
     * Change an order, e.g. move its fulfilment along. $order needs the
     * current version from getOrder().
     */
    public function updateOrder(string $orderId, array $order, string $idempotencyKey): array
    {
        return $this->http()->put("/v2/orders/{$orderId}", [
            'idempotency_key' => $idempotencyKey, 'order' => $order,
        ])->throw()->json('order');
    }

    // ---- customers ----
    public function findCustomerByEmail(string $email): ?string
    {
        return $this->http()->post('/v2/customers/search', [
            'limit' => 1,
            'query' => ['filter' => ['email_address' => ['exact' => $email]]],
        ])->throw()->json('customers.0.id');
    }

    public function createCustomer(array $customer, string $idempotencyKey): string
    {
        return $this->http()->post('/v2/customers', ['idempotency_key' => $idempotencyKey, ...$customer])
            ->throw()->json('customer.id');
    }

    /**
     * Charge a Web Payments SDK token without a Square order. reference_id
     * ties the payment to our order number (and to webhooks).
     */
    public function chargeToken(string $sourceToken, int $cents, string $currency, string $idempotencyKey, string $referenceId, ?string $note = null, ?string $buyerEmail = null): array
    {
        return $this->payHttp()->post('/v2/payments', array_filter([
            'idempotency_key' => $idempotencyKey,
            'source_id' => $sourceToken,
            'location_id' => self::payments()['location_id'],
            'amount_money' => ['amount' => $cents, 'currency' => $currency],
            'reference_id' => $referenceId,
            'note' => $note,
            'buyer_email_address' => $buyerEmail,
            'autocomplete' => true,
        ]))->throw()->json('payment');
    }

    public function getPayment(string $paymentId): array
    {
        return $this->payHttp()->get("/v2/payments/{$paymentId}")->throw()->json('payment', []);
    }

    /**
     * Payments taken in a time window, newest first: ['payments' => [...], 'cursor' => ?string].
     */
    public function listPayments(string $beginTime, string $endTime, ?string $cursor = null): array
    {
        return $this->payHttp()->get('/v2/payments', array_filter([
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'location_id' => self::payments()['location_id'],
            'sort_order' => 'DESC',
            'cursor' => $cursor,
        ]))->throw()->json() + ['payments' => [], 'cursor' => null];
    }

    public function refundPayment(string $paymentId, int $cents, string $currency, string $reason, string $idempotencyKey): array
    {
        return $this->payHttp()->post('/v2/refunds', [
            'idempotency_key' => $idempotencyKey,
            'payment_id' => $paymentId,
            'amount_money' => ['amount' => $cents, 'currency' => $currency],
            'reason' => $reason,
        ])->throw()->json('refund');
    }

    // ---- is this webhook really from Square? ----
    public function isValidWebhook(Request $request): bool
    {
        $expected = base64_encode(hash_hmac(
            'sha256',
            config('services.square.webhook_url').$request->getContent(),
            config('services.square.webhook_key'),
            true
        ));

        return hash_equals($expected, (string) $request->header('x-square-hmacsha256-signature'));
    }
}
