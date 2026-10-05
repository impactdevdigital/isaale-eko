<?php
// app/Console/Commands/SquarePing.php

namespace App\Console\Commands;

use App\Services\Square\SquareGateway;
use Illuminate\Console\Command;

class SquarePing extends Command
{
    protected $signature = 'square:ping';
    protected $description = 'Check that we can talk to Square';

    public function handle(SquareGateway $square): int
    {
        try {
            $locations = $square->listLocations();
        } catch (\Throwable $e) {
            $this->error('Could not reach Square: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Connected to Square (' . config('services.square.env') . ')');
        foreach ($locations as $l) {
            $this->line("- {$l['name']}  (id: {$l['id']}, status: " . ($l['status'] ?? '?') . ')');
        }
        return self::SUCCESS;
    }
}