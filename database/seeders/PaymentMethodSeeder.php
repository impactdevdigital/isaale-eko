<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = array_keys(config('payments.methods', []));

        foreach ($methods as $index => $code) {
            PaymentMethod::firstOrCreate(
                ['code' => $code],
                [
                    'is_enabled' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
