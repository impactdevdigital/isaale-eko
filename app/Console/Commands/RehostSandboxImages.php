<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Products adopted from the Square sandbox kept photos hosted by the sandbox
 * account when the live item has none. Copy those photos onto the site so
 * they no longer depend on the test account. Safe to run again.
 */
class RehostSandboxImages extends Command
{
    protected $signature = 'square:rehost-sandbox-images {--dry-run : List what would change}';

    protected $description = 'Copy product photos still hosted by the Square sandbox onto the site';

    public function handle(): int
    {
        $products = Product::where('image', 'like', '%items-images-sandbox%')
            ->orWhere('gallery', 'like', '%items-images-sandbox%')
            ->get();

        $this->info("{$products->count()} products use sandbox photos.");
        [$copied, $failed] = [0, 0];

        foreach ($products as $product) {
            if ($this->option('dry-run')) {
                $this->line("{$product->name}: {$product->image}");

                continue;
            }

            $image = $this->rehost($product->image, $product->slug);
            $gallery = $product->gallery
                ? array_values(array_filter(array_map(fn ($url) => $this->rehost($url, $product->slug), $product->gallery)))
                : $product->gallery;

            if ($image === null) {
                $failed++;
                $this->warn("Could not copy the photo for {$product->name}; left as it was.");

                continue;
            }

            $product->forceFill(['image' => $image, 'gallery' => $gallery])->save();
            $copied++;
        }

        if (! $this->option('dry-run')) {
            $this->info("Copied {$copied}, failed {$failed}.");
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Returns the site path for a sandbox URL, or the value unchanged when it isn't one.
     */
    private function rehost(?string $url, string $slug): ?string
    {
        if (! $url || ! str_contains($url, 'items-images-sandbox')) {
            return $url;
        }

        $extension = Str::lower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION)) ?: 'jpg';
        $path = 'products/square/'.$slug.'-'.substr(sha1($url), 0, 8).'.'.$extension;

        if (! Storage::disk('public')->exists($path)) {
            $response = rescue(fn () => Http::timeout(30)->retry(2, 500)->get($url), null, false);

            if (! $response?->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                return null;
            }

            Storage::disk('public')->put($path, $response->body());
        }

        return 'storage/'.$path;
    }
}
