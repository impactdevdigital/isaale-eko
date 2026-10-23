<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * Sign-in happens in the drawer; the auth middleware lands here and we
     * open the drawer on the home page. The intended URL is already saved.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('home', ['sign-in' => 1]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
