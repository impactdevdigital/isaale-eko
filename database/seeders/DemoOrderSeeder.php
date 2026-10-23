<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('variations')->take(5)->get();

        if ($products->isEmpty()) {
            return;
        }

        // 1. New Unprepared Pickup Order (Pay at Counter)
        $order1 = Order::firstOrCreate(
            ['number' => 'ISA-794012'],
            [
                'status' => Order::STATUS_PLACED,
                'fulfilment' => 'pickup',
                'contact_name' => 'Chioma Adeyemi',
                'contact_email' => 'chioma.adeyemi@example.com',
                'contact_phone' => '+1 (506) 555-0142',
                'pickup_starts_at' => now()->addHours(2),
                'pickup_ends_at' => now()->addHours(4),
                'subtotal_cents' => 3897,
                'savings_cents' => 400,
                'discount_cents' => 0,
                'delivery_fee_cents' => 0,
                'total_cents' => 3897,
                'payment_method' => 'pay_at_pickup',
                'placed_at' => now()->subMinutes(25),
                'fulfilment_status' => null,
            ]
        );

        if ($order1->wasRecentlyCreated && $products->count() >= 2) {
            $p1 = $products[0];
            $p2 = $products[1];
            OrderItem::create([
                'order_id' => $order1->id,
                'product_id' => $p1->id,
                'name' => $p1->name,
                'variation_name' => $p1->variations->first()?->name ?? 'Standard',
                'image' => $p1->image,
                'unit_price_cents' => 1299,
                'quantity' => 2,
                'line_total_cents' => 2598,
            ]);
            OrderItem::create([
                'order_id' => $order1->id,
                'product_id' => $p2->id,
                'name' => $p2->name,
                'variation_name' => $p2->variations->first()?->name ?? 'Standard',
                'image' => $p2->image,
                'unit_price_cents' => 1299,
                'quantity' => 1,
                'line_total_cents' => 1299,
            ]);
        }

        // 2. Preparing Local Delivery Order (Paid Online via Card)
        $order2 = Order::firstOrCreate(
            ['number' => 'ISA-832104'],
            [
                'status' => Order::STATUS_PAID,
                'paid_at' => now()->subHour(),
                'fulfilment' => 'delivery',
                'contact_name' => 'Kwame Mensah',
                'contact_email' => 'kwame.mensah@example.com',
                'contact_phone' => '+1 (506) 555-0199',
                'delivery_address' => [
                    'street' => '142 King Street East, Apt 4B',
                    'city' => 'Saint John',
                    'region' => 'NB',
                    'postal_code' => 'E2L 1G8',
                    'country' => 'CA',
                ],
                'subtotal_cents' => 5490,
                'savings_cents' => 0,
                'discount_cents' => 500,
                'delivery_fee_cents' => 499,
                'total_cents' => 5489,
                'coupon_code' => 'WELCOME5',
                'payment_method' => 'card',
                'placed_at' => now()->subHours(1),
                'fulfilment_status' => Order::FULFILMENT_PREPARING,
            ]
        );

        if ($order2->wasRecentlyCreated && $products->count() >= 3) {
            $p3 = $products[2];
            OrderItem::create([
                'order_id' => $order2->id,
                'product_id' => $p3->id,
                'name' => $p3->name,
                'variation_name' => $p3->variations->first()?->name ?? 'Standard',
                'image' => $p3->image,
                'unit_price_cents' => 2745,
                'quantity' => 2,
                'line_total_cents' => 5490,
            ]);
        }

        // 3. Ready for Collection Pickup Order (Paid Online via Apple Pay)
        $order3 = Order::firstOrCreate(
            ['number' => 'ISA-910283'],
            [
                'status' => Order::STATUS_PAID,
                'paid_at' => now()->subHours(3),
                'fulfilment' => 'pickup',
                'contact_name' => 'Folake Balogun',
                'contact_email' => 'folake.b@example.com',
                'contact_phone' => '+1 (506) 555-0211',
                'pickup_starts_at' => now()->subMinutes(30),
                'pickup_ends_at' => now()->addHours(1),
                'subtotal_cents' => 2498,
                'savings_cents' => 200,
                'discount_cents' => 0,
                'delivery_fee_cents' => 0,
                'total_cents' => 2498,
                'payment_method' => 'card',
                'placed_at' => now()->subHours(3),
                'fulfilment_status' => Order::FULFILMENT_READY,
            ]
        );

        if ($order3->wasRecentlyCreated && $products->count() >= 1) {
            $p1 = $products[0];
            OrderItem::create([
                'order_id' => $order3->id,
                'product_id' => $p1->id,
                'name' => $p1->name,
                'variation_name' => $p1->variations->first()?->name ?? 'Standard',
                'image' => $p1->image,
                'unit_price_cents' => 1249,
                'quantity' => 2,
                'line_total_cents' => 2498,
            ]);
        }

        // 4. Completed Order Earlier Today
        $order4 = Order::firstOrCreate(
            ['number' => 'ISA-654129'],
            [
                'status' => Order::STATUS_PAID,
                'paid_at' => now()->subHours(6),
                'fulfilment' => 'pickup',
                'contact_name' => 'Tariq Al-Mansoor',
                'contact_email' => 'tariq.m@example.com',
                'contact_phone' => '+1 (506) 555-0388',
                'pickup_starts_at' => now()->subHours(6),
                'pickup_ends_at' => now()->subHours(4),
                'subtotal_cents' => 1899,
                'savings_cents' => 0,
                'discount_cents' => 0,
                'delivery_fee_cents' => 0,
                'total_cents' => 1899,
                'payment_method' => 'pay_at_pickup',
                'placed_at' => now()->subHours(6),
                'fulfilment_status' => Order::FULFILMENT_COMPLETED,
            ]
        );

        if ($order4->wasRecentlyCreated && $products->count() >= 1) {
            $p1 = $products[0];
            OrderItem::create([
                'order_id' => $order4->id,
                'product_id' => $p1->id,
                'name' => $p1->name,
                'variation_name' => $p1->variations->first()?->name ?? 'Standard',
                'image' => $p1->image,
                'unit_price_cents' => 1899,
                'quantity' => 1,
                'line_total_cents' => 1899,
            ]);
        }
    }
}
