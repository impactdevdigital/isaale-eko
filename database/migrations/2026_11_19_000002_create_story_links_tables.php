<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stories (Food Stories and Brand Updates) can feature products and
 * recipes: the story page shows them, and product / recipe pages show the
 * stories that mention them. No separate Product Story type is needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_story', function (Blueprint $table) {
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['story_id', 'product_id']);
        });

        Schema::create('recipe_story', function (Blueprint $table) {
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['story_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_story');
        Schema::dropIfExists('product_story');
    }
};
