<?php

namespace App\Livewire\Store;

use App\Models\User;
use App\Services\Checkout\OneTimeCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Password-free sign-in drawer (Figma 06 "Create Account or Sign In"):
 * phone or email, a six-digit code, then a name for first-time shoppers.
 * Accounts are matched only on the identifier just verified, as at checkout.
 */
class SignIn extends Component
{
    public bool $open = false;

    /** contact, code or details */
    public string $step = 'contact';

    public string $channel = 'email';

    public string $email = '';

    public string $phone = '';

    public string $code = '';

    public string $name = '';

    #[Locked]
    public ?string $codeChannel = null;

    #[Locked]
    public ?string $codeDestination = null;

    /** Set once the code checks out but no account exists yet. */
    #[Locked]
    public bool $verified = false;

    public int $resendAt = 0;

    /** Where to go after signing in. */
    #[Locked]
    public string $returnTo = '/';

    public function mount(): void
    {
        $this->returnTo = url()->current();

        // Sent here by the auth middleware: open straight away and return to the page asked for.
        if (Auth::guest() && request()->boolean('sign-in')) {
            $this->open = true;
            $this->returnTo = session('url.intended', route('account'));
        }
    }

    #[On('sign-in-open')]
    public function show(): void
    {
        if (Auth::check()) {
            $this->redirectRoute('account');

            return;
        }

        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function useChannel(string $channel): void
    {
        $this->channel = $channel === 'phone' ? 'phone' : 'email';
        $this->resetErrorBag();
    }

    public function sendCode(): void
    {
        $this->validate(
            $this->channel === 'email'
                ? ['email' => ['required', 'email:rfc', 'max:255']]
                : ['phone' => ['required', 'regex:/^\+?[0-9 ()-]{10,20}$/']],
            ['phone.regex' => 'Enter a mobile number with area code.'],
        );

        $key = 'sign-in-code:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError($this->channel, 'Too many codes requested. Try again in a few minutes.');

            return;
        }

        RateLimiter::hit($key, 600);

        $destination = $this->channel === 'email'
            ? strtolower(trim($this->email))
            : Checkout::normalisePhone($this->phone);

        $otp = app(OneTimeCode::class);
        $otp->send($this->channel, $destination);

        $this->codeChannel = $this->channel;
        $this->codeDestination = $destination;
        $this->resendAt = now()->addSeconds($otp->cooldown($this->channel, $destination))->timestamp;
        $this->code = '';
        $this->step = 'code';
    }

    public function resendCode(): void
    {
        if (! $this->codeDestination) {
            return;
        }

        $otp = app(OneTimeCode::class);
        $otp->send($this->codeChannel, $this->codeDestination);
        $this->resendAt = now()->addSeconds($otp->cooldown($this->codeChannel, $this->codeDestination))->timestamp;
        $this->resetErrorBag('code');
    }

    public function back(): void
    {
        $this->step = 'contact';
        $this->code = '';
        $this->resetErrorBag();
    }

    public function verifyCode(): void
    {
        $this->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Enter all six digits.']);

        if (! app(OneTimeCode::class)->verify($this->codeChannel, $this->codeDestination, $this->code)) {
            $this->addError('code', 'This code is invalid or has expired. Check the latest code, or request a new one.');

            return;
        }

        $this->code = '';
        $user = User::where($this->column(), $this->codeDestination)->first();

        if ($user) {
            $this->signIn($user);

            return;
        }

        $this->verified = true;
        $this->step = 'details';
    }

    public function completeAccount(): void
    {
        if (! $this->verified) {
            return;
        }

        $this->validate(['name' => ['required', 'string', 'max:120']], ['name.required' => 'Enter your full name.']);

        $this->signIn(User::firstOrCreate([$this->column() => $this->codeDestination], ['name' => trim($this->name)]));
    }

    private function signIn(User $user): void
    {
        $column = $this->column();
        $user->forceFill([$column.'_verified_at' => $user->{$column.'_verified_at'} ?? now()])->save();

        Auth::login($user, remember: true);
        session()->regenerate();

        $this->redirect(session()->pull('url.intended', $this->returnTo));
    }

    private function column(): string
    {
        return $this->codeChannel === 'email' ? 'email' : 'phone';
    }

    public function render()
    {
        return view('livewire.store.sign-in');
    }
}
