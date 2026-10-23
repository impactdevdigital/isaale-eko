<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Account overview (Figma 06.03): balances and the latest orders.
     */
    public function overview(Request $request): View
    {
        $orders = $request->user()->orders()->visibleToCustomer();

        return view('store.account.overview', [
            'orderCount' => (clone $orders)->count(),
            'tabCounts' => collect(['unpaid', 'processing', 'shipped'])
                ->mapWithKeys(fn ($tab) => [$tab => (clone $orders)->inTab($tab)->count()]),
            'recent' => (clone $orders)->with('items')->limit(2)->get(),
        ]);
    }
}
