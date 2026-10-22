<?php

namespace App\Services\Checkout;

/**
 * Can we deliver to this address, and for what fee? Coverage is a list of
 * postal code prefixes (config/shop.php) until real delivery-radius data
 * is wired in.
 */
class DeliveryCheck
{
    /**
     * Canadian postal code, e.g. "E2L 4L1". Returns null when the format is wrong.
     */
    public static function normalisePostalCode(string $postalCode): ?string
    {
        $compact = strtoupper(preg_replace('/\s+/', '', $postalCode));

        return preg_match('/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z]\d[ABCEGHJ-NPRSTV-Z]\d$/', $compact)
            ? substr($compact, 0, 3).' '.substr($compact, 3)
            : null;
    }

    /**
     * @param  array<string, string|null>  $address
     * @return array{status: string, message: string} status is ok, invalid_postcode or out_of_area
     */
    public function check(array $address): array
    {
        if (($address['country'] ?? 'CA') !== 'CA') {
            return ['status' => 'out_of_area', 'message' => 'We can’t deliver to this address. Edit it or choose Pickup.'];
        }

        $postal = self::normalisePostalCode($address['postal_code'] ?? '');

        if (! $postal) {
            return ['status' => 'invalid_postcode', 'message' => 'Check the postal code — Canadian codes look like E2L 4L1.'];
        }

        $covered = collect(config('shop.delivery.postal_prefixes'))
            ->contains(fn (string $prefix) => str_starts_with($postal, strtoupper(trim($prefix))));

        return $covered
            ? ['status' => 'ok', 'message' => 'We deliver to this address.']
            : ['status' => 'out_of_area', 'message' => 'We can’t deliver to this address. Edit it or choose Pickup.'];
    }
}
