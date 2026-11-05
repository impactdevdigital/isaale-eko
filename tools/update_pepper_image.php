<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Recipe;
use Illuminate\Contracts\Console\Kernel;

$pepper = Recipe::where('slug', 'pepper-soup')->first();
if ($pepper) {
    $pepper->image = 'images/store/recipes/pepper-soup.webp';
    $pepper->image_url = 'images/store/recipes/pepper-soup.webp';
    $pepper->save();
    echo "Updated Pepper Soup image to images/store/recipes/pepper-soup.webp\n";
}
