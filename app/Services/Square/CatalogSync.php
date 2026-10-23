<?php

namespace App\Services\Square;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Copies the Square catalogue and stock into our product tables. Square is
 * the source of truth for names, prices, categories, photos and stock; our
 * own extras (badges, countries, recipes, collections, previous prices)
 * are never touched. Nothing is ever written to Square.
 */
class CatalogSync
{
    /** When the last successful sync started, as an ISO time for Square's "changed since". */
    public const STATE_KEY = 'square.catalog_synced_at';

    /** Shown when a Square item has no photo. */
    public const NO_IMAGE = 'images/store/no-image.svg';

    /** @var array{items: int, deactivated: int, categories: int, stock: int} */
    private array $stats = ['items' => 0, 'deactivated' => 0, 'categories' => 0, 'stock' => 0];

    /** @var array<int, int> Products created during this run. */
    private array $created = [];

    public function __construct(private SquareGateway $square) {}

    /**
     * Read the whole catalogue. Products that are no longer in Square are
     * hidden, not deleted, so past orders and our links to them survive.
     *
     * @return array{items: int, deactivated: int, categories: int, stock: int}
     */
    public function full(): array
    {
        $started = now();
        $initial = ! Cache::has(self::STATE_KEY);
        $objects = collect();
        $cursor = null;

        do {
            $page = $this->square->catalogPage($cursor);
            $objects = $objects->concat($page['objects'] ?? []);
            $cursor = $page['cursor'] ?? null;
        } while ($cursor);

        $this->apply($objects);

        // The very first import brings in the whole range at once; that's not "new arrivals".
        if ($initial) {
            Product::whereIn('id', $this->created)->update(['first_seen_at' => null]);
        }

        $seen = $objects->where('type', 'ITEM')->pluck('id');
        $this->stats['deactivated'] += Product::whereNotNull('square_id')
            ->whereNotIn('square_id', $seen)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->syncStock(full: true);
        Cache::forever(self::STATE_KEY, $started->toIso8601ZuluString());

        return $this->stats;
    }

    /**
     * Only what changed since the last sync; falls back to a full sync the
     * first time.
     *
     * @return array{items: int, deactivated: int, categories: int, stock: int}
     */
    public function changes(): array
    {
        $since = Cache::get(self::STATE_KEY);

        if (! $since) {
            return $this->full();
        }

        $started = now();
        $objects = collect();
        $cursor = null;

        do {
            $page = $this->square->catalogChangesSince($since, $cursor);
            $objects = $objects->concat($page['objects'] ?? []);
            $cursor = $page['cursor'] ?? null;
        } while ($cursor);

        $this->apply($objects);
        $this->syncStock(full: false, since: $since);
        Cache::forever(self::STATE_KEY, $started->toIso8601ZuluString());

        return $this->stats;
    }

