<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Order page for the signed-in owner only; anyone else gets a 404 so
     * order numbers can't be probed.
     */
    public function show(Request $request, Order $order): View
    {
        abort_unless($request->user()?->is($order->user), 404);

        return view('store.order', ['order' => $order->load('items')]);
    }
}
