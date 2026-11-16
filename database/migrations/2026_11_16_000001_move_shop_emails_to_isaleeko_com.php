<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Client request: the shop's domain is isaleeko.com. Moves the saved
 * support email setting and the store admin account off the old spellings
 * (isaleeeko.ca, isaleeeko.com, isaaleeko.com).
 */
return new class extends Migration
{
    private const OLD_ADMIN = 'admin@isaleeeko.ca';

    private const NEW_ADMIN = 'admin@isaleeko.com';

    public function up(): void
    {
        $setting = DB::table('settings')->where('key', 'support_email')->first();

        if ($setting && preg_match('/@(isaleeeko|isaaleeko)\.(ca|com)"?$/i', (string) $setting->value)) {
            DB::table('settings')->where('id', $setting->id)->update([
                'value' => json_encode('support@isaleeko.com'),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('users')->where('email', self::NEW_ADMIN)->doesntExist()) {
            DB::table('users')->where('email', self::OLD_ADMIN)->update(['email' => self::NEW_ADMIN, 'updated_at' => now()]);
        }

        Cache::forget('settings.all');
    }

    public function down(): void
    {
        if (DB::table('users')->where('email', self::OLD_ADMIN)->doesntExist()) {
            DB::table('users')->where('email', self::NEW_ADMIN)->update(['email' => self::OLD_ADMIN, 'updated_at' => now()]);
        }
    }
};
