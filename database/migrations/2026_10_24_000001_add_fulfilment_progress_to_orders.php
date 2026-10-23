<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a confirmed order is in the store's hands (for the account order
 * tracker), and customer cancellations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfilment_status')->nullable()->after('status')->comment('null (confirmed), preparing, ready, completed');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['fulfilment_status', 'cancelled_at']);
        });
    }
};
