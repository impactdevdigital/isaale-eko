<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\Square\SquareGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Test data only: copies the demo catalogue into the Square SANDBOX so the
 * sync can be tried end to end. Refuses to run against production.
 */
class SquareSeedSandbox extends Command
{
    protected $signature = 'square:seed-sandbox {--force : Add the items even if the sandbox already has some}';

    protected $description = 'Load the demo products into the Square sandbox account (never production)';

    public function handle(SquareGateway $square): int
    {
        if (config('services.square.env') !== 'sandbox') {
            $this->error('This only runs with SQUARE_ENV=sandbox. It will never write to a live Square account.');

            return self::FAILURE;
        }

        if (! $this->option('force') && collect($square->catalogPage()['objects'] ?? [])->contains('type', 'ITEM')) {
            $this->warn('The sandbox already has items. Use --force to add the demo products anyway.');

            return self::FAILURE;
        }

        $products = Product::whereNull('square_id')->with(['variations', 'category'])->get();
        $currency = config('shop.currency');
        $categories = $products->pluck('category')->filter()->unique('id');

        $objects = $categories->map(fn (Category $c) => [
            'type' => 'CATEGORY',
            'id' => '#category-'.$c->id,
            'category_data' => ['name' => $c->name],
        ])->values()->all();

        foreach ($products as $product) {
            $objects[] = [
                'type' => 'ITEM',
                'id' => '#item-'.$product->id,
                'present_at_all_locations' => true,
                'item_data' => array_filter([
                    'name' => $product->name,
                    'description' => $product->description,
                    'categories' => $product->category ? [['id' => '#category-'.$product->category_id]] : null,
                    'reporting_category' => $product->category ? ['id' => '#category-'.$product->category_id] : null,
                    'variations' => $product->variations->map(fn ($v) => [
                        'type' => 'ITEM_VARIATION',
                        'id' => '#variation-'.$v->id,
                        'present_at_all_locations' => true,
                        'item_variation_data' => [
                            'item_id' => '#item-'.$product->id,
                            'name' => $v->name ?: 'Regular',
                            'ordinal' => $v->sort_order,
                            'pricing_type' => 'FIXED_PRICING',
                            'price_money' => ['amount' => $v->price_cents, 'currency' => $currency],
                            'track_inventory' => true,
                        ],
                    ])->all(),
                ]),
            ];
        }

        $result = $square->upsertCatalog($objects, (string) Str::uuid());
        $ids = collect($result['id_mappings'] ?? [])->pluck('object_id', 'client_object_id');
        $this->info('Created '.$products->count().' items and '.$categories->count().' categories in the sandbox.');

        // Stock: the demo numbers, or 50 where the demo didn't track stock.
        $counts = $products->flatMap->variations
            ->mapWithKeys(fn ($v) => [$ids['#variation-'.$v->id] ?? null => $v->stock_quantity ?? 50])
            ->filter(fn ($q, $id) => $id !== '');
        $counts->chunk(100)->each(fn ($chunk) => $square->setInventory($chunk->all(), (string) Str::uuid()));
        $this->info('Set stock for '.$counts->count().' variations.');

        // Square takes JPEG, PNG or GIF; the demo photos are WebP, so convert them.
        if (! function_exists('imagecreatefromwebp')) {
            $this->warn('PHP GD with WebP support is missing, so no photos were uploaded.');
        } else {
            $bar = $this->output->createProgressBar($products->count());
            $bar->setFormat(' Uploading photos %current%/%max% [%bar%]');
            $png = tempnam(sys_get_temp_dir(), 'sq').'.png';

            foreach ($products as $product) {
                $path = public_path((string) $product->image);

                if ($product->image && is_file($path) && isset($ids['#item-'.$product->id])) {
                    $image = str_ends_with($path, '.webp') ? imagecreatefromwebp($path) : null;

                    if ($image) {
                        imagepng($image, $png);
                        $path = $png;
                    }

                    $square->uploadCatalogImage($ids['#item-'.$product->id], $path, $product->name, (string) Str::uuid());
                }

                $bar->advance();
            }

            @unlink($png);
            $bar->finish();
            $this->newLine();
        }

        $this->info('Done. Run `php artisan square:sync --full` to pull them back in.');

        return self::SUCCESS;
    }
}
