<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online payment details on orders (Square).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->unique()->after('payment_method');
            $table->string('payment_error')->nullable()->after('payment_id');
            $table->timestamp('paid_at')->nullable()->after('placed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['payment_id']);
            $table->dropColumn(['payment_id', 'payment_error', 'paid_at']);
        });
    }
};
