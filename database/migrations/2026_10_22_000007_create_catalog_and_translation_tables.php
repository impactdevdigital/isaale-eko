<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active'); // active | hidden
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('square_category_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active | hidden | deleted
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('square_category_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['category_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('square_item_id')->unique();
            $table->unsignedBigInteger('square_version')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_url', 500)->nullable();
            $table->string('status')->default('active'); // active | hidden | deleted
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_enriched')->default(false);
            $table->string('item_class')->default('perishable'); // perishable | non_perishable | non_food
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('square_variation_id')->unique();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->boolean('tracks_inventory')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->foreignId('variation_id')->primary()->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->string('state')->default('out'); // in | low | out
            $table->timestamp('synced_at')->nullable();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type', 40);
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 5);
            $table->string('field', 40);
            $table->text('value')->nullable();
            $table->string('review_status')->default('draft'); // draft | reviewed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'translations_unique');
            $table->index(['locale', 'field']);
            if (DB::getDriverName() === 'mysql') {
                $table->fullText('value');
            }
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // square | doordash
            $table->string('event_id');
            $table->string('type')->nullable();
            $table->json('payload');
            $table->string('status')->default('received'); // received | processed | failed
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('inventory_snapshots');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('countries');
    }
};
