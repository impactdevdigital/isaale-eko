<?php

namespace App\Actions\Catalog;

use App\Models\InventorySnapshot;
use App\Models\Setting;
use App\Models\Variation;
use App\Services\Square\SquareGateway;

class ImportInventory
{
    public function __construct(private SquareGateway $square) {}

    /**
     * Pass Square variation ids to refresh specific variations, or empty array to refresh all counts.
     */
    public function handle(array $squareVariationIds = []): int
    {
        $n = 0;
        $cursor = null;

        do {
            $page = $this->square->inventoryCounts($squareVariationIds, $cursor);
            foreach ($page['counts'] ?? [] as $c) {
                if (($c['state'] ?? '') !== 'IN_STOCK') {
                    continue;
                }

                $variation = Variation::where('square_variation_id', $c['catalog_object_id'])->first();
                if (!$variation) {
                    continue;
                }

                $qty = (int) floor((float) $c['quantity']);

                InventorySnapshot::updateOrCreate(
                    ['variation_id' => $variation->id],
                    [
                        'quantity' => $qty,
                        'state' => $this->state($qty, $variation),
                        'synced_at' => now(),
                    ]
                );
                $n++;
            }
            $cursor = $page['cursor'] ?? null;
        } while ($cursor);

        return $n;
    }

    private function state(int $qty, Variation $v): string
    {
        if (!$v->tracks_inventory) {
            return 'out'; // stock not tracked in Square = treated as Out of Stock
        }

        if ($qty <= 0) {
            return 'out';
        }

        $lowThreshold = (int) Setting::get('low_stock_threshold', 5);
        return $qty <= $lowThreshold ? 'low' : 'in';
    }
}
