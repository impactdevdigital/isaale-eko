<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ingredients: the concept (e.g. Palm Oil, Egusi), not a specific SKU
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('alt_names')->nullable(); // Alternative names / synonyms (e.g. "melon seed")
            $table->boolean('is_pantry_staple')->default(false); // Salt, water: never added to the cart
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 2. Ingredient Products: links real store product variations to an ingredient, ranked
        Schema::create('ingredient_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->unsignedTinyInteger('rank')->default(1); // 1 = preferred option
            $table->decimal('pack_factor', 5, 2)->default(1); // e.g. 2.0 = double-sized pack
            $table->timestamps();
            $table->unique(['ingredient_id', 'product_variation_id']);
        });

        // 3. Extend Recipes table with Kitchen metadata
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->text('intro')->nullable()->after('tagline');
            $table->text('steps')->nullable()->after('intro');
            $table->string('cuisine')->default('west_african')->index()->after('steps');
            $table->unsignedSmallInteger('base_servings')->default(4)->after('cuisine');
            $table->unsignedSmallInteger('prep_minutes')->nullable()->after('base_servings');
            $table->json('diet_tags')->nullable()->after('prep_minutes');
            $table->string('image_url', 500)->nullable()->after('diet_tags');
            $table->string('status')->default('draft')->after('image_url'); // draft | approved | retired
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->date('review_due_at')->nullable()->after('approved_at');
        });

        // 4. Recipe Ingredients: links recipe to ingredients with role and servings per pack
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('role')->default('required'); // required | optional
            $table->unsignedSmallInteger('people_per_pack')->default(4);
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['recipe_id', 'ingredient_id']);
        });

        // 5. Ingredient Substitutions: curated, chef-approved swaps
        Schema::create('ingredient_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('substitute_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['ingredient_id', 'substitute_id']);
        });

        // 6. Meal Combos: curated pairs like "Egusi Soup + Eba"
        Schema::create('meal_combos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('meal_combo_recipes', function (Blueprint $table) {
            $table->foreignId('meal_combo_id')->constrained('meal_combos')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['meal_combo_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_combo_recipes');
        Schema::dropIfExists('meal_combos');
        Schema::dropIfExists('ingredient_substitutions');
        Schema::dropIfExists('recipe_ingredients');

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'title',
                'intro',
                'steps',
                'cuisine',
                'base_servings',
                'prep_minutes',
                'diet_tags',
                'image_url',
                'status',
                'approved_by',
                'approved_at',
                'review_due_at',
            ]);
        });

        Schema::dropIfExists('ingredient_products');
        Schema::dropIfExists('ingredients');
    }
};
