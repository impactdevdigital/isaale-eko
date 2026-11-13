<?php

/*
 * Loyalty (Key Features §3): every $100 of eligible spend unlocks $3 off.
 *
 * Shoppers only ever see dollars ("$76 / $100"); behind the scenes it is
 * 1 point per whole dollar, the way Square Loyalty would run it with points
 * hidden. Eligible spend is the items after any discount: no delivery fee,
 * no tax. The remainder carries over into the next $100.
 */
return [

    // Which engine runs loyalty: "website" today. Once the Square Loyalty
    // spike passes, a SquareLoyaltyProvider can be bound here instead.
    'provider' => env('LOYALTY_PROVIDER', 'website'),

    'points_per_dollar' => 1,

    // Spend (in points, i.e. dollars) that unlocks one reward.
    'block_points' => 100,

    // The reward: a fixed amount off an order over a minimum.
    'reward_value_cents' => 300,
    'reward_min_order_cents' => 2500,

    // An unlocked reward must be used within this many days.
    'reward_valid_days' => 30,

];
