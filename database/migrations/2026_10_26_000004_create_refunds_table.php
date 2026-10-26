<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_issue_id')->nullable();
            $table->string('source', 30); // issue | substitution | uncollected | exchange | goodwill | in_store
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('delivery_fee_cents')->default(0);
            $table->string('reason')->nullable();
            $table->string('square_refund_id')->nullable()->unique();
            $table->string('status', 30)->default('initiating'); // initiating | initiated | processed | failed
            $table->text('error')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
