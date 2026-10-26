<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substitution_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('substitute_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->integer('price_difference_cents')->default(0); // positive: customer pays more; negative: refund difference
            $table->string('status', 30)->default('contacting'); // contacting | awaiting_reply | approved | declined | auto_refunded | cancelled
            $table->json('call_log')->nullable(); // [{at, staff_id, staff_name, reached: bool}]
            $table->timestamp('message_sent_at')->nullable();
            $table->timestamp('response_deadline_at')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['status', 'response_deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitution_requests');
    }
};
