<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsEvent;
use App\Services\Analytics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Analytics & Measurement: what shoppers did on the website, from the
 * analytics_events log. Discovery, commerce and pickup events, the
 * shopping funnel, and the top searches and products. When Google
 * Analytics is connected the same events go there too.
 *
 * Periods are store days (Atlantic time): today, the last 7/30/90 days,
 * or one chosen day, with a day-by-day breakdown for ranges.
 */
class AnalyticsDashboard extends Component
{
    public const RANGES = [1 => 'Today', 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    /** Event groups shown on the screen: name => label. */
    public const GROUPS = [
        'Discovery' => [
            'page_view' => 'Page views',
            'view_item' => 'Product views',
            'search' => 'Searches',
            'search_no_results' => 'Searches with no results',
            'select_category' => 'Category views',
            'select_country' => 'Country views',
            'view_recipe' => 'Recipe views',
            'view_content' => 'Story views',
        ],
        'Commerce' => [
            'add_to_cart' => 'Added to basket',
            'remove_from_cart' => 'Removed from basket',
            'begin_checkout' => 'Checkouts started',
            'payment_attempted' => 'Payments attempted',
            'payment_successful' => 'Payments successful',
            'purchase' => 'Orders completed',
        ],
        'Pickup' => [
            'select_fulfilment' => 'Pickup chosen',
            'view_pickup_slots' => 'Pickup times viewed',
            'select_pickup_slot' => 'Pickup slot chosen',
        ],
    ];

    /** Sessions that reached each step, in order. */
    public const FUNNEL = [
        'view_item' => 'Viewed a product',
        'add_to_cart' => 'Added to basket',
        'begin_checkout' => 'Started checkout',
        'purchase' => 'Completed an order',
    ];

    #[Url(except: 30)]
    public int $days = 30;

    /** One store day (Y-m-d). Empty = use the range above. */
    #[Url(except: '')]
    public string $day = '';

    public function updatedDays(): void
    {
        $this->days = array_key_exists($this->days, self::RANGES) ? $this->days : 30;
        $this->day = '';
    }

    public function updatedDay(): void
    {
        $this->day = $this->validDay($this->day) ?? '';
    }

    public function showDay(string $day): void
    {
        $this->day = $this->validDay($day) ?? '';
    }

    public function clearDay(): void
    {
        $this->day = '';
    }

    public function render()
    {
        [$since, $until] = $this->window();
        $base = fn () => AnalyticsEvent::where('created_at', '>=', $since)->where('created_at', '<', $until);

        $counts = $base()->selectRaw('name, count(*) as total')->groupBy('name')->pluck('total', 'name');

        // "Pickup chosen" only counts pickup; delivery analytics are out of scope.
        $counts['select_fulfilment'] = $this->events('select_fulfilment', $since, $until)
            ->filter(fn ($e) => ($e->properties['method'] ?? null) === 'pickup')->count();

        $slotViews = $this->events('view_pickup_slots', $since, $until);
        $purchases = $this->events('purchase', $since, $until);
        $term = fn ($e) => mb_strtolower(trim((string) ($e->properties['search_term'] ?? '')));

        return view('livewire.admin.analytics-dashboard', [
            'counts' => $counts,
            'visitors' => $base()->where('name', 'page_view')->distinct()->count('session_id'),
            'funnel' => collect(self::FUNNEL)->map(fn ($label, $name) => [
                'label' => $label,
                'sessions' => $base()->where('name', $name)->distinct()->count('session_id'),
            ]),
            'revenue' => $purchases->sum(fn ($e) => (float) ($e->properties['value'] ?? 0)),
            'averageSlots' => $slotViews->isEmpty() ? null : round($slotViews->avg(fn ($e) => (int) ($e->properties['available_slots'] ?? 0)), 1),
            'topSearches' => $this->top($this->events('search', $since, $until), $term),
            'noResultSearches' => $this->top($this->events('search_no_results', $since, $until), $term),
            'topProducts' => $this->top($this->events('view_item', $since, $until), fn ($e) => $e->properties['items'][0]['item_name'] ?? null),
            'topPages' => $this->top($this->events('page_view', $since, $until), 'path'),
            'googleConnected' => Analytics::googleEnabled(),
            'daily' => $this->day || $this->days === 1 ? collect() : $this->daily($since, $until),
            'periodLabel' => $this->day
                ? CarbonImmutable::parse($this->day)->format('l j F Y')
                : self::RANGES[$this->days],
            'today' => CarbonImmutable::now($this->tz())->format('Y-m-d'),
        ])->layout('layouts.admin', ['header' => 'Analytics & Measurement']);
    }

    private function tz(): string
    {
        return config('shop.timezone', 'America/Moncton');
    }

    /**
     * Start (inclusive) and end (exclusive) of the period, in UTC.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function window(): array
    {
        if ($day = $this->validDay($this->day)) {
            $start = CarbonImmutable::parse($day, $this->tz())->startOfDay();

            return [$start->utc(), $start->addDay()->utc()];
        }

        $today = CarbonImmutable::now($this->tz())->startOfDay();

        return [$today->subDays($this->days - 1)->utc(), $today->addDay()->utc()];
    }

    private function validDay(string $day): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            return null;
        }

        $date = rescue(fn () => CarbonImmutable::createFromFormat('!Y-m-d', $day, $this->tz()), null, false);

        return $date && $date->format('Y-m-d') === $day && ! $date->isFuture() ? $day : null;
    }

    /**
     * Visitors, page views, baskets, orders and order value for each store
     * day in the period, newest first.
     *
     * @return Collection<string, array{visitors: int, page_views: int, add_to_cart: int, orders: int, revenue: float}>
     */
    private function daily(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $tz = $this->tz();
        $rows = [];

        for ($d = $to->setTimezone($tz)->subDay()->startOfDay(); $d->utc() >= $from; $d = $d->subDay()) {
            $rows[$d->format('Y-m-d')] = ['sessions' => [], 'page_views' => 0, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0];
        }

        AnalyticsEvent::whereIn('name', ['page_view', 'add_to_cart', 'purchase'])
            ->where('created_at', '>=', $from)->where('created_at', '<', $to)
            ->select(['id', 'name', 'session_id', 'properties', 'created_at'])
            ->lazyById(2000)
            ->each(function (AnalyticsEvent $e) use (&$rows, $tz) {
                $key = $e->created_at->copy()->setTimezone($tz)->format('Y-m-d');

                if (! isset($rows[$key])) {
                    return;
                }

                if ($e->name === 'page_view') {
                    $rows[$key]['page_views']++;
                    if ($e->session_id) {
                        $rows[$key]['sessions'][$e->session_id] = true;
                    }
                } elseif ($e->name === 'add_to_cart') {
                    $rows[$key]['add_to_cart']++;
                } else {
                    $rows[$key]['orders']++;
                    $rows[$key]['revenue'] += (float) ($e->properties['value'] ?? 0);
                }
            });

        return collect($rows)->map(fn ($row) => [
            'visitors' => count($row['sessions']),
            'page_views' => $row['page_views'],
            'add_to_cart' => $row['add_to_cart'],
            'orders' => $row['orders'],
            'revenue' => $row['revenue'],
        ]);
    }

    /**
     * @return Collection<int, AnalyticsEvent>
     */
    private function events(string $name, $since, $until): Collection
    {
        return AnalyticsEvent::where('name', $name)
            ->where('created_at', '>=', $since)->where('created_at', '<', $until)
            ->latest('id')->limit(5000)->get(['id', 'properties']);
    }

    /**
     * Most common values of one property: [value => count], top 10.
     *
     * @return Collection<string, int>
     */
    private function top(Collection $events, string|callable $key): Collection
    {
        return $events
            ->map(fn ($e) => is_callable($key) ? $key($e) : ($e->properties[$key] ?? null))
            ->filter(fn ($value) => filled($value))
            ->countBy()
            ->sortDesc()
            ->take(10);
    }
}
