<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Whether customers can choose Delivery at checkout. Staff switch it on and off
 * from Admin > Payment Methods; until they do, SHOP_DELIVERY_ENABLED decides.
 */
class Delivery
{
    public const SETTING = 'delivery_enabled';

    public static function enabled(): bool
    {
        return (bool) Setting::get(self::SETTING, (bool) config('shop.delivery.enabled'));
    }

    public static function setEnabled(bool $enabled, ?int $userId = null): void
    {
        Setting::put(self::SETTING, $enabled, $userId);
    }
}
