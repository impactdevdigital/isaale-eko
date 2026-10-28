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
    ],
];
