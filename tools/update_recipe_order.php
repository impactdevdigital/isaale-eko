<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Recipe;
use Illuminate\Contracts\Console\Kernel;

$order = [
    'jollof-rice' => 1,
    'egusi-soup' => 2,
    'banga-soup' => 3,
    'curry-goat' => 4,
    'pepper-soup' => 5,
    'zobo-hibiscus-drink' => 6,
];

foreach ($order as $slug => $sort) {
    $recipe = Recipe::where('slug', $slug)->first();
    if ($recipe) {
        $recipe->sort_order = $sort;
        $recipe->is_featured = 1;
        $recipe->save();
        echo "Updated {$slug} to sort_order={$sort}, is_featured=1\n";
    }
}
