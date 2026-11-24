<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Story;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml: every public page for Google and Bing, rebuilt hourly.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = collect([
            [route('home'), null, 'daily', '1.0'],
            [route('shop'), null, 'daily', '0.9'],
            [route('kitchen.index'), null, 'weekly', '0.7'],
            [route('stories.index'), null, 'weekly', '0.6'],
            [route('updates.index'), null, 'weekly', '0.5'],
            [route('community'), null, 'monthly', '0.5'],
            [route('help'), null, 'monthly', '0.5'],
            [route('about'), null, 'monthly', '0.5'],
            [route('privacy-policy'), null, 'yearly', '0.2'],
            [route('refund-policy'), null, 'yearly', '0.2'],
        ]);

        Country::listed()->whereHas('products', fn ($q) => $q->active())->get()
            ->each(fn (Country $c) => $urls->push([route('shop.country', $c), $c->updated_at, 'weekly', '0.8']));

        Category::whereHas('products', fn ($q) => $q->active())->get()
            ->each(fn (Category $c) => $urls->push([route('shop', ['category' => $c->slug]), $c->updated_at, 'weekly', '0.7']));

        Product::active()->select(['id', 'slug', 'updated_at'])->orderBy('id')->get()
            ->each(fn (Product $p) => $urls->push([route('product', $p), $p->updated_at, 'weekly', '0.8']));

        Recipe::approved()->get()
            ->each(fn (Recipe $r) => $urls->push([route('kitchen.recipe', $r), $r->updated_at, 'monthly', '0.6']));

        Story::published()->get()
            ->each(fn (Story $s) => $urls->push([$s->url(), $s->updated_at, 'monthly', '0.5']));

        $body = $urls->unique(0)->map(function ($u) {
            [$loc, $updated, $freq, $priority] = $u;

            return '  <url><loc>'.e($loc).'</loc>'
                .($updated ? '<lastmod>'.$updated->toAtomString().'</lastmod>' : '')
                ."<changefreq>{$freq}</changefreq><priority>{$priority}</priority></url>";
        })->implode("\n");

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n{$body}\n</urlset>\n";
    }
}
