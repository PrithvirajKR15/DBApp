<?php

/**
 * Per-order customer/delivery extras keyed by order code.
 * Fallback defaults are applied in OrderSeeder when a code is missing.
 */
return [
    'ORD-8823-XYZ' => [
        'customer_code' => 'CUS-2048',
        'vip' => true,
        'phone_alt' => '+1 555-0199',
        'avatar' => '6.png',
        'landmark' => 'Blue gate next to the bakery — use side entrance',
        'instructions' => 'Ring bell twice, leave at side door if no answer. Fragile items in the order.',
        'packages' => '3 Bags · 1 Heavy',
        'weight' => '~8.4 kg',
        'card_last4' => '4321',
        'distance_km' => 4.2,
        'eta' => '~18 min',
        'line_item_count' => 8,
    ],
    'ORD-8819-ABC' => [
        'customer_code' => 'CUS-1190',
        'vip' => false,
        'phone_alt' => '+1 555-0177',
        'avatar' => '2.png',
        'landmark' => 'Opposite the temple parking lot',
        'instructions' => 'Call on arrival. Leave with security if no answer.',
        'packages' => '2 Bags',
        'weight' => '~5.1 kg',
        'card_last4' => null,
        'distance_km' => 3.1,
        'eta' => '22m',
        'line_item_count' => 4,
    ],
    'ORD-8815-QWE' => [
        'customer_code' => 'CUS-3301',
        'vip' => true,
        'phone_alt' => '+1 555-0122',
        'avatar' => '3.png',
        'landmark' => 'Hospital gate 2, white building',
        'instructions' => 'Hand to receptionist at ward 3.',
        'packages' => '5 Bags · 2 Cold',
        'weight' => '~12.0 kg',
        'card_last4' => '8890',
        'distance_km' => 2.8,
        'eta' => '~15 min',
        'line_item_count' => 8,
    ],
];
