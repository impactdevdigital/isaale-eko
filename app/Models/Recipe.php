<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    protected $guarded = [];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /**
     * "50 min" / "1 hr 10 min"
     */
    public function durationLabel(): ?string
    {
        if (! $this->duration_minutes) {
            return null;
        }

        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return trim(($hours ? "{$hours} hr " : '').($minutes ? "{$minutes} min" : ''));
    }
}
