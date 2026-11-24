<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

// The client's domain is isaleeeko.com: three Es. isaleeko.com (two Es) is someone else's.
test('customer pages show isaleeeko.com and never a wrong domain spelling', function () {
    foreach (['/privacy-policy', '/refund-policy', '/about', '/help'] as $page) {
        $html = $this->get($page)->assertOk()->getContent();

        expect($html)->not->toMatch('/isaleeko|isaaleeko|isaleeeko\.ca/i');
    }

    $this->get('/privacy-policy')->assertSee('support@isaleeeko.com');
});

test('no file in the codebase uses the two-E domain', function () {
    $hits = collect(['app', 'config', 'resources', 'routes', 'database/seeders', 'public/docs', 'docs', 'deploy'])
        ->flatMap(fn ($dir) => File::allFiles(base_path($dir)))
        ->filter(fn ($file) => preg_match('/\bisaleeko\.(com|ca)\b/i', $file->getContents()))
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($hits)->toBe([]);
});

test('the domain migration moves the saved support email and the admin account to isaleeeko.com', function () {
    DB::table('settings')->updateOrInsert(['key' => 'support_email'], ['value' => json_encode('support@isaleeko.com')]);
    $admin = User::factory()->create(['email' => 'admin@isaleeko.com', 'is_admin' => true]);
    Cache::forget('settings.all');

    (require database_path('migrations/2026_11_24_000001_move_shop_emails_to_isaleeeko_com.php'))->up();

    expect(Setting::get('support_email'))->toBe('support@isaleeeko.com')
        ->and($admin->fresh()->email)->toBe('admin@isaleeeko.com');
});
