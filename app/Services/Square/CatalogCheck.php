<?php

namespace App\Services\Square;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Compares the Square catalogue (items, categories, sub-categories,
 * variations and prices) with what the website has. Read-only on both
 * sides: it only lists Square's catalogue and reads our tables.
 *
 * It follows the same rules as CatalogSync, so "in step" means "a sync
 * has nothing left to change".
 */
class CatalogCheck
{
    public function __construct(private SquareGateway $square) {}

    /**
     * @return array{
     *     summary: array<string, int>,
     *     categories: array<string, array<int, string>>,
     *     items: array<string, array<int, string>>,
     * }
     */
    public function run(): array
    {
        $objects = collect();
        $cursor = null;

        do {
            $page = $this->square->catalogPage($cursor, 'ITEM,CATEGORY');
            $objects = $objects->concat($page['objects'] ?? []);
            $cursor = $page['cursor'] ?? null;
        } while ($cursor);

        $objects = $objects->reject(fn ($o) => $o['is_deleted'] ?? false);
        $categories = $objects->where('type', 'CATEGORY')->keyBy('id');
        $items = $objects->where('type', 'ITEM');

        $categoryReport = $this->categories($categories);
        [$itemReport, $sellable, $notSellable] = $this->items($items, $categories);

        return [
            'summary' => [
                'square_categories' => $categories->count(),
                'square_subcategories' => $categories->filter(fn ($c) => isset($c['category_data']['parent_category']['id']))->count(),
                'square_items_for_sale' => $sellable,
                'square_items_not_for_sale' => $notSellable,
                'site_products_for_sale' => Product::where('is_active', true)->where('is_hidden', false)->count(),
                'differences' => collect($categoryReport)->sum(fn ($list) => count($list)) + collect($itemReport)->sum(fn ($list) => count($list)),
            ],
            'categories' => $categoryReport,
            'items' => $itemReport,
        ];
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $categories
     * @return array<string, array<int, string>>
     */
    private function categories(Collection $categories): array
    {
        $ours = Category::with('parent')->get();
        $bySquareId = $ours->whereNotNull('square_id')->keyBy('square_id');
        $report = ['missing_on_site' => [], 'renamed' => [], 'wrong_parent' => [], 'gone_from_square' => []];

        foreach ($categories as $id => $category) {
            $name = trim($category['category_data']['name'] ?? '');
            $parentId = $category['category_data']['parent_category']['id'] ?? null;
            $label = $parentId ? $this->categoryName($categories, $parentId).' › '.$name : $name;
            $site = $bySquareId->get($id);

            if (! $site) {
                $report['missing_on_site'][] = $label;

                continue;
            }

            if ($site->name !== $name) {
                $report['renamed'][] = "Square “{$name}” is “{$site->name}” on the site";
            }

            if ($parentId && $site->parent?->square_id !== $parentId) {
                $report['wrong_parent'][] = "“{$name}” is under “{$this->categoryName($categories, $parentId)}” in Square, under “".($site->parent?->name ?? 'nothing').'” on the site';
            }
        }

        foreach ($bySquareId as $squareId => $site) {
            if (! $categories->has($squareId)) {
                $report['gone_from_square'][] = $site->name;
            }
        }

        return $report;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<string, array<string, mixed>>  $categories
     * @return array{0: array<string, array<int, string>>, 1: int, 2: int}
     */
    private function items(Collection $items, Collection $categories): array
    {
        $products = Product::with('category', 'variations')->whereNotNull('square_id')->get()->keyBy('square_id');
        $report = [
            'missing_on_site' => [], 'not_for_sale_on_site' => [], 'renamed' => [], 'wrong_category' => [],
            'price_differs' => [], 'variation_missing' => [], 'still_for_sale_on_site' => [], 'not_linked_to_square' => [],
        ];
        $sellable = $notSellable = 0;
        $seen = [];

        foreach ($items as $item) {
            $data = $item['item_data'] ?? [];
            $name = trim($data['name'] ?? '');
            $variations = collect($data['variations'] ?? [])
                ->reject(fn ($v) => ($v['is_deleted'] ?? false) || ! $this->availableHere($v))
                ->filter(fn ($v) => $this->priceOf($v) !== null);
            $forSale = $name !== '' && ! ($data['is_archived'] ?? false) && $this->availableHere($item)
                && $variations->isNotEmpty() && ($data['ecom_visibility'] ?? null) !== 'HIDDEN';
            $product = $products->get($item['id']);
            $seen[$item['id']] = true;

            if (! $forSale) {
                $notSellable++;

                if ($product?->is_active) {
                    $report['still_for_sale_on_site'][] = "{$product->name} (not for sale in Square)";
                }

                continue;
            }

            $sellable++;

            if (! $product) {
                $report['missing_on_site'][] = $name;

                continue;
            }

            if (! $product->is_active) {
                $report['not_for_sale_on_site'][] = $name;
            } elseif ($product->is_hidden) {
                $report['not_for_sale_on_site'][] = "{$name} (hidden by staff on the site)";
            }

            if ($product->name !== $name) {
                $report['renamed'][] = "Square “{$name}” is “{$product->name}” on the site";
            }

            $squareCategories = $this->categoryIds($item);

            if ($squareCategories !== [] && ! in_array($product->category?->square_id, $squareCategories, true)) {
                $report['wrong_category'][] = "{$name}: “".$this->categoryName($categories, $squareCategories[0]).'” in Square, “'.($product->category?->name ?? 'none').'” on the site';
            }

            foreach ($variations as $variation) {
                $vName = $variation['item_variation_data']['name'] ?? 'Regular';
                $row = $product->variations->firstWhere('square_id', $variation['id']);

                if (! $row) {
                    $report['variation_missing'][] = "{$name} / {$vName}";
                } elseif ($row->price_cents !== $this->priceOf($variation)) {
                    $report['price_differs'][] = "{$name} / {$vName}: ".money($this->priceOf($variation)).' in Square, '.money($row->price_cents).' on the site';
                }
            }
        }

        foreach ($products as $squareId => $product) {
            if (! isset($seen[$squareId]) && $product->is_active) {
                $report['still_for_sale_on_site'][] = "{$product->name} (deleted from Square)";
            }
        }

        $report['not_linked_to_square'] = Product::whereNull('square_id')->where('is_active', true)->orderBy('name')->pluck('name')->all();

        return [$report, $sellable, $notSellable];
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $categories
     */
    private function categoryName(Collection $categories, string $id): string
    {
        return $categories->get($id)['category_data']['name']
            ?? Category::where('square_id', $id)->value('name')
            ?? 'unknown category';
    }

    /**
     * Same rules as CatalogSync.
     *
     * @return array<int, string>
     */
    private function categoryIds(array $item): array
    {
        $data = $item['item_data'] ?? [];

        return array_values(array_unique(array_filter([
            $data['reporting_category']['id'] ?? null,
            ...array_column($data['categories'] ?? [], 'id'),
            $data['category_id'] ?? null,
        ])));
    }

    private function availableHere(array $object): bool
    {
        $location = config('services.square.location_id');

        return ($object['present_at_all_locations'] ?? true)
            ? ! in_array($location, $object['absent_at_location_ids'] ?? [], true)
            : in_array($location, $object['present_at_location_ids'] ?? [], true);
    }

    private function priceOf(array $variation): ?int
    {
        $vd = $variation['item_variation_data'] ?? [];
        $price = collect($vd['location_overrides'] ?? [])
            ->firstWhere('location_id', config('services.square.location_id'))['price_money'] ?? $vd['price_money'] ?? null;

        if (! $price || ($vd['pricing_type'] ?? 'FIXED_PRICING') !== 'FIXED_PRICING' || ($price['currency'] ?? null) !== config('shop.currency')) {
            return null;
        }

        return (int) $price['amount'];
    }

    /** Headings for the report sections. */
    public const LABELS = [
        'categories.missing_on_site' => 'Square categories missing on the site',
        'categories.renamed' => 'Categories named differently',
        'categories.wrong_parent' => 'Sub-categories under a different parent',
        'categories.gone_from_square' => 'Site categories no longer in Square',
        'items.missing_on_site' => 'Square items missing on the site',
        'items.not_for_sale_on_site' => 'For sale in Square but not on the site',
        'items.renamed' => 'Items named differently',
        'items.wrong_category' => 'Items in a different category',
        'items.price_differs' => 'Prices that differ',
        'items.variation_missing' => 'Square sizes/variations missing on the site',
        'items.still_for_sale_on_site' => 'On sale on the site but not in Square',
        'items.not_linked_to_square' => 'Site products not linked to Square (demo or site-only)',
    ];
}
