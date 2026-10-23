<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Admin Authentication Portal.
 * Handles secure email/password credential sign-in for store managers and staff,
 * enforcing rate-limiting, admin role verification, and intended destination routing.
 */
class AdminLogin extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
    }

    protected function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    public function login()
    {
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many sign-in attempts. Please try again in {$seconds} seconds.");

            return;
        }

        $credentials = [
            'email' => strtolower(trim($this->email)),
            'password' => $this->password,
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->password = '';
            $this->addError('email', 'The provided credentials do not match our staff records.');

            return;
        }

        if (! Auth::user()->isAdmin()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            RateLimiter::hit($throttleKey, 60);
            $this->password = '';
            $this->addError('email', 'Access denied. This account does not have store operations privileges.');

            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.admin-login')
            ->layout('layouts.guest', ['title' => 'Staff Sign-In — Operations Portal']);
    }
}
