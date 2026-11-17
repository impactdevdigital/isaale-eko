<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website orders are copied into Square as real orders (items, customer,
 * pickup or delivery details), so staff see them in Square's Orders page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('square_order_id')->nullable()->unique();
            $table->timestamp('square_synced_at')->nullable();
            $table->string('square_sync_error')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('square_customer_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['square_order_id']);
            $table->dropColumn(['square_order_id', 'square_synced_at', 'square_sync_error']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('square_customer_id');
        });
    }
};
