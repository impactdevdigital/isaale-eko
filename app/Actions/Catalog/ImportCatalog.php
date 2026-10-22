<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Variation;
use App\Services\Square\SquareGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImportCatalog
{
    private array $imageCache = [];

    public function __construct(private SquareGateway $square) {}

    /**
     * $initial = first ever import (skips New Arrival tagging).
     * $since = only changes after this timestamp (incremental webhook / check).
     */
    public function handle(bool $initial = false, ?string $since = null): array
    {
        $stats = ['items' => 0, 'categories' => 0];

        if ($since) {
            $this->eachPage(fn ($c) => $this->square->catalogChangesSince($since, $c), function (Collection $objects) use (&$stats, $initial) {
                $stats['categories'] += $this->saveCategories($objects->where('type', 'CATEGORY'));
                $this->collectImages($objects);
                $stats['items'] += $this->saveItems($objects->where('type', 'ITEM'), $initial);
            });

            return $stats;
        }

        // Full import: categories first so products can link immediately
        $this->eachPage(fn ($c) => $this->square->catalogPage($c, 'CATEGORY'), function (Collection $objects) use (&$stats) {
            $stats['categories'] += $this->saveCategories($objects);
        });

        $this->eachPage(fn ($c) => $this->square->catalogPage($c, 'ITEM,IMAGE'), function (Collection $objects) use (&$stats, $initial) {
            $this->collectImages($objects);
            $stats['items'] += $this->saveItems($objects->where('type', 'ITEM'), $initial);
        });

        return $stats;
    }

    private function eachPage(callable $fetch, callable $each): void
    {
        $cursor = null;
        do {
            $page = $fetch($cursor);
            $each(collect($page['objects'] ?? []));
            $cursor = $page['cursor'] ?? null;
        } while ($cursor);
    }

    // ---------------- Categories ----------------
    private function saveCategories(Collection $objects): int
    {
        $n = 0;

        $sorted = $objects
            ->filter(fn ($o) => ($o['category_data']['category_type'] ?? 'REGULAR_CATEGORY') === 'REGULAR_CATEGORY')
            ->sortBy(fn ($o) => empty($o['category_data']['parent_category']['id'] ?? null) ? 0 : 1);

        foreach ($sorted as $o) {
            if (!empty($o['is_deleted'])) {
                $this->markCategoryDeleted($o['id']);
                continue;
            }

            $d = $o['category_data'] ?? [];
            $parentId = $d['parent_category']['id'] ?? null;

            if (!$parentId) {
                $this->saveTopCategory($o['id'], $d['name'] ?? 'Category');
                $n++;
                continue;
            }

            $top = Category::where('square_category_id', $parentId)->first()
                ?? SubCategory::where('square_category_id', $parentId)->first()?->category;

            if (!$top) {
                continue;
            }

            $this->saveSubCategory($o['id'], $d['name'] ?? 'Sub-category', $top);
            $n++;
        }

        return $n;
    }

    private function saveTopCategory(string $squareId, string $name): void
    {
        $cat = Category::firstOrNew(['square_category_id' => $squareId]);
        if (!$cat->exists) {
            $cat->slug = $this->uniqueSlug(Category::class, $name);
        }
        if ($cat->status === 'deleted') {
            $cat->status = 'active';
        }

        $cat->name = $name;
        $cat->synced_at = now();
        $cat->save();
    }

    private function saveSubCategory(string $squareId, string $name, Category $top): void
    {
        $sub = SubCategory::firstOrNew(['square_category_id' => $squareId]);
        if (!$sub->exists) {
            $sub->slug = $this->uniqueSlug(SubCategory::class, $name, ['category_id' => $top->id]);
        }
        if ($sub->status === 'deleted') {
            $sub->status = 'active';
        }

        $sub->category_id = $top->id;
        $sub->name = $name;
        $sub->synced_at = now();
        $sub->save();
    }

    private function markCategoryDeleted(string $squareId): void
    {
        Category::where('square_category_id', $squareId)->update(['status' => 'deleted']);
        SubCategory::where('square_category_id', $squareId)->update(['status' => 'deleted']);
    }

    // ---------------- Items ----------------
    private function collectImages(Collection $objects): void
    {
        foreach ($objects->where('type', 'IMAGE') as $o) {
            $this->imageCache[$o['id']] = $o['image_data']['url'] ?? null;
        }
    }

    private function saveItems(Collection $objects, bool $initial): int
    {
        $n = 0;
        foreach ($objects as $o) {
            $this->saveItem($o, $initial);
            $n++;
        }
        return $n;
    }

    private function saveItem(array $o, bool $initial): void
    {
        if (!empty($o['is_deleted'])) {
            Product::where('square_item_id', $o['id'])->update(['status' => 'deleted']);
            return;
        }

        $d = $o['item_data'] ?? [];
        $product = Product::firstOrNew(['square_item_id' => $o['id']]);
        $isNew = !$product->exists;

        [$categoryId, $subCategoryId] = $this->resolveCategories($d);
        $imageId = $d['image_ids'][0] ?? null;

        $product->fill([
            'name' => $d['name'] ?? 'Unnamed',
            'description' => $d['description_plaintext'] ?? $d['description'] ?? null,
            'square_version' => $o['version'] ?? null,
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
            'image_url' => $imageId ? $this->imageUrl($imageId) : null,
            'synced_at' => now(),
        ]);

        if ($product->status === 'deleted') {
            $product->status = 'active';
        }

        if ($isNew) {
            $product->slug = $this->uniqueSlug(Product::class, $d['name'] ?? 'item');
            $product->first_seen_at = now();
        }

        $product->save();

        $seen = [];
        foreach ($d['variations'] ?? [] as $v) {
            $vd = $v['item_variation_data'] ?? [];
            $seen[] = $v['id'];

            $variation = Variation::withTrashed()->firstOrNew(['square_variation_id' => $v['id']]);
            $variation->fill([
                'product_id' => $product->id,
                'name' => $vd['name'] ?? 'Regular',
                'sku' => $vd['sku'] ?? null,
                'price_cents' => $vd['price_money']['amount'] ?? null,
                'currency' => $vd['price_money']['currency'] ?? config('shop.currency', 'GBP'),
                'tracks_inventory' => (bool) ($vd['track_inventory'] ?? false),
            ]);
            $variation->save();

            if ($variation->trashed()) {
                $variation->restore();
            }
        }

        // Variations no longer in Square stop being sold online
        $product->variations()->whereNotIn('square_variation_id', $seen)->delete();
    }

    private function resolveCategories(array $d): array
    {
        $squareId = $d['reporting_category']['id'] ?? $d['categories'][0]['id'] ?? $d['category_id'] ?? null;
        if (!$squareId) {
            return [null, null];
        }

        if ($sub = SubCategory::where('square_category_id', $squareId)->first()) {
            return [$sub->category_id, $sub->id];
        }

        return [Category::where('square_category_id', $squareId)->value('id'), null];
    }

    private function imageUrl(string $imageId): ?string
    {
        return $this->imageCache[$imageId] ??= ($this->square->catalogObject($imageId)['image_data']['url'] ?? null);
    }

    private function uniqueSlug(string $model, string $name, array $scope = []): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $n = 2;
        while ($model::where($scope)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
