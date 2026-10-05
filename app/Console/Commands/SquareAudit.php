<?php
// app/Console/Commands/SquareAudit.php

namespace App\Console\Commands;

use App\Services\Square\SquareGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SquareAudit extends Command
{
    protected $signature = 'square:audit';
    protected $description = 'Read-only health check of the Square catalogue';

    public function handle(SquareGateway $square): int
    {
        $items = $variations = $categories = 0;
        $noImage = $noDesc = $noCat = $noPrice = $untracked = [];
        $cursor = null;

        do {
            $page = $square->catalogPage($cursor);        // GET only. Nothing is changed.

            foreach ($page['objects'] ?? [] as $o) {
                $type = $o['type'] ?? '';
                if ($type === 'CATEGORY') { $categories++; continue; }
                if ($type !== 'ITEM') { continue; }

                $d = $o['item_data'] ?? [];
                $name = $d['name'] ?? '(no name)';
                $items++;

                if (empty($d['image_ids']))                                   $noImage[] = $name;
                if (empty($d['description_plaintext']) && empty($d['description'])) $noDesc[] = $name;
                if (empty($d['categories']) && empty($d['category_id']))      $noCat[] = $name;

                foreach ($d['variations'] ?? [] as $v) {
                    $variations++;
                    $vd = $v['item_variation_data'] ?? [];
                    $label = $name . ' / ' . ($vd['name'] ?? 'Regular');
                    if (empty($vd['price_money']))     $noPrice[] = $label;
                    if (empty($vd['track_inventory'])) $untracked[] = $label;
                }
            }
            $cursor = $page['cursor'] ?? null;
        } while ($cursor);

        $this->table(['Check', 'Count'], [
            ['Items', $items],
            ['Variations', $variations],
            ['Categories', $categories],
            ['Items with no image', count($noImage)],
            ['Items with no description', count($noDesc)],
            ['Items with no category', count($noCat)],
            ['Variations with no fixed price', count($noPrice)],
            ['Variations with stock NOT tracked', count($untracked)],
        ]);

        $report = "SQUARE AUDIT " . now() . "\n\n"
            . "NO IMAGE\n" . implode("\n", $noImage) . "\n\n"
            . "NO DESCRIPTION\n" . implode("\n", $noDesc) . "\n\n"
            . "NO CATEGORY\n" . implode("\n", $noCat) . "\n\n"
            . "NO FIXED PRICE\n" . implode("\n", $noPrice) . "\n\n"
            . "STOCK NOT TRACKED\n" . implode("\n", $untracked) . "\n";
        Storage::put('square-audit.txt', $report);

        $this->info('Full lists saved to storage/app/private/square-audit.txt (or storage/app/square-audit.txt on older Laravel).');
        return self::SUCCESS;
    }
}