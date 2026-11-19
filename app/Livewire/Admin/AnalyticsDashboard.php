<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsEvent;
use App\Services\Analytics;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Analytics & Measurement: what shoppers did on the website, from the
 * analytics_events log. Discovery, commerce and pickup events, the
 * shopping funnel, and the top searches and products. When Google
 * Analytics is connected the same events go there too.
 */
class AnalyticsDashboard extends Component
{
    public const RANGES = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

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

    public function updatedDays(): void
    {
        $this->days = array_key_exists($this->days, self::RANGES) ? $this->days : 30;
    }

    public function render()
    {
        $since = now()->subDays($this->days);
        $base = fn () => AnalyticsEvent::where('created_at', '>=', $since);

        $counts = $base()->selectRaw('name, count(*) as total')->groupBy('name')->pluck('total', 'name');

        // "Pickup chosen" only counts pickup; delivery analytics are out of scope.
        $counts['select_fulfilment'] = $this->events('select_fulfilment', $since)
            ->filter(fn ($e) => ($e->properties['method'] ?? null) === 'pickup')->count();

        $slotViews = $this->events('view_pickup_slots', $since);
        $purchases = $this->events('purchase', $since);

        return view('livewire.admin.analytics-dashboard', [
            'counts' => $counts,
            'visitors' => $base()->where('name', 'page_view')->distinct()->count('session_id'),
            'funnel' => collect(self::FUNNEL)->map(fn ($label, $name) => [
                'label' => $label,
                'sessions' => $base()->where('name', $name)->distinct()->count('session_id'),
            ]),
            'revenue' => $purchases->sum(fn ($e) => (float) ($e->properties['value'] ?? 0)),
            'averageSlots' => $slotViews->isEmpty() ? null : round($slotViews->avg(fn ($e) => (int) ($e->properties['available_slots'] ?? 0)), 1),
            'topSearches' => $this->top($this->events('search', $since), $term = fn ($e) => mb_strtolower(trim((string) ($e->properties['search_term'] ?? '')))),
            'noResultSearches' => $this->top($this->events('search_no_results', $since), $term),
            'topProducts' => $this->top($this->events('view_item', $since), fn ($e) => $e->properties['items'][0]['item_name'] ?? null),
            'topPages' => $this->top($this->events('page_view', $since), 'path'),
            'googleConnected' => Analytics::googleEnabled(),
        ])->layout('layouts.admin', ['header' => 'Analytics & Measurement']);
    }

    /**
     * @return Collection<int, AnalyticsEvent>
     */
    private function events(string $name, $since): Collection
    {
        return AnalyticsEvent::where('name', $name)->where('created_at', '>=', $since)
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
