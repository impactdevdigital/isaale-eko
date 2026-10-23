# Catalogue merge: storefront + admin products (23 Oct 2026)

**For:** Saliu and anyone working on products, Square or the admin.
**Merge commit:** `e3022a7` on `storefront-ui` (merges `main` at `87b8e34`).

## Summary

Two catalogues had been built at the same time:

| | Storefront branch (`storefront-ui`) | Saliu's commit on `main` (`87b8e34`, 22 Oct) |
|---|---|---|
| Tables | `products`, `product_variations`, `categories` (with `parent_id`), `countries` | `products`, `variations`, `inventory_snapshots`, `categories`, `sub_categories`, `countries` |
| Square import | `square:sync` (`App\Services\Square\CatalogSync`) | `square:sync-catalog` (`ImportCatalog`, `ImportInventory`) |
| Used by | Home, shop, product pages, basket, checkout, orders, accounts, rewards, wishlist, 86 tests | Admin Products & Inventory page |

Both created tables with the same names, so `php artisan migrate` would fail
("table already exists"), and two importers would have overwritten each other.

**Decision:** keep the storefront catalogue and `square:sync`, and move Saliu's
admin page and ideas onto it. Rebuilding the whole shop on the other schema
would have been much larger.

## What was kept

| Saliu's work | Where it lives now |
|---|---|
| Admin **Products & Inventory** page (`/admin/products`) | `app/Livewire/Admin/ProductsList.php`, `resources/views/livewire/admin/products-list.blade.php`; same layout and features |
| Search by name or **SKU** | `product_variations.sku`, filled by `square:sync` |
| Category, stock state and item-class filters | Same filters; a parent category also matches its sub-categories |
| **Featured** star | `products.is_featured` |
| **Item class** (perishable / non-perishable / non-food) | `products.item_class`; allowed values in `Product::ITEM_CLASSES` |
| **Show / hide** online | `products.is_hidden` (see "Changed" below) |
| **Sub-categories** from Square | Child categories: `categories.parent_id`, set from Square's `parent_category` |
| **New Arrivals** not flooded by the first import | `products.first_seen_at`; the first full sync leaves it empty for everything it creates |
| `is_enriched` flag | `products.is_enriched` |
| **Translations** (French groundwork) | `translations` table, `App\Models\Translation`, `App\Models\Concerns\HasTranslations` on `Product`, `Category`, `Country` (unchanged) |
| **Webhook log** | `webhook_events` table, `App\Models\WebhookEvent` (unchanged) |

All new columns and tables are in
`database/migrations/2026_10_24_000004_add_catalogue_admin_fields.php`.

## What changed

- **Show/hide uses its own flag.** The original toggled the product's status,
  which the Square sync sets on every run, so a hidden product would come back
  within 5 minutes. Now `is_active` belongs to Square (removed, archived,
  unpriced → off) and `is_hidden` belongs to staff. The sync never touches
  `is_hidden`. The shop shows a product only when it's active **and** not hidden
  (`Product::scopeActive()`, `Product::isAvailable()`).
- **Untracked stock means "available".** The original treated variations with
  stock tracking off in Square as out of stock. The storefront treats them as
  always available (`stock_quantity = null`), which is how checkout already
  worked. The admin page labels them "Not tracked".
- **Stock states** come from `product_variations.stock_quantity` and the
  `low_stock_threshold` setting, the same numbers the shop uses.
- **Morph map is not enforced.** `enforceMorphMap` makes every polymorphic
  relation fail for models not in the map, including API tokens (`tokenable` →
  `User`). `AppServiceProvider` now uses a plain `Relation::morphMap` with the
  same short names (`product`, `category`, `country`).

## What was removed

| Removed | Replaced by |
|---|---|
| Migration `2026_10_22_000007_create_catalog_and_translation_tables.php` | Storefront tables + `2026_10_24_000004_add_catalogue_admin_fields.php` |
| `App\Models\Variation`, `InventorySnapshot`, `SubCategory` | `ProductVariation` (with `stock_quantity`), child `Category` |
| `App\Actions\Catalog\ImportCatalog`, `ImportInventory` | `App\Services\Square\CatalogSync` |
| `square:sync-catalog` | `square:sync` (`--full`, `--retire-demo`) |
| `SquareGateway::catalogBatchRetrieve()` | `SquareGateway::catalogObjects()` (same API call) |

Nothing is lost: everything is still in commit `87b8e34` in the history.

## What you need to do

If your local database ran the old migration (`2026_10_22_000007`), reset it once:

```
php artisan migrate:fresh
php artisan db:seed --class=DemoCatalogSeeder   # local demo products
```

Then use `php artisan square:sync` (not `square:sync-catalog`) to pull products from Square.

## Tests

- `tests/Feature/AdminComponentsTest.php`: the admin page test, updated to the
  storefront schema, plus stock and category filter tests
- `tests/Feature/SquareCatalogSyncTest.php`: includes "staff hiding a product
  survives later syncs", SKUs and sub-categories, and the New Arrivals rule

Full suite: 91 tests passing at the merge.
