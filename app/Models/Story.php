<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * A Food Story or a Brand Update. The body is Markdown written by staff in admin.
 */
class Story extends Model
{
    public const TYPE_FOOD = 'food_story';

    public const TYPE_UPDATE = 'brand_update';

    public const TYPES = [self::TYPE_FOOD => 'Food Story', self::TYPE_UPDATE => 'Brand Update'];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $guarded = [];

    protected $casts = [
        'is_trending' => 'boolean',
        'allow_comments' => 'boolean',
        'read_minutes' => 'integer',
        'published_at' => 'datetime',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(StoryComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', StoryComment::STATUS_APPROVED)->oldest();
    }

    /**
     * Live on the storefront: published and not scheduled for later.
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at?->isPast();
    }

    public function isFoodStory(): bool
    {
        return $this->type === self::TYPE_FOOD;
    }

    public function url(): string
    {
        return $this->isFoodStory() ? route('stories.show', $this) : route('updates.show', $this);
    }

    public function readMinutes(): int
    {
        return $this->read_minutes ?: max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }

    public function dateLabel(): string
    {
        return ($this->published_at ?? $this->created_at)->setTimezone(config('shop.timezone'))->format('j F Y');
    }

    /**
     * Staff-written Markdown as HTML. Raw HTML in the body is escaped and unsafe links are dropped.
     */
    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown((string) $this->body, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]));
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return Str::startsWith($this->cover_image, ['http://', 'https://']) ? $this->cover_image : asset($this->cover_image);
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'story';
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