    /**
     * Square's inventory webhook carries the new counts; apply them directly.
     *
     * @param  array<int, array<string, mixed>>  $counts
     */
    public function applyCounts(array $counts): int
    {
        $updated = 0;

        foreach ($counts as $count) {
            if (($count['state'] ?? null) !== 'IN_STOCK' || ($count['location_id'] ?? null) !== config('services.square.location_id')) {
                continue;
            }

            $updated += ProductVariation::where('square_id', $count['catalog_object_id'] ?? null)
                ->whereNotNull('stock_quantity')
                ->update(['stock_quantity' => max(0, (int) floor((float) $count['quantity']))]);
        }

        return $updated;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $objects
     */
    private function apply(Collection $objects): void
    {
        $items = $objects->where('type', 'ITEM');

        // Items can point at images and categories that didn't change, so fetch those too.
        $known = $objects->pluck('id')->flip();
        $missing = $items->flatMap(fn ($item) => [
            ...($item['item_data']['image_ids'] ?? []),
            ...collect($this->categoryIds($item))->reject(fn ($id) => Category::where('square_id', $id)->exists()),
        ])->unique()->reject(fn ($id) => $known->has($id))->values();

        $objects = $objects
            ->concat($missing->chunk(1000)->flatMap(fn ($ids) => $this->square->catalogObjects($ids->all())))
            ->unique('id');

        $images = $objects->where('type', 'IMAGE')->mapWithKeys(fn ($o) => [$o['id'] => $o['image_data']['url'] ?? null]);

        DB::transaction(function () use ($objects, $items, $images) {
            // Top-level categories first, so sub-categories can point at their parent.
            $objects->where('type', 'CATEGORY')
                ->sortBy(fn ($c) => isset($c['category_data']['parent_category']['id']) ? 1 : 0)
                ->each(fn ($category) => $this->applyCategory($category));
            $items->each(fn ($item) => $this->applyItem($item, $images));
        });
    }

    private function applyCategory(array $object): void
    {
        $existing = Category::where('square_id', $object['id'])->first();

        if ($object['is_deleted'] ?? false) {
            // Products keep working without a category (category_id is nulled).
            $existing?->delete();

            return;
        }

        $name = trim($object['category_data']['name'] ?? '');

        if ($name === '') {
            return;
        }

        $category = $existing ?? $this->storefrontCategory($name) ?? new Category;
        $category->square_id = $object['id'];
        $category->name = $name;
        // Square sub-categories become children of their parent category. A
        // top-level Square category keeps its place in our menu tree.
        $parentId = $object['category_data']['parent_category']['id'] ?? null;
        if ($parentId) {
            $category->parent_id = Category::where('square_id', $parentId)->value('id');
        }
        $category->slug ??= $this->uniqueSlug(Category::class, $name);
        $category->save();

        $this->stats['categories']++;
    }

    /**
     * Our own category with the same name that isn't linked to Square yet.
     */
    private function storefrontCategory(string $name): ?Category
    {
        return Category::whereNull('square_id')
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->orderBy('id')
            ->first();
    }

    /**
     * Square categories imported before our menu categories existed: move
     * their products onto our category with the same name, link that one to
     * Square instead, and remove the copy. Safe to run any time.
     */
    public function mergeIntoStorefrontCategories(): int
    {
        $merged = 0;

        Category::whereNotNull('square_id')->doesntHave('children')->get()
            ->each(function (Category $copy) use (&$merged) {
                $ours = $this->storefrontCategory($copy->name);

                if (! $ours) {
                    return;
                }

                DB::transaction(function () use ($copy, $ours) {
                    Product::where('category_id', $copy->id)->update(['category_id' => $ours->id]);
                    $squareId = $copy->square_id;
                    $copy->delete();
                    $ours->update(['square_id' => $squareId]);
                });

                $merged++;
            });

        return $merged;
    }

    /**
     * @param  Collection<string, string|null>  $images
     */
    private function applyItem(array $object, Collection $images): void
    {
        $data = $object['item_data'] ?? [];
        $product = Product::where('square_id', $object['id'])->first();

        if (($object['is_deleted'] ?? false) || ($data['is_archived'] ?? false)) {
            if ($product?->is_active) {
                $product->update(['is_active' => false]);
                $this->stats['deactivated']++;
            }

            return;
        }

        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return;
        }

        $variations = collect($data['variations'] ?? [])
            ->reject(fn ($v) => ($v['is_deleted'] ?? false) || ! $this->availableHere($v))
            ->filter(fn ($v) => $this->priceOf($v) !== null)
            ->values();

        // First sync: take over the demo product with the same name, so its
        // badges, country and recipe links carry over. Until Square has a
        // description, category or photo for it, the demo one stays.
        $adopting = false;

        if (! $product && $product = Product::whereNull('square_id')->where('slug', Str::slug($name))->first()) {
            $adopting = true;
        }

        $product ??= new Product;

        $categoryId = collect($this->categoryIds($object))
            ->map(fn ($id) => Category::where('square_id', $id)->value('id'))
            ->first(fn ($id) => $id !== null);

        $product->fill([
            'square_id' => $object['id'],
            'name' => $name,
            'description' => $this->descriptionOf($data) ?? ($adopting ? $product->description : null),
            'category_id' => $categoryId ?? ($adopting ? $product->category_id : null),
            'image' => $images->get($data['image_ids'][0] ?? '') ?? ($adopting ? $product->image : self::NO_IMAGE),
            'is_active' => $this->availableHere($object) && $variations->isNotEmpty() && ($data['ecom_visibility'] ?? null) !== 'HIDDEN',
        ]);
        $product->slug ??= $this->uniqueSlug(Product::class, $name);
        $product->save();

        if ($product->wasRecentlyCreated) {
            $this->created[] = $product->id;
        }

        $keep = [];

        foreach ($variations as $i => $variation) {
            $vd = $variation['item_variation_data'];
            $row = ProductVariation::where('square_id', $variation['id'])->first()
                ?? ($i === 0 ? $product->variations()->whereNull('square_id')->orderBy('sort_order')->first() : null)
                ?? new ProductVariation;

            $tracked = $this->tracksStock($variation);

            $row->fill([
                'square_id' => $variation['id'],
                'product_id' => $product->id,
                'name' => ($vd['name'] ?? null) === 'Regular' ? $row->name : ($vd['name'] ?? null),
                'sku' => $vd['sku'] ?? null,
                'price_cents' => $this->priceOf($variation),
                'sort_order' => $vd['ordinal'] ?? $i,
                // Tracked stock is filled in from Square's counts; keep the last known number meanwhile.
                'stock_quantity' => $tracked ? ($row->stock_quantity ?? 0) : null,
            ])->save();

            $keep[] = $row->id;
        }

        // Variations removed in Square. Order lines keep their own copy of the name and price.
        $product->variations()->whereNotIn('id', $keep)->delete();

        $this->stats['items']++;
    }

