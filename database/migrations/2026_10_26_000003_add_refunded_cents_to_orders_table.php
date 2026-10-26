<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('refunded_cents')->default(0)->after('total_cents');
            $table->unsignedBigInteger('replacement_for_id')->nullable()->after('refunded_cents');
            $table->timestamp('fulfilled_at')->nullable()->after('replacement_for_id');
            $table->timestamp('promised_by')->nullable()->after('fulfilled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refunded_cents', 'replacement_for_id', 'fulfilled_at', 'promised_by']);
        });
    }
};
