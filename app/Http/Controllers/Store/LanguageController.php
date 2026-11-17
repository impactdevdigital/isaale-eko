<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The English / Français switch in the account menu and footer. Remembered
 * for the visit, and on the account of a signed-in shopper.
 */
class LanguageController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('locales.supported', [])), 404);

        $request->session()->put('locale', $locale);

        if ($user = $request->user()) {
            $user->forceFill(['language' => $locale])->save();
        }

        // Back to the same page, never to another site.
        $back = url()->previous();

        return redirect()->to(str_starts_with($back, url('/')) && ! str_contains($back, '/language/') ? $back : route('home'));
    }
}
