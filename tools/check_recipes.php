<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Recipe;
use Illuminate\Contracts\Console\Kernel;

$recipes = Recipe::approved()->orderBy('is_featured', 'desc')->orderBy('sort_order')->get();

foreach ($recipes as $r) {
    echo "ID: {$r->id} | Slug: {$r->slug} | Title: {$r->title} | sort_order: {$r->sort_order} | is_featured: {$r->is_featured}\n";
    echo "   Image: {$r->image} | Image_URL: {$r->image_url}\n";
}
