<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Order timeline: one row per status change, with when it happened and
 * which staff member made it (null = customer or system).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('fulfilment_status')->nullable();
            $table->string('label');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
        });

        // Orders placed before the timeline existed start with "Order placed".
        DB::table('orders')->orderBy('id')->chunkById(500, function ($orders) {
            DB::table('order_status_events')->insert($orders->map(fn ($order) => [
                'order_id' => $order->id,
                'status' => $order->status,
                'fulfilment_status' => $order->fulfilment_status,
                'label' => 'Order placed',
                'created_at' => $order->placed_at ?? $order->created_at,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_events');
    }
};