    /**
     * Pull stock counts for our location into tracked variations. A full
     * sync sets tracked variations with no count in Square to 0.
     */
    private function syncStock(bool $full, ?string $since = null): void
    {
        $seen = [];
        $cursor = null;

        do {
            $page = $this->square->inventoryCounts([], $cursor, $full ? null : $since);
            $counts = $page['counts'] ?? [];
            $this->stats['stock'] += $this->applyCounts($counts);
            $seen = [...$seen, ...array_column(array_filter($counts, fn ($c) => ($c['state'] ?? null) === 'IN_STOCK'), 'catalog_object_id')];
            $cursor = $page['cursor'] ?? null;
        } while ($cursor);

        if ($full) {
            ProductVariation::whereNotNull('square_id')
                ->whereNotNull('stock_quantity')
                ->whereNotIn('square_id', $seen)
                ->update(['stock_quantity' => 0]);
        }
    }

    private function availableHere(array $object): bool
    {
        $location = config('services.square.location_id');

        return ($object['present_at_all_locations'] ?? true)
            ? ! in_array($location, $object['absent_at_location_ids'] ?? [], true)
            : in_array($location, $object['present_at_location_ids'] ?? [], true);
    }

    /**
     * Fixed price in cents, or null for variable pricing or another currency.
     */
    private function priceOf(array $variation): ?int
    {
        $vd = $variation['item_variation_data'] ?? [];
        $price = collect($vd['location_overrides'] ?? [])
            ->firstWhere('location_id', config('services.square.location_id'))['price_money'] ?? $vd['price_money'] ?? null;

        if (! $price || ($vd['pricing_type'] ?? 'FIXED_PRICING') !== 'FIXED_PRICING') {
            return null;
        }

        if (($price['currency'] ?? null) !== config('shop.currency')) {
            Log::warning('Square variation '.$variation['id'].' is priced in '.($price['currency'] ?? '?').', not '.config('shop.currency').'; skipped.');

            return null;
        }

        return (int) $price['amount'];
    }

    private function tracksStock(array $variation): bool
    {
        $vd = $variation['item_variation_data'] ?? [];
        $override = collect($vd['location_overrides'] ?? [])->firstWhere('location_id', config('services.square.location_id'));

        return (bool) ($override['track_inventory'] ?? $vd['track_inventory'] ?? false);
    }

    /**
     * @return array<int, string>
     */
    private function categoryIds(array $item): array
    {
        $data = $item['item_data'] ?? [];

        return array_values(array_filter([
            $data['reporting_category']['id'] ?? null,
            ...array_column($data['categories'] ?? [], 'id'),
            $data['category_id'] ?? null,
        ]));
    }

    private function descriptionOf(array $data): ?string
    {
        $text = trim($data['description_plaintext'] ?? strip_tags($data['description'] ?? ''));

        return $text === '' ? null : $text;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function uniqueSlug(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;

        for ($i = 2; $model::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
