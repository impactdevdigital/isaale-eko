<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasTranslations;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETIRED = 'retired';

    protected $guarded = [];

    protected $casts = [
        'duration_minutes' => 'integer',
        'base_servings' => 'integer',
        'prep_minutes' => 'integer',
        'diet_tags' => 'array',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'approved_at' => 'datetime',
        'review_due_at' => 'date',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function mealCombos(): BelongsToMany
    {
        return $this->belongsToMany(MealCombo::class, 'meal_combo_recipes')->withPivot('sort_order');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Resolves the primary title (falling back to legacy name if title is empty).
     */
    public function getTitleAttribute(?string $value): string
    {
        return $value ?: ($this->attributes['name'] ?? '');
    }

    /**
     * Resolves the intro text (falling back to tagline if intro is empty).
     */
    public function getIntroAttribute(?string $value): ?string
    {
        return $value ?: ($this->attributes['tagline'] ?? null);
    }

    /**
     * "50 min" / "1 hr 10 min"
     */
    public function durationLabel(): ?string
    {
        $minutes = $this->duration_minutes ?? $this->prep_minutes;
        if (! $minutes) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return trim(($hours ? "{$hours} hr " : '').($remainder ? "{$remainder} min" : ''));
    }
}
