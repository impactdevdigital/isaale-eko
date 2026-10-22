<?php

return [
    'pickup' => [
        'label' => 'Pickup & Scheduling',
        'description' => 'Control when and how often customers can schedule store pickups.',
        'icon' => 'clock',
        'fields' => [
            'pickup_lead_minutes' => [
                'label' => 'Earliest pickup (minutes from now)',
                'type' => 'number',
                'default' => 30,
                'rules' => 'required|integer|min:0|max:1440',
                'help' => 'Preparation buffer time needed before first pickup.'
            ],
            'pickup_step_minutes' => [
                'label' => 'Gap between pickup slots (minutes)',
                'type' => 'number',
                'default' => 15,
                'rules' => 'required|integer|in:5,10,15,20,30,60',
                'help' => 'Interval between available customer pickup slots.'
            ],
            'pickup_last_slot_buffer_minutes' => [
                'label' => 'Last pickup time before closing (minutes)',
                'type' => 'number',
                'default' => 15,
                'rules' => 'required|integer|min:0|max:240',
                'help' => 'Cutoff before official store closure.'
            ],
            'pickup_days_ahead' => [
                'label' => 'Booking horizon (days ahead)',
                'type' => 'number',
                'default' => 7,
                'rules' => 'required|integer|min:1|max:30',
                'help' => 'How many days in advance customers can place orders.'
            ],
            'pickup_followup_minutes' => [
                'label' => 'Follow-up reminder delay (minutes)',
                'type' => 'number',
                'default' => 30,
                'rules' => 'required|integer|min:5|max:240',
                'help' => 'Time to wait after scheduled pickup before sending late notice.'
            ],
        ]
    ],

    'uncollected' => [
        'label' => 'Uncollected Orders',
        'description' => 'Rules for orders left waiting past their scheduled pickup date.',
        'icon' => 'archive',
        'fields' => [
            'uncollected_grace_days' => [
                'label' => 'Grace period (days after pickup time)',
                'type' => 'number',
                'default' => 3,
                'rules' => 'required|integer|min:1|max:14',
                'help' => 'Days store will hold order before marking uncollected.'
            ],
            'uncollected_action' => [
                'label' => 'Action when grace period expires',
                'type' => 'select',
                'options' => [
                    'hold_for_staff' => 'Hold for staff review',
                    'auto_cancel' => 'Cancel automatically'
                ],
                'default' => 'hold_for_staff',
                'rules' => 'required|in:hold_for_staff,auto_cancel',
                'help' => 'Whether staff decides or the system automatically cancels.'
            ],
            'uncollected_refund_percent' => [
                'label' => 'Refund percentage for uncollected cancellations (%)',
                'type' => 'number',
                'default' => 100,
                'rules' => 'required|integer|min:0|max:100',
                'help' => 'Percentage of total order refunded if cancelled.'
            ],
        ]
    ],

    'catalogue' => [
        'label' => 'Stock & Tags',
        'description' => 'Thresholds for low inventory badges and algorithmic product tags.',
        'icon' => 'tag',
        'fields' => [
            'low_stock_threshold' => [
                'label' => 'Low stock warning threshold',
                'type' => 'number',
                'default' => 5,
                'rules' => 'required|integer|min:1|max:1000',
                'help' => 'Quantities at or below this value display a "Low Stock" badge.'
            ],
            'new_arrival_days' => [
                'label' => 'New Arrival badge duration (days)',
                'type' => 'number',
                'default' => 21,
                'rules' => 'required|integer|min:7|max:60',
                'help' => 'Days a freshly synced product retains the "New" tag.'
            ],
            'best_seller_days' => [
                'label' => 'Best Sellers lookback window (days)',
                'type' => 'number',
                'default' => 30,
                'rules' => 'required|integer|min:7|max:365',
                'help' => 'Sales period evaluated to calculate bestsellers.'
            ],
            'best_seller_count' => [
                'label' => 'Best Sellers display limit',
                'type' => 'number',
                'default' => 12,
                'rules' => 'required|integer|min:1|max:100',
                'help' => 'Maximum top products tagged as bestseller.'
            ],
        ]
    ],

    'delivery' => [
        'label' => 'Local Delivery',
        'description' => 'Delivery radius limits around the store location.',
        'icon' => 'truck',
        'fields' => [
            'delivery_radius_km' => [
                'label' => 'Maximum delivery radius (km)',
                'type' => 'number',
                'default' => 15,
                'rules' => 'required|integer|min:1|max:200',
                'help' => 'Orders outside this straight-line radius will be prompted for pickup.'
            ],
        ]
    ],

    'issues' => [
        'label' => 'Returns & Issues Policy',
        'description' => 'Customer claim reporting windows according to store policy.',
        'icon' => 'shield-alert',
        'fields' => [
            'issue_window_perishable_hours' => [
                'label' => 'Report window: Food & Perishables (hours)',
                'type' => 'number',
                'default' => 24,
                'rules' => 'required|integer|min:1|max:720',
                'help' => 'Hours from delivery/pickup customer has to report problems with food.'
            ],
            'issue_window_grocery_hours' => [
                'label' => 'Report window: Non-perishable groceries (hours)',
                'type' => 'number',
                'default' => 24,
                'rules' => 'required|integer|min:1|max:720',
                'help' => 'Reporting window for dry/shelf-stable pantry items.'
            ],
            'issue_window_nonfood_days' => [
                'label' => 'Report window: Non-food merchandise (days)',
                'type' => 'number',
                'default' => 7,
                'rules' => 'required|integer|min:1|max:90',
                'help' => 'Reporting window for non-food items (defects, damage).'
            ],
            'return_window_nonfood_days' => [
                'label' => 'Change-of-mind return window: Non-food (days)',
                'type' => 'number',
                'default' => 7,
                'rules' => 'required|integer|min:1|max:90',
                'help' => 'Return period for unused non-food products in original packaging.'
            ],
            'issue_response_hours' => [
                'label' => 'Staff response target (hours)',
                'type' => 'number',
                'default' => 24,
                'rules' => 'required|integer|min:1|max:168',
                'help' => 'Internal SLA target for staff to reply to open issue cases.'
            ],
            'substitution_response_minutes' => [
                'label' => 'Customer substitution response window (minutes)',
                'type' => 'number',
                'default' => 15,
                'rules' => 'required|integer|min:5|max:120',
                'help' => 'Time to wait for customer confirmation before auto-refunding item.'
            ],
        ]
    ],

    'contact' => [
        'label' => 'Support & Contact',
        'description' => 'Contact points displayed across policies, Asha, and order confirmations.',
        'icon' => 'phone',
        'fields' => [
            'support_email' => [
                'label' => 'Support Email',
                'type' => 'text',
                'default' => 'support@isaleeeko.com',
                'rules' => 'required|email|max:120',
                'help' => 'Customer service inquiry destination.'
            ],
            'support_phone' => [
                'label' => 'Support Phone',
                'type' => 'text',
                'default' => '+1 506-333-7474',
                'rules' => 'required|string|max:30',
                'help' => 'Store telephone number.'
            ],
            'support_whatsapp' => [
                'label' => 'Support WhatsApp',
                'type' => 'text',
                'default' => '+1 506-333-7474',
                'rules' => 'required|string|max:30',
                'help' => 'Direct WhatsApp support line for urgent allergen/safety reports.'
            ],
        ]
    ],

    'language' => [
        'label' => 'Language & Fallbacks',
        'description' => 'Bilingual translation approval settings.',
        'icon' => 'globe',
        'fields' => [
            'show_unreviewed_translations' => [
                'label' => 'Show unreviewed French translation drafts?',
                'type' => 'select',
                'options' => [
                    'no' => 'No — Show English master until French is reviewed',
                    'yes' => 'Yes — Show draft French translations immediately'
                ],
                'default' => 'no',
                'rules' => 'required|in:yes,no',
                'help' => 'Prevents raw drafts from being visible on the public site.'
            ],
            'asha_translate_english_kb' => [
                'label' => 'Allow Asha to answer in French from English knowledge base articles?',
                'type' => 'select',
                'options' => [
                    'yes' => 'Yes — Translate on-the-fly (excluding policies)',
                    'no' => 'No — State article is only available in English'
                ],
                'default' => 'yes',
                'rules' => 'required|in:yes,no',
                'help' => 'Allows Asha to be helpful when French FAQ articles are pending.'
            ],
        ]
    ],
];
