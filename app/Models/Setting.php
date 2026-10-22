<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get a setting by key, checking memory cache, DB, and fallback config.
     * Setting::get('pickup_lead_minutes') -> 30
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', function () {
            return static::pluck('value', 'key')->all();
        });

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? static::defaultFor($key);
    }

    /**
     * Look up default value from config/shop_settings.php
     */
    public static function defaultFor(string $key): mixed
    {
        foreach (config('shop_settings', []) as $group) {
            if (isset($group['fields'][$key])) {
                return $group['fields'][$key]['default'] ?? null;
            }
        }

        return null;
    }

    /**
     * Update or create setting with audit change log
     */
    public static function put(string $key, mixed $value, ?int $userId = null): void
    {
        $old = static::get($key);

        if ($old === $value) {
            return;
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);

        SettingChange::create([
            'key' => $key,
            'old_value' => $old,
            'new_value' => $value,
            'user_id' => $userId,
        ]);

        Cache::forget('settings.all');
    }
}
