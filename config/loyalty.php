<?php

/*
 * Loyalty runs on Square Loyalty (the programme set up in the Square
 * Dashboard: 1 point per $1, 100 points = $3 off). The website signs
 * shoppers up by the phone number they give at checkout, adds points for
 * paid online orders, shows the balance, and lets signed-in shoppers spend
 * a reward online. Points earned or spent in store are the same account.
 *
 * It uses the Square account that takes card payments (see
 * SquareGateway::payments()), so test payments only ever touch a sandbox
 * programme and real points start with real payments. It switches itself
 * off when that account has no active loyalty programme.
 */
return [

    // Kill switch: LOYALTY_ENABLED=false turns the website side off.
    'enabled' => (bool) env('LOYALTY_ENABLED', true),

    // Orders older than this aren't retried by loyalty:sync.
    'retry_days' => 7,

];
