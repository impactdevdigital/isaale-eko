<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('uncollected_at')->nullable()->after('promised_by');
            $table->string('cancel_reason', 100)->nullable()->after('uncollected_at');
            $table->timestamp('pickup_reminder_sent_at')->nullable()->after('cancel_reason');
            $table->timestamp('pickup_overdue_reminder_sent_at')->nullable()->after('pickup_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'uncollected_at',
                'cancel_reason',
                'pickup_reminder_sent_at',
                'pickup_overdue_reminder_sent_at',
            ]);
        });
    }
};
