<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientProduct;
use App\Models\MealCombo;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KitchenSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Core Ingredients
        $ingredientsData = [
            ['name' => 'Parboiled Rice', 'slug' => 'parboiled-rice', 'alt_names' => 'long grain rice, jollof rice', 'pantry' => false],
            ['name' => 'Tomato Paste', 'slug' => 'tomato-paste', 'alt_names' => 'puree, tin tomato, gino', 'pantry' => false],
            ['name' => 'Scotch Bonnet Peppers', 'slug' => 'scotch-bonnet', 'alt_names' => 'atarodo, fresh habanero', 'pantry' => false],
            ['name' => 'Cooking Oil', 'slug' => 'cooking-oil', 'alt_names' => 'vegetable oil, pure oil', 'pantry' => false],
            ['name' => 'Red Palm Oil', 'slug' => 'palm-oil', 'alt_names' => 'epo pupa, palm oil', 'pantry' => false],
            ['name' => 'Seasoning Cubes', 'slug' => 'seasoning-cubes', 'alt_names' => 'maggi, knorr, bouillon cubes', 'pantry' => false],
            ['name' => 'Ground Egusi', 'slug' => 'egusi', 'alt_names' => 'melon seed, egusi powder', 'pantry' => false],
            ['name' => 'Dried Stockfish', 'slug' => 'stockfish', 'alt_names' => 'okporoko, dry fish, cod', 'pantry' => false],
            ['name' => 'Dried Crayfish', 'slug' => 'crayfish', 'alt_names' => 'ground crayfish', 'pantry' => false],
            ['name' => 'Fresh Beef', 'slug' => 'fresh-beef', 'alt_names' => 'beef chunks, stewing beef', 'pantry' => false],
            ['name' => 'Dried Hibiscus Petals', 'slug' => 'zobo-leaves', 'alt_names' => 'hibiscus, sorrel, zobo', 'pantry' => false],
            ['name' => 'Ginger', 'slug' => 'ginger', 'alt_names' => 'fresh ginger root', 'pantry' => false],
            ['name' => 'Clove', 'slug' => 'clove', 'alt_names' => 'whole cloves, kanunfari', 'pantry' => false],
            ['name' => 'Pineapple', 'slug' => 'pineapple', 'alt_names' => 'sweet pineapple', 'pantry' => false],
            ['name' => 'Salt', 'slug' => 'salt', 'alt_names' => 'table salt, sea salt', 'pantry' => true],
            ['name' => 'Water', 'slug' => 'water', 'alt_names' => 'clean water', 'pantry' => true],
        ];

        $ingredients = [];
        foreach ($ingredientsData as $d) {
            $ingredients[$d['slug']] = Ingredient::updateOrCreate(['slug' => $d['slug']], [
                'name' => $d['name'],
                'alt_names' => $d['alt_names'],
                'is_pantry_staple' => $d['pantry'],
                'status' => 'active',
            ]);
        }

        // 2. Link Ingredients to Real Store Product Variations
        $mappings = [
            'parboiled-rice' => ['premium-parboiled-rice', 'basmati-rice', 'ofada-rice'],
            'tomato-paste' => ['gino-tomato-paste', 'tomatoes'],
            'scotch-bonnet' => ['scotch-bonnet-peppers'],
            'cooking-oil' => ['mamador-pure-oil'],
            'palm-oil' => ['red-palm-oil'],
            'seasoning-cubes' => ['maggi-seasoning-cubes', 'knorr-chicken-seasoning', 'chief-curry-powder'],
            'egusi' => ['groundnut-paste'], // fallback shelf item
            'stockfish' => ['dried-stockfish', 'titus-sardines'],
            'crayfish' => ['dried-crayfish'],
            'fresh-beef' => ['fresh-beef'],
            'zobo-leaves' => ['dried-hibiscus-petals'],
            'ginger' => ['ginger'],
            'clove' => ['clove'],
            'pineapple' => ['pineapple'],
        ];

        foreach ($mappings as $ingSlug => $productSlugs) {
            if (! isset($ingredients[$ingSlug])) {
                continue;
            }
            $rank = 1;
            foreach ($productSlugs as $pSlug) {
                $product = Product::with('variations')->where('slug', $pSlug)->first();
                if ($product && $product->variations->isNotEmpty()) {
                    $variation = $product->variations->first();
                    IngredientProduct::updateOrCreate(
                        [
                            'ingredient_id' => $ingredients[$ingSlug]->id,
                            'product_variation_id' => $variation->id,
                        ],
                        [
                            'rank' => $rank++,
                            'pack_factor' => 1.0,
                        ]
                    );
                }
            }
        }

        // 3. Approved Substitutions
        if (isset($ingredients['cooking-oil'], $ingredients['palm-oil'])) {
            $ingredients['cooking-oil']->substitutes()->syncWithoutDetaching([$ingredients['palm-oil']->id]);
        }
        if (isset($ingredients['stockfish'], $ingredients['crayfish'])) {
            $ingredients['stockfish']->substitutes()->syncWithoutDetaching([$ingredients['crayfish']->id]);
        }

        // 4. Enrich Recipes with Kitchen Data (Steps, Cuisines, RecipeIngredients)
        $recipeDefinitions = [
            'jollof-rice' => [
                'title' => 'Nigerian Jollof Rice',
                'cuisine' => 'nigerian',
                'prep_minutes' => 50,
                'intro' => 'Smoky, party-style Nigerian Jollof Rice simmered in an aromatic tomato-pepper base.',
                'steps' => "1. Blend fresh tomatoes, scotch bonnet peppers, and onions into a smooth pepper mix.\n2. Heat vegetable oil in a deep heavy-bottom pot and fry sliced onions until fragrant.\n3. Add tomato paste and fry for 5 minutes, then pour in the blended pepper puree.\n4. Cook the sauce until oil separates at the top, then season with bouillon cubes, thyme, curry powder, and salt.\n5. Wash the parboiled rice thoroughly and add to the pot. Stir well to coat with sauce.\n6. Add meat stock or water just to the level of the rice. Cover tightly with foil and pot lid.\n7. Cook on low heat for 30 minutes until rice is tender and bottom develops a signature smoky party crust.",
                'ingredients' => [
                    ['slug' => 'parboiled-rice', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'tomato-paste', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'scotch-bonnet', 'role' => 'required', 'people_per_pack' => 6],
                    ['slug' => 'cooking-oil', 'role' => 'required', 'people_per_pack' => 6],
                    ['slug' => 'seasoning-cubes', 'role' => 'required', 'people_per_pack' => 8],
                    ['slug' => 'salt', 'role' => 'required', 'people_per_pack' => 10],
                    ['slug' => 'water', 'role' => 'required', 'people_per_pack' => 10],
                ],
            ],
            'egusi-soup' => [
                'title' => 'Egusi Soup',
                'cuisine' => 'nigerian',
                'prep_minutes' => 45,
                'intro' => 'Rich and hearty melon seed soup infused with palm oil, leafy greens, and rich smoked proteins.',
                'steps' => "1. Season beef and stockfish with salt, pepper, and seasoning cubes. Boil until tender and set stock aside.\n2. In a bowl, mix ground egusi with a little warm water to form thick pastes or balls.\n3. Heat palm oil in a pot and sauté diced onions until translucent.\n4. Add tomato paste and pepper blend, frying gently for 8 minutes.\n5. Scoop egusi pastes into the hot oil, cover, and let simmer for 10 minutes without stirring so curds form.\n6. Gently stir in cooked meat, stockfish, and reserved broth. Simmer for 15 minutes.\n7. Finish with chopped spinach or bitterleaf, cook for another 3 minutes, and remove from heat.",
                'ingredients' => [
                    ['slug' => 'egusi', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'palm-oil', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'tomato-paste', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'fresh-beef', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'stockfish', 'role' => 'optional', 'people_per_pack' => 4],
                    ['slug' => 'seasoning-cubes', 'role' => 'required', 'people_per_pack' => 8],
                    ['slug' => 'salt', 'role' => 'required', 'people_per_pack' => 10],
                ],
            ],
            'banga-soup' => [
                'title' => 'Banga Palm Soup',
                'cuisine' => 'west_african',
                'prep_minutes' => 70,
                'intro' => 'A luxurious Delta palm nut soup delicately spiced and packed with assorted seafood and beef.',
                'steps' => "1. Extract fresh palm nut juice or dissolve palm concentrate in warm broth.\n2. Place palm concentrate in a wide cooking pot and bring to a rolling boil over medium-high heat.\n3. Add precooked meats, dried stockfish, ground crayfish, and Banga spices.\n4. Allow soup to boil vigorously until it thickens and aromatic red oil rises to the surface.\n5. Season with salt and crushed bouillon cubes.\n6. Add crushed dried bitterleaf or scent leaf and simmer on low for 5 minutes before serving with starch.",
                'ingredients' => [
                    ['slug' => 'palm-oil', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'crayfish', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'stockfish', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'fresh-beef', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'seasoning-cubes', 'role' => 'required', 'people_per_pack' => 8],
                    ['slug' => 'salt', 'role' => 'required', 'people_per_pack' => 10],
                ],
            ],
            'zobo-hibiscus-drink' => [
                'title' => 'Zobo Hibiscus Drink',
                'cuisine' => 'west_african',
                'prep_minutes' => 30,
                'intro' => 'Deep crimson, tangy, and invigorating hibiscus beverage spiced with ginger, cloves, and pineapple.',
                'steps' => "1. Wash dried hibiscus petals thoroughly in cold water to remove any field dust.\n2. Place washed petals in a large stainless steel pot and add peeled pineapple chunks, grated ginger, and cloves.\n3. Pour in fresh water until ingredients are fully submerged.\n4. Bring to a boil over medium heat and boil for 25 minutes until the liquid turns intensely dark red.\n5. Turn off heat and allow to cool completely so spices infuse deeply.\n6. Strain through a fine mesh cloth. Sweeten to taste and serve chilled over ice cubes.",
                'ingredients' => [
                    ['slug' => 'zobo-leaves', 'role' => 'required', 'people_per_pack' => 4],
                    ['slug' => 'ginger', 'role' => 'required', 'people_per_pack' => 6],
                    ['slug' => 'clove', 'role' => 'required', 'people_per_pack' => 8],
                    ['slug' => 'pineapple', 'role' => 'optional', 'people_per_pack' => 4],
                    ['slug' => 'water', 'role' => 'required', 'people_per_pack' => 10],
                ],
            ],
            'curry-goat' => [
                'title' => 'Curry Goat',
                'cuisine' => 'caribbean',
                'prep_minutes' => 90,
                'intro' => 'Tender goat chunks slow-simmered in toasted Jamaican curry powder, scotch bonnet, and aromatic garlic.',
                'steps' => "1. Trim goat meat into bite-sized cubes and wash thoroughly with lime juice.\n2. Season meat with curry powder, allspice, minced garlic, ginger, scotch bonnet, and scallions. Marinate for at least 4 hours.\n3. Heat oil in a heavy Dutch oven. Brown seasoned meat on all sides.\n4. Add sliced onions and cook until softened.\n5. Pour in warm water or broth to cover meat. Bring to a boil, then reduce heat to low.\n6. Cover and braise slowly for 1.5 hours until meat is melt-in-your-mouth tender and curry gravy is thick.",
                'ingredients' => [
                    ['slug' => 'fresh-beef', 'role' => 'required', 'people_per_pack' => 4], // goat/beef protein
                    ['slug' => 'cooking-oil', 'role' => 'required', 'people_per_pack' => 6],
                    ['slug' => 'scotch-bonnet', 'role' => 'required', 'people_per_pack' => 6],
                    ['slug' => 'seasoning-cubes', 'role' => 'required', 'people_per_pack' => 8],
                    ['slug' => 'salt', 'role' => 'required', 'people_per_pack' => 10],
                ],
            ],
        ];

        foreach ($recipeDefinitions as $slug => $def) {
            $imagePath = file_exists(public_path("images/store/recipes/{$slug}.webp"))
                ? "images/store/recipes/{$slug}.webp"
                : (file_exists(public_path("images/store/recipes/thumbs/{$slug}.webp"))
                    ? "images/store/recipes/thumbs/{$slug}.webp"
                    : null);

            $recipe = Recipe::updateOrCreate(['slug' => $slug], [
                'name' => $def['title'],
                'title' => $def['title'],
                'cuisine' => $def['cuisine'],
                'base_servings' => 4,
                'prep_minutes' => $def['prep_minutes'],
                'duration_minutes' => $def['prep_minutes'],
                'intro' => $def['intro'],
                'tagline' => $def['intro'],
                'steps' => $def['steps'],
                'image' => $imagePath,
                'image_url' => $imagePath,
                'thumbnail' => file_exists(public_path("images/store/recipes/thumbs/{$slug}.webp")) ? "images/store/recipes/thumbs/{$slug}.webp" : $imagePath,
                'status' => Recipe::STATUS_APPROVED,
                'approved_at' => now(),
                'review_due_at' => now()->addYear(),
            ]);

            // Add recipe ingredients
            foreach ($def['ingredients'] as $i => $ri) {
                if (! isset($ingredients[$ri['slug']])) {
                    continue;
                }
                RecipeIngredient::updateOrCreate(
                    [
                        'recipe_id' => $recipe->id,
                        'ingredient_id' => $ingredients[$ri['slug']]->id,
                    ],
                    [
                        'role' => $ri['role'],
                        'people_per_pack' => $ri['people_per_pack'],
                        'sort_order' => $i,
                    ]
                );
            }
        }

        // 5. Meal Combos
        $jollof = Recipe::where('slug', 'jollof-rice')->first();
        $egusi = Recipe::where('slug', 'egusi-soup')->first();
        $banga = Recipe::where('slug', 'banga-soup')->first();

        if ($jollof && $egusi) {
            $combo = MealCombo::updateOrCreate(['slug' => 'nigerian-feast-combo'], [
                'name' => 'Nigerian Feast: Jollof & Egusi',
                'status' => 'active',
            ]);
            $combo->recipes()->sync([
                $jollof->id => ['sort_order' => 1],
                $egusi->id => ['sort_order' => 2],
            ]);
        }

        if ($egusi && $banga) {
            $combo2 = MealCombo::updateOrCreate(['slug' => 'delta-delight-combo'], [
                'name' => 'Delta Delight: Egusi & Banga',
                'status' => 'active',
            ]);
            $combo2->recipes()->sync([
                $egusi->id => ['sort_order' => 1],
                $banga->id => ['sort_order' => 2],
            ]);
        }
    }
}
