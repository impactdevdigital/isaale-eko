<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\StoreClosure;
use App\Models\StoreHour;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

/**
 * About Ìsàlèè Èkó: the client's story copy, store photos, opening hours
 * (from Admin → Hours & Closures) and how to find the shop.
 */
class AboutController extends Controller
{
    public const GALLERY = [
        ['file' => 'grains-aisle', 'alt' => 'Aisle of flours, grains and garri'],
        ['file' => 'pantry-corner', 'alt' => 'Milk, Milo, tomato paste and noodles on the shelves'],
        ['file' => 'own-brand-spices', 'alt' => 'Ìsàlèè Èkó own-brand spice jars'],
        ['file' => 'own-brand-grains', 'alt' => 'Ìsàlèè Èkó own-brand bagged beans and grains'],
    ];

    public function __invoke(): View
    {
        $tz = config('shop.timezone', 'America/Moncton');

        // Monday first, Sunday last.
        $hours = StoreHour::all()
            ->sortBy(fn (StoreHour $h) => ($h->weekday + 6) % 7)
            ->map(fn (StoreHour $h) => [
                'day' => __($h->weekdayName()),
                'today' => $h->weekday === now($tz)->dayOfWeek,
                'time' => $h->is_closed || ! $h->opens_at || ! $h->closes_at
                    ? __('Closed')
                    : Carbon::parse($h->opens_at)->format('g:i A').' – '.Carbon::parse($h->closes_at)->format('g:i A'),
            ])
            ->values();

        $closures = StoreClosure::whereDate('date', '>=', now($tz)->toDateString())
            ->whereDate('date', '<=', now($tz)->addDays(30)->toDateString())
            ->orderBy('date')
            ->get();

        $location = config('shop.pickup.location', 'Saint John, NB, Canada');

        return view('store.about', [
            'hours' => $hours,
            'closures' => $closures,
            'location' => $location,
            'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Ìsàlèè Èkó '.$location),
            'supportEmail' => Setting::get('support_email', 'support@isaleeko.com'),
            'supportPhone' => Setting::get('support_phone'),
            'gallery' => self::GALLERY,
        ]);
    }
}
