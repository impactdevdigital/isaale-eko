<?php

return [

    'currency' => env('SHOP_CURRENCY', 'CAD'),

    'timezone' => env('SHOP_TIMEZONE', 'America/Moncton'),

    'pickup' => [
        'location' => 'Saint John',

        // Customers pick a window of this many hours; capacity is per window.
        'window_hours' => 2,
        'window_capacity' => (int) env('SHOP_PICKUP_WINDOW_CAPACITY', 20),
    ],

    'delivery' => [
        'fee_cents' => (int) env('SHOP_DELIVERY_FEE_CENTS', 799),
        'free_over_cents' => (int) env('SHOP_FREE_DELIVERY_OVER_CENTS', 4000),

        // Postal code prefixes (forward sortation areas) we deliver to. "E2" covers Saint John.
        // Replace with real coverage data once the delivery radius is mapped.
        'postal_prefixes' => array_filter(explode(',', env('SHOP_DELIVERY_POSTAL_PREFIXES', 'E2'))),
    ],

    'verification' => [
        'code_ttl_minutes' => 10,
        'resend_after_seconds' => 30,
        'max_attempts' => 5,
    ],

];
