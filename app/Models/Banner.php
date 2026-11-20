<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A homepage banner slide. Staff edit them in Admin → Homepage Banners.
 */
class Banner extends Model
{
    public const PLACEMENTS = [
        'hero' => 'Top of homepage (large)',
        'promo' => 'Middle of homepage',
    ];

    /**
     * Text and button colours: [label, text classes, button classes].
     * Kept here so Tailwind builds these classes.
     */
    public const STYLES = [
        'light' => ['White text, white button', 'text-white', 'bg-surface text-ink hover:bg-brand-yellow-50'],
        'light-yellow' => ['White text, yellow button', 'text-white', 'bg-brand-yellow text-ink hover:bg-brand-yellow-50'],
        'dark' => ['Dark text, black button', 'text-ink', 'bg-ink text-white hover:bg-grayscale-90'],
    ];

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /** Switched on, and within its start and end dates (if any). */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function isLive(): bool
    {
        return $this->is_active
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->ends_at || $this->ends_at->isFuture());
    }

    /** "Live", "Scheduled", "Ended" or "Hidden", for the admin list. */
    public function stateLabel(): string
    {
        return match (true) {
            ! $this->is_active => 'Hidden',
            $this->ends_at && ! $this->ends_at->isFuture() => 'Ended',
            $this->starts_at && $this->starts_at->isFuture() => 'Scheduled',
            default => 'Live',
        };
    }

    public function imageUrl(): string
    {
        return Str::startsWith($this->image, ['http://', 'https://']) ? $this->image : asset($this->image);
    }

    /** The button link: a full address, or a page on this site such as /shop. */
    public function href(): ?string
    {
        if (blank($this->cta_url)) {
            return null;
        }

        return Str::startsWith($this->cta_url, ['http://', 'https://']) ? $this->cta_url : url($this->cta_url);
    }

    /**
     * The slide in the shape the banner carousel uses, in the shopper's language.
     *
     * @return array{eyebrow: ?string, title: string, body: ?string, cta: ?string, url: ?string, image: string, tint: string, text: string, button: string}
     */
    public function toSlide(): array
    {
        $fr = app()->getLocale() === 'fr';
        $pick = fn (string $field) => ($fr ? $this->{$field.'_fr'} : null) ?: $this->{$field};

        return [
            'eyebrow' => $pick('eyebrow'),
            'title' => $pick('title'),
            'body' => $pick('body'),
            'cta' => $pick('cta_label'),
            'url' => $this->href(),
            'image' => $this->imageUrl(),
            'tint' => $this->tint ?: 'bg-trend-60',
            'text' => (self::STYLES[$this->style] ?? self::STYLES['light'])[1],
            'button' => (self::STYLES[$this->style] ?? self::STYLES['light'])[2],
        ];
    }
}
