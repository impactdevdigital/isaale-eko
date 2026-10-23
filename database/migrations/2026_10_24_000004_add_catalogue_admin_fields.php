<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin products & inventory (ported from the 22 Oct catalogue work onto the
 * storefront tables): admin-only flags that the Square sync never touches,
 * SKUs, first-seen dates for New Arrivals, translations and a webhook log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Hidden by staff, whatever Square says. is_active stays Square's.
            $table->boolean('is_hidden')->default(false)->after('is_active');
            $table->boolean('is_featured')->default(false)->after('is_hidden');
            $table->boolean('is_enriched')->default(false)->after('is_featured');
            $table->string('item_class')->default('perishable')->after('is_enriched')->comment('perishable, non_perishable or non_food');
            // When the product first appeared in the shop; drives New Arrivals.
            $table->timestamp('first_seen_at')->nullable()->after('item_class');
        });

        DB::table('products')->update(['first_seen_at' => DB::raw('created_at')]);

        Schema::table('product_variations', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
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

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn('sku');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_hidden', 'is_featured', 'is_enriched', 'item_class', 'first_seen_at']);
        });
    }
};
