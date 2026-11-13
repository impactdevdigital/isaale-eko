<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved baskets (named lists of variations to rebuild later at today's
 * prices) and saved meals (a customer's favourite recipes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_baskets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();
        });

        Schema::create('saved_basket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_basket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variation_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots, so a line still reads sensibly after its product is gone.
            $table->string('name');
            $table->string('variation_name')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
            $table->timestamps();
        });

        Schema::create('saved_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_recipes');
        Schema::dropIfExists('saved_basket_items');
        Schema::dropIfExists('saved_baskets');
    }
};
