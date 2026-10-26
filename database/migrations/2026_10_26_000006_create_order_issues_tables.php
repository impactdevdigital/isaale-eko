<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_issues', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique(); // e.g. IS-261026-AB12
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 30)->default('problem'); // problem | return | safety
            $table->string('channel', 30)->default('website'); // website | email | whatsapp | phone | in_store
            $table->string('status', 30)->default('new'); // new | reviewing | awaiting_customer | awaiting_return | resolved | declined | closed
            $table->text('description')->nullable();
            $table->string('item_instruction', 30)->nullable(); // keep | dispose | return | none
            $table->boolean('within_window')->default(true);
            $table->string('override_reason', 50)->nullable(); // late_review | legal | goodwill
            $table->json('offered')->nullable(); // ["refund","replacement","exchange"]
            $table->string('chosen', 30)->nullable(); // refund | replacement | exchange
            $table->foreignId('exchange_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->unsignedInteger('exchange_quantity')->nullable();
            $table->integer('exchange_difference_cents')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('due_at')->nullable(); // SLA target: reported_at + 24 hours
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('order_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->string('issue_type', 40); // wrong_product | wrong_quantity | missing_item | damaged | spoiled_defective | not_as_ordered | late_or_undelivered | other_fulfilment_error | change_of_mind
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_issue_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_issue_id')->constrained()->cascadeOnDelete();
            $table->string('path', 500);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_issue_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_issue_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40); // opened | investigating | info_requested | resolution_offered | resolved | declined
            $table->string('actor', 30); // staff | customer | system
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_issue_events');
        Schema::dropIfExists('order_issue_photos');
        Schema::dropIfExists('order_issue_items');
        Schema::dropIfExists('order_issues');
    }
};
