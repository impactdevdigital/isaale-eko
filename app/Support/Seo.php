<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\StoreHour;
use Illuminate\Support\Str;

/**
 * Search and sharing tags for the storefront: description, canonical URL,
 * robots, Open Graph / Twitter cards and JSON-LD structured data.
 *
 * Pages set what they know (Seo::description(...), Seo::image(...)) while
 * rendering; the store layout prints the result in <head>.
 */
class Seo
{
    public const SITE_NAME = 'Ìsàlèè Èkó';

    public const DEFAULT_DESCRIPTION = 'African and Caribbean groceries in Saint John, NB. Shop pantry staples, spices, fresh produce and flavours from home online, then pick up in store.';

    /** Routes that should never appear in search results. */
    private const PRIVATE_ROUTES = [
        'cart', 'checkout', 'login', 'language', 'orders.*', 'account', 'account.*', 'admin.*',
    ];

    /** Default descriptions for pages that don't set their own. */
    private const ROUTE_DESCRIPTIONS = [
        'shop' => 'Shop African and Caribbean groceries online: rice and beans, sauces, spices, fresh produce, meat and fish, snacks and drinks. Pick up in Saint John, NB.',
        'kitchen.index' => 'Cook African and Caribbean recipes at home and add every ingredient to your basket in one tap.',
        'recipes.index' => 'Cook African and Caribbean recipes at home and add every ingredient to your basket in one tap.',
        'kitchen.pantry' => 'Tell us what is in your kitchen and find African and Caribbean recipes you can cook with it.',
        'kitchen.budget' => 'Affordable African and Caribbean recipes, with the ingredients ready to add to your basket.',
        'stories.index' => 'Food stories from across Africa and the Caribbean: the people, dishes and ingredients behind the flavours you love.',
        'updates.index' => 'News and updates from Ìsàlèè Èkó, Saint John’s African and Caribbean grocery store.',
        'community' => 'A space where food, culture and community come together through cultural celebrations, gatherings, exhibitions, food experiences and events.',
        'help' => 'Questions about orders, pickup, refunds or products? Find answers and contact the Ìsàlèè Èkó team in Saint John, NB.',
        'about' => 'Ìsàlèè Èkó brings the taste of home to Saint John, NB, with African and Caribbean groceries, recipes and community.',
        'privacy-policy' => 'How Ìsàlèè Èkó collects, uses and protects your personal information.',
        'refund-policy' => 'Returns, refunds and order issues at Ìsàlèè Èkó: what we cover and how to report a problem.',
    ];

    /** Query parameters that change what a page shows; the rest are dropped from canonical URLs. */
    private const CANONICAL_QUERY = ['category', 'country', 'page'];

    private ?string $description = null;

    private ?string $image = null;

    private string $type = 'website';

    private ?bool $noindex = null;

    private ?string $canonical = null;

    /** @var array<int, array<string, mixed>> */
    private array $schemas = [];

    public static function get(): self
    {
        return app(self::class);
    }

    public static function description(?string $text): self
    {
        $seo = self::get();
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
        $seo->description = $text !== '' ? Str::limit($text, 158, '…') : null;

        return $seo;
    }

    public static function image(?string $pathOrUrl): self
    {
        $seo = self::get();
        $seo->image = self::absolute($pathOrUrl);

        return $seo;
    }

    public static function type(string $type): self
    {
        $seo = self::get();
        $seo->type = $type;

        return $seo;
    }

    public static function noindex(bool $noindex = true): self
    {
        $seo = self::get();
        $seo->noindex = $noindex;

        return $seo;
    }

