<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront catalogue. Products, variations and categories mirror Square
 * (square_id columns); badges, countries, recipes and collections are ours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('square_id')->nullable()->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->string('theme')->default('green');
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('square_id')->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_hot')->default(false);
            $table->unsignedSmallInteger('bestseller_rank')->nullable();
            $table->unsignedTinyInteger('discount_estimate')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->string('square_id')->nullable()->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('previous_price_cents')->nullable();
            $table->integer('stock_quantity')->nullable()->comment('null = stock not tracked');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->string('image')->nullable();
            $table->string('thumbnail')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_recipe', function (Blueprint $table) {
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['recipe_id', 'product_id']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->timestamps();
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['collection_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('product_recipe');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('categories');
    }
};
