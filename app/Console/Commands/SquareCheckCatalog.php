<?php

namespace App\Console\Commands;

use App\Services\Square\CatalogCheck;
use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only: is the website's catalogue in step with Square?
 *
 * By default it uses this app's Square settings. To check another Square
 * account (e.g. the live one) without putting its keys in .env, put
 * SQUARE_ENV, SQUARE_ACCESS_TOKEN and SQUARE_LOCATION_ID in a separate
 * file (.env.square-live is git-ignored) and pass --credentials. The keys
 * are only used for this run and never printed.
 */
class SquareCheckCatalog extends Command
{
    protected $signature = 'square:check-catalog
        {--credentials= : A file with SQUARE_ENV, SQUARE_ACCESS_TOKEN and SQUARE_LOCATION_ID to use instead of .env}';

    protected $description = 'Compare Square items, categories, sub-categories and prices with the website (read-only)';

    public function handle(): int
    {
        if ($file = $this->option('credentials')) {
            if (! is_readable($file)) {
                $this->error("Can't read {$file}.");

                return self::FAILURE;
            }

            $values = Dotenv::parse(file_get_contents($file));
            config(array_filter([
                'services.square.env' => $values['SQUARE_ENV'] ?? null,
                'services.square.token' => $values['SQUARE_ACCESS_TOKEN'] ?? null,
                'services.square.location_id' => $values['SQUARE_LOCATION_ID'] ?? null,
                'services.square.version' => $values['SQUARE_VERSION'] ?? null,
            ]));
        }

        if (! config('services.square.token') || ! config('services.square.location_id')) {
            $this->error('Square is not configured (SQUARE_ACCESS_TOKEN / SQUARE_LOCATION_ID).');

            return self::FAILURE;
        }

        $this->line('Checking Square <info>'.config('services.square.env').'</info>, location <info>'.config('services.square.location_id').'</info> against database <info>'.config('database.connections.'.config('database.default').'.database').'</info>.');
        $this->line('Nothing is changed in Square or on the site.');
        $this->newLine();

        $report = app(CatalogCheck::class)->run();

        $this->table(['', 'Count'], collect($report['summary'])->map(fn ($n, $k) => [ucfirst(str_replace('_', ' ', $k)), $n])->values()->all());

        $text = 'SQUARE CATALOGUE CHECK '.now()->toDateTimeString().' ('.config('services.square.env').")\n";

        foreach (CatalogCheck::LABELS as $key => $label) {
            $list = data_get($report, $key, []);
            $text .= "\n".strtoupper($label).' ('.count($list).")\n".implode("\n", array_map(fn ($l) => '  - '.$l, $list))."\n";

            if ($list !== []) {
                $this->newLine();
                $this->line("<comment>{$label}</comment> (".count($list).')');
                collect($list)->take(15)->each(fn ($l) => $this->line('  - '.$l));

                if (count($list) > 15) {
                    $this->line('  … and '.(count($list) - 15).' more in the report file');
                }
            }
        }

        Storage::put('square-catalog-check.txt', $text);
        $this->newLine();
        $this->info($report['summary']['differences'] === 0 ? 'Square and the site are in step.' : 'Full report: '.Storage::path('square-catalog-check.txt'));

        return self::SUCCESS;
    }
}
