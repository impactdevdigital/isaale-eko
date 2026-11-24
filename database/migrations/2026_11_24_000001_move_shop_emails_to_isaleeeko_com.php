<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The client's domain is isaleeeko.com (three Es). The 2026-11-16 migration
 * had moved the saved support email and the store admin login to the
 * two-E spelling (isaleeko.com), which is a different domain. This moves
 * them back. Only the support_email setting and the seeded admin account
 * are touched; staff accounts keep whatever address they were given.
 */
return new class extends Migration
{
    private const OLD_ADMIN = 'admin@isaleeko.com';

    private const NEW_ADMIN = 'admin@isaleeeko.com';

    public function up(): void
    {
        $setting = DB::table('settings')->where('key', 'support_email')->first();

        if ($setting && preg_match('/@(isaleeko|isaaleeko)\.(ca|com)"?$/i', (string) $setting->value)) {
            DB::table('settings')->where('id', $setting->id)->update([
                'value' => json_encode('support@isaleeeko.com'),
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
