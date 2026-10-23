<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function flagUrl(): ?string
    {
        return $this->code ? asset('images/flags/'.strtolower($this->code).'.svg') : null;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
