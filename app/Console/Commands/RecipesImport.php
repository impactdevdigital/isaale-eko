<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\IngredientProduct;
use App\Models\ProductVariation;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Console\Command;

class RecipesImport extends Command
{
    protected $signature = 'recipes:import {path=database/recipes/*.json}';

    protected $description = 'Import recipe JSON files into the Kitchen engine';

    public function handle(): int
    {
        $pattern = base_path($this->argument('path'));
        $files = glob($pattern);

        if (empty($files)) {
            $this->warn("No recipe JSON files found matching: {$this->argument('path')}");
            return self::SUCCESS;
        }

        foreach ($files as $file) {
            $d = json_decode(file_get_contents($file), true);
            if (! $d || empty($d['slug'])) {
                continue;
            }

            $recipe = Recipe::updateOrCreate(['slug' => $d['slug']], [
                'name' => $d['title'] ?? ($d['name'] ?? 'Untitled Recipe'),
                'title' => $d['title'] ?? ($d['name'] ?? 'Untitled Recipe'),
                'cuisine' => $d['cuisine'] ?? 'west_african',
                'base_servings' => $d['base_servings'] ?? 4,
                'prep_minutes' => $d['prep_minutes'] ?? 45,
                'duration_minutes' => $d['prep_minutes'] ?? 45,
                'intro' => $d['intro'] ?? ($d['tagline'] ?? null),
                'tagline' => $d['intro'] ?? ($d['tagline'] ?? null),
                'steps' => $d['steps'] ?? null,
                'image' => $d['image'] ?? null,
                'image_url' => $d['image_url'] ?? ($d['image'] ?? null),
                'status' => $d['status'] ?? Recipe::STATUS_DRAFT,
            ]);

            foreach ($d['translations'] ?? [] as $locale => $fields) {
                foreach ($fields as $field => $value) {
                    $recipe->setTranslation($field, $locale, $value, 'draft');
                }
            }

            foreach ($d['ingredients'] ?? [] as $i => $row) {
                $ing = Ingredient::updateOrCreate(['slug' => $row['slug']], [
                    'name' => $row['name'],
                    'alt_names' => $row['alt_names'] ?? null,
                    'is_pantry_staple' => $row['pantry_staple'] ?? ($row['is_pantry_staple'] ?? false),
                ]);

                foreach ($row['options'] ?? [] as $o) {
                    $variation = null;
                    if (! empty($o['sku'])) {
                        $variation = ProductVariation::where('sku', $o['sku'])->first();
                    }
                    if (! $variation && ! empty($o['product_id'])) {
                        $variation = ProductVariation::where('product_id', $o['product_id'])->first();
                    }
                    if ($variation) {
                        IngredientProduct::updateOrCreate(
                            ['ingredient_id' => $ing->id, 'product_variation_id' => $variation->id],
                            ['rank' => $o['rank'] ?? 1, 'pack_factor' => $o['pack_factor'] ?? 1]
                        );
                    }
                }

                RecipeIngredient::updateOrCreate(
                    ['recipe_id' => $recipe->id, 'ingredient_id' => $ing->id],
                    [
                        'role' => $row['role'] ?? 'required',
                        'people_per_pack' => $row['people_per_pack'] ?? 4,
                        'sort_order' => $i,
                        'note' => $row['note'] ?? null,
                    ]
                );
            }

            $this->info("Imported recipe: {$recipe->title}");
        }

        return self::SUCCESS;
    }
}
