<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('line_status', 20)->default('active')->after('line_total_cents'); // active | removed | substituted
            $table->string('item_class', 30)->default('perishable')->after('line_status'); // perishable | non_perishable | non_food
            $table->boolean('is_substitute')->default(false)->after('item_class');
            $table->foreignId('substitute_for_item_id')->nullable()->after('is_substitute')->constrained('order_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['substitute_for_item_id']);
            $table->dropColumn(['line_status', 'item_class', 'is_substitute', 'substitute_for_item_id']);
        });
    }
};
