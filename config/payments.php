<?php

return [
    'methods' => [
        'card' => [
            'label' => 'Pay by Card',
            'description' => 'Credit/Debit card via Square secure web tokenization.',
            'fulfilment' => ['pickup', 'delivery'],
        ],
        'wallet' => [
            'label' => 'Apple Pay / Google Pay',
            'description' => 'Digital wallet checkout on supported browsers.',
            'fulfilment' => ['pickup', 'delivery'],
        ],
        'etransfer' => [
            'label' => 'Interac e-Transfer',
            'description' => 'Send an Interac e-Transfer from your bank. We confirm it by hand before preparing the order.',
            'fulfilment' => ['pickup', 'delivery'],
        ],
    ],

    // Interac e-Transfer details shown to the shopper after they place the order.
    // Square doesn't take Interac online, so staff confirm each transfer in the admin queue.
    'etransfer' => [
        'email' => env('SHOP_ETRANSFER_EMAIL', 'payments@isaaleeko-demo.ca'),
        'recipient' => env('SHOP_ETRANSFER_RECIPIENT', 'Ìsàlèè Èkó'),
        // Orders not paid within this many hours can be cancelled by staff.
        'hold_hours' => (int) env('SHOP_ETRANSFER_HOLD_HOURS', 24),
    ],
];
