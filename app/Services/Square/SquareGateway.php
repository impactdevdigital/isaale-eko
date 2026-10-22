<?php
// app/Services/Square/SquareGateway.php

namespace App\Services\Square;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SquareGateway
{
    private function http(): PendingRequest
    {
        $base = config('services.square.env') === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';

        return Http::baseUrl($base)
            ->withToken(config('services.square.token'))
            ->withHeaders(['Square-Version' => config('services.square.version')])
            ->acceptJson()->asJson()->timeout(20)
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

    public function catalogBatchRetrieve(array $ids): array
    {
        return $this->http()->post('/v2/catalog/batch-retrieve', ['object_ids' => $ids])->throw()->json('objects', []);
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

    public function inventoryCounts(array $variationIds = [], ?string $cursor = null): array   // ['counts' => [...]]
    {
        return $this->http()->post('/v2/inventory/counts/batch-retrieve', array_filter([
            'catalog_object_ids' => $variationIds,            // empty list = everything
            'location_ids' => [config('services.square.location_id')],
            'cursor' => $cursor,
        ]))->throw()->json();
    }

    public function getOrder(string $orderId): array
    {
        return $this->http()->get("/v2/orders/{$orderId}")->throw()->json('order', []);
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

    public function takePayment(string $sourceToken, string $squareOrderId, int $cents, string $currency, string $idempotencyKey): array
    {
        return $this->http()->post('/v2/payments', [
            'idempotency_key' => $idempotencyKey,
            'source_id' => $sourceToken,
            'order_id' => $squareOrderId,
            'location_id' => config('services.square.location_id'),
            'amount_money' => ['amount' => $cents, 'currency' => $currency],
        ])->throw()->json('payment');
    }

    public function refundPayment(string $paymentId, int $cents, string $currency, string $reason, string $idempotencyKey): array
    {
        return $this->http()->post('/v2/refunds', [
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
            config('services.square.webhook_url') . $request->getContent(),
            config('services.square.webhook_key'),
            true
        ));
        return hash_equals($expected, (string) $request->header('x-square-hmacsha256-signature'));
    }
}