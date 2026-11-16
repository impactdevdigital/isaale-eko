<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('customer pages show isaleeko.com and never an old domain spelling', function () {
    foreach (['/privacy-policy', '/refund-policy'] as $page) {
        $html = $this->get($page)->assertOk()->assertSee('support@isaleeko.com')->getContent();

        expect($html)->not->toMatch('/isaleeeko|isaaleeko|isaleeko\.ca/i');
    }
});

test('the domain migration moves the saved support email and the admin account to isaleeko.com', function () {
    DB::table('settings')->updateOrInsert(['key' => 'support_email'], ['value' => json_encode('support@isaleeeko.com')]);
    $admin = User::factory()->create(['email' => 'admin@isaleeeko.ca', 'is_admin' => true]);
    Cache::forget('settings.all');

    (require database_path('migrations/2026_11_16_000001_move_shop_emails_to_isaleeko_com.php'))->up();

    expect(Setting::get('support_email'))->toBe('support@isaleeko.com')
        ->and($admin->fresh()->email)->toBe('admin@isaleeko.com');
});