    public static function canonical(string $url): self
    {
        $seo = self::get();
        $seo->canonical = $url;

        return $seo;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function schema(array $schema): self
    {
        $seo = self::get();
        $seo->schemas[] = ['@context' => 'https://schema.org'] + $schema;

        return $seo;
    }

    // ---- read by the layout ----

    public function metaDescription(): string
    {
        $route = request()->route()?->getName();

        return $this->description
            ?? (self::ROUTE_DESCRIPTIONS[$route] ?? null)
            ?? self::DEFAULT_DESCRIPTION;
    }

    public function metaImage(): string
    {
        return $this->image ?? asset('images/og-image.png');
    }

    public function ogType(): string
    {
        return $this->type;
    }

    public function robots(): string
    {
        $private = $this->noindex ?? (request()->routeIs(...self::PRIVATE_ROUTES) || request()->filled('q'));

        return $private
            ? 'noindex, follow'
            : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    public function canonicalUrl(): string
    {
        if ($this->canonical) {
            return $this->canonical;
        }

        // /recipes is the same page as /kitchen.
        if (request()->routeIs('recipes.index')) {
            return route('kitchen.index');
        }

        $query = collect(request()->query())
            ->only(self::CANONICAL_QUERY)
            ->filter(fn ($value, $key) => is_string($value) && $value !== '' && ! ($key === 'page' && $value === '1'))
            ->sortKeys()
            ->all();

        return url()->current().($query ? '?'.http_build_query($query) : '');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function schemas(): array
    {
        $schemas = $this->schemas;

        if (request()->routeIs('home')) {
            array_unshift($schemas, $this->websiteSchema(), $this->storeSchema());
        }

        return $schemas;
    }

    /**
     * @param  array<int, array{0: string, 1: ?string}>  $crumbs  [name, url]
     */
    public static function breadcrumbs(array $crumbs): self
    {
        return self::schema([
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => $crumb[1] ?? null,
            ]))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::SITE_NAME,
            'alternateName' => 'Isalee Eko',
            'url' => url('/'),
            'inLanguage' => ['en-CA', 'fr-CA'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('shop').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * The store as a local business, for Google Maps and local results.
     *
     * @return array<string, mixed>
     */
    private function storeSchema(): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $hours = rescue(fn () => StoreHour::query()->where('is_closed', false)->whereNotNull('opens_at')->orderBy('weekday')->get()
            ->groupBy(fn (StoreHour $h) => substr($h->opens_at, 0, 5).'-'.substr($h->closes_at, 0, 5))
            ->map(fn ($group) => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $group->map(fn (StoreHour $h) => $days[$h->weekday] ?? null)->filter()->values()->all(),
                'opens' => substr($group->first()->opens_at, 0, 5),
                'closes' => substr($group->first()->closes_at, 0, 5),
            ])->values()->all(), [], false);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'GroceryStore',
            '@id' => url('/').'#store',
            'name' => self::SITE_NAME,
            'alternateName' => 'Isalee Eko',
            'description' => self::DEFAULT_DESCRIPTION,
            'url' => url('/'),
            'logo' => asset('images/logo-small.svg'),
            'image' => asset('images/og-image.png'),
            'telephone' => rescue(fn () => Setting::get('support_phone', '+1 506-333-7474'), '+1 506-333-7474', false),
            'email' => rescue(fn () => Setting::get('support_email', 'support@isaleeeko.com'), 'support@isaleeeko.com', false),
            'priceRange' => '$$',
            'currenciesAccepted' => 'CAD',
            'paymentAccepted' => 'Credit Card, Debit Card, Apple Pay, Google Pay, Interac e-Transfer',
            'servesCuisine' => ['African', 'Caribbean'],
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Saint John',
                'addressRegion' => 'NB',
                'addressCountry' => 'CA',
            ],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => 45.2753734, 'longitude' => -66.0789801],
            'hasMap' => 'https://www.google.com/maps/place/%C3%8Csal%C3%A8%C3%A8+%C3%88k%C3%B2/@45.2753734,-66.0789801,17z',
            'areaServed' => 'Saint John, New Brunswick',
            'openingHoursSpecification' => $hours ?: null,
        ]);
    }

    public static function absolute(?string $pathOrUrl): ?string
    {
        if (! $pathOrUrl) {
            return null;
        }

        return Str::startsWith($pathOrUrl, ['http://', 'https://', '//']) ? $pathOrUrl : asset(ltrim($pathOrUrl, '/'));
    }
}
