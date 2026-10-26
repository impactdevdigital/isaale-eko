<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_difference_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('source', 30); // substitution | exchange
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('amount_cents');
            $table->string('payment_link_url', 500)->nullable();
            $table->string('square_order_id')->nullable();
            $table->string('status', 30)->default('pending'); // pending | paid | expired | cancelled
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_difference_requests');
    }
};
