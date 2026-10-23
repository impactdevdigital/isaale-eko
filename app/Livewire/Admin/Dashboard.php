<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Recipe;
use App\Models\StoreClosure;
use App\Models\StoreHour;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Admin Operations Overview Dashboard.
 * Serves as the central command center for store managers and associates upon login.
 * Aggregates live fulfillment backlog, today's sales velocity, low stock alerts,
 * and quick-action navigation.
 */
class Dashboard extends Component
{
    public function render()
    {
        $today = Carbon::today();

        // 1. Today's Sales & Orders
        $todayOrdersQuery = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->whereDate('placed_at', $today);

        $todaySalesCents = (int) $todayOrdersQuery->sum('total_cents');
        $todayOrdersCount = (int) $todayOrdersQuery->count();

        // 2. Active Fulfillment Backlog (Placed / Paid orders awaiting completion)
        $activeOrdersQuery = Order::whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])
            ->where(fn ($q) => $q->whereNull('fulfilment_status')->orWhere('fulfilment_status', '!=', Order::FULFILMENT_COMPLETED));

        $activeOrdersCount = (int) $activeOrdersQuery->count();

        // 3. Urgent Orders needing packing (oldest first)
        $urgentOrders = (clone $activeOrdersQuery)
            ->with(['items', 'user'])
            ->orderBy('placed_at', 'asc')
            ->take(5)
            ->get();

        // 4. Low Stock / Depleted Inventory Alert
        $lowStockVariations = ProductVariation::with('product')
            ->whereNotNull('stock_quantity')
            ->where('stock_quantity', '<=', 5)
            ->orderBy('stock_quantity', 'asc')
            ->take(5)
            ->get();

        $totalLowStockCount = ProductVariation::whereNotNull('stock_quantity')
            ->where('stock_quantity', '<=', 5)
            ->count();

        // 5. Store Operating Status for Today
        $now = Carbon::now();
        $weekday = $now->dayOfWeek; // 0 (Sun) to 6 (Sat)
        $todaySchedule = StoreHour::where('weekday', $weekday)->first();
        $isHolidayClosure = StoreClosure::whereDate('date', $today)->exists();

        $storeIsOpen = false;
        $storeStatusText = 'Closed';

        if (! $isHolidayClosure && $todaySchedule && ! $todaySchedule->is_closed) {
            $currentTimeStr = $now->format('H:i:s');
            $opensAt = $todaySchedule->opens_at;
            $closesAt = $todaySchedule->closes_at;

            if ($opensAt && $closesAt && $currentTimeStr >= $opensAt && $currentTimeStr <= $closesAt) {
                $storeIsOpen = true;
                $storeStatusText = 'Open until '.Carbon::parse($closesAt)->format('g:i A');
            } elseif ($opensAt) {
                $storeStatusText = 'Opens at '.Carbon::parse($opensAt)->format('g:i A');
            } else {
                $storeStatusText = 'Open Today';
            }
        } elseif ($isHolidayClosure) {
            $storeStatusText = 'Closed for Holiday';
        }

        // 6. Recent Activity Stream
        $recentOrders = Order::with('items')
            ->orderByDesc('placed_at')
            ->take(6)
            ->get();

        // 7. General Store Asset Counts
        $kpis = [
            'today_sales_cents' => $todaySalesCents,
            'today_orders_count' => $todayOrdersCount,
            'active_fulfillment_count' => $activeOrdersCount,
            'low_stock_count' => $totalLowStockCount,
            'store_is_open' => $storeIsOpen,
            'store_status_text' => $storeStatusText,
            'total_products' => Product::count(),
            'total_recipes' => Recipe::count(),
        ];

        return view('livewire.admin.dashboard', [
            'kpis' => $kpis,
            'urgentOrders' => $urgentOrders,
            'lowStockVariations' => $lowStockVariations,
            'recentOrders' => $recentOrders,
        ])->layout('layouts.admin', ['title' => 'Operations Overview', 'header' => 'Operations Command Center']);
    }
}
