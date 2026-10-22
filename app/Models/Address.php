<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved delivery address. The recipient phone is a courier contact only;
 * it never changes how the account signs in.
 */
class Address extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string|null>
     */
    public function toSnapshot(): array
    {
        return $this->only(['recipient_name', 'phone', 'street', 'city', 'region', 'postal_code', 'country']);
    }
}
