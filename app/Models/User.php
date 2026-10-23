<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'is_admin', 'role', 'is_active', 'email_verified_at', 'phone_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_MANAGER = 'manager';

    public const ROLE_ASSOCIATE = 'associate';

    public const ROLE_CUSTOMER = 'customer';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin && (bool) $this->is_active;
    }

    public function isManager(): bool
    {
        return (bool) $this->is_admin && (bool) $this->is_active && in_array($this->role, [self::ROLE_MANAGER, 'owner'], true);
    }

    public function isAssociate(): bool
    {
        return (bool) $this->is_admin && (bool) $this->is_active && $this->role === self::ROLE_ASSOCIATE;
    }

    public function scopeStaff($query)
    {
        return $query->where('is_admin', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->latest();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlist_items')->withTimestamps()->orderByPivot('created_at', 'desc');
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class)->latest()->latest('id');
    }

    public function pointsBalance(): int
    {
        return (int) $this->pointTransactions()->sum('points');
    }

    /**
     * The verified contact the shopper signs in with, e.g. for "Verified email".
     *
     * @return array{channel: string, value: string}|null
     */
    public function signInContact(): ?array
    {
        return match (true) {
            $this->email_verified_at !== null => ['channel' => 'email', 'value' => $this->email],
            $this->phone_verified_at !== null => ['channel' => 'phone', 'value' => $this->phone],
            default => null,
        };
    }
}
