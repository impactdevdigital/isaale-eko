<?php

namespace App\Livewire\Store\Account;

use App\Livewire\Store\Checkout;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\OneTimeCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Account management (Figma 06.08): name, sign-in email and phone, and
 * closing the account. Changing a verified email or phone takes a code to
 * the current one first, then a code to the new one; the old one keeps
 * working until the new one is verified.
 */
#[Layout('layouts::store')]
#[Title('Account management')]
class AccountManagement extends Component
{
    /** name, verify-current, enter-new, verify-new or close */
    public ?string $modal = null;

    public string $firstName = '';

    public string $lastName = '';

    /** Which sign-in detail is being changed: email or phone. */
    #[Locked]
    public ?string $changing = null;

    /** Set once the current email/phone is verified (or there was none to verify). */
    #[Locked]
    public bool $currentVerified = false;

    #[Locked]
    public ?string $codeDestination = null;

    public string $newValue = '';

    public string $code = '';

    public int $resendAt = 0;

    public ?string $notice = null;

    public function editName(): void
    {
        $name = str(Auth::user()->name)->squish();
        $this->firstName = (string) $name->before(' ');
        $this->lastName = $name->contains(' ') ? (string) $name->after(' ') : '';
        $this->resetErrorBag();
        $this->modal = 'name';
    }

    public function saveName(): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:60'],
            'lastName' => ['nullable', 'string', 'max:60'],
        ], ['firstName.required' => 'Enter your first name.']);

        Auth::user()->update(['name' => trim(trim($this->firstName).' '.trim($this->lastName))]);

        $this->modal = null;
        $this->notice = 'Your name was updated.';
    }

    /**
     * Start changing the email or phone. A verified one must be confirmed
     * first; adding one the account doesn't have goes straight to entry.
     */
    public function change(string $channel): void
    {
        $this->changing = $channel === 'phone' ? 'phone' : 'email';
        $this->reset('newValue', 'code', 'codeDestination', 'currentVerified');
        $this->resetErrorBag();

        $user = Auth::user();

        if ($user->{$this->changing.'_verified_at'} && $user->{$this->changing}) {
            $this->sendTo($user->{$this->changing});
            $this->modal = 'verify-current';

            return;
        }

        $this->currentVerified = true;
        $this->modal = 'enter-new';
    }

    public function verifyCurrent(): void
    {
        if (! $this->checkCode()) {
            return;
        }

        $this->currentVerified = true;
        $this->code = '';
        $this->modal = 'enter-new';
    }

    public function sendNewCode(): void
    {
        if (! $this->currentVerified) {
            return;
        }

        $this->validate(
            $this->changing === 'email'
                ? ['newValue' => ['required', 'email:rfc', 'max:255']]
                : ['newValue' => ['required', 'regex:/^\+?[0-9 ()-]{10,20}$/']],
            ['newValue.regex' => 'Enter a mobile number with area code.', 'newValue.required' => 'Enter your new '.($this->changing === 'email' ? 'email address.' : 'mobile number.')],
        );

        $destination = $this->changing === 'email' ? strtolower(trim($this->newValue)) : Checkout::normalisePhone($this->newValue);
        $user = Auth::user();

        if ($destination === $user->{$this->changing}) {
            $this->addError('newValue', 'That’s already your '.($this->changing === 'email' ? 'email address.' : 'phone number.'));

            return;
        }

        if (User::where($this->changing, $destination)->exists()) {
            $this->addError('newValue', 'Another account already uses this '.($this->changing === 'email' ? 'email address.' : 'number.'));

            return;
        }

        if (! $this->sendTo($destination)) {
            return;
        }

        $this->code = '';
        $this->modal = 'verify-new';
    }

    public function verifyNew(): void
    {
        if (! $this->currentVerified || ! $this->checkCode()) {
            return;
        }

        Auth::user()->forceFill([
            $this->changing => $this->codeDestination,
            $this->changing.'_verified_at' => now(),
        ])->save();

        $this->notice = $this->changing === 'email' ? 'Your email address was updated.' : 'Your phone number was updated.';
        $this->close();
    }

    public function resendCode(): void
    {
        if ($this->codeDestination) {
            $this->sendTo($this->codeDestination);
        }
    }

    public function confirmClose(): void
    {
        $this->resetErrorBag();
        $this->modal = 'close';
    }

    /**
     * Close the account. Orders stay on the store's books without the link
     * to the customer; addresses and points go with the account.
     */
    public function closeAccount(): void
    {
        $user = Auth::user();

        if ($this->hasOpenOrders($user)) {
            $this->addError('close', 'You have orders still in progress. Close your account once they’re collected or delivered, or cancel them first.');

            return;
        }

        Auth::logout();
        $user->delete();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('home');
    }

    public function close(): void
    {
        $this->modal = null;
        $this->reset('changing', 'currentVerified', 'codeDestination', 'newValue', 'code');
        $this->resetErrorBag();
    }

    private function hasOpenOrders(User $user): bool
    {
        return $user->orders()
            ->whereIn('status', [Order::STATUS_AWAITING_PAYMENT, Order::STATUS_PLACED, Order::STATUS_PAID])
            ->where(fn ($q) => $q->whereNull('fulfilment_status')->orWhere('fulfilment_status', '!=', Order::FULFILMENT_COMPLETED))
            ->exists();
    }

    private function sendTo(string $destination): bool
    {
        $key = 'account-code:'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError($this->modal === 'enter-new' ? 'newValue' : 'code', 'Too many codes requested. Try again in a few minutes.');

            return false;
        }

        RateLimiter::hit($key, 600);

        $otp = app(OneTimeCode::class);
        $otp->send($this->changing, $destination);

        $this->codeDestination = $destination;
        $this->resendAt = now()->addSeconds($otp->cooldown($this->changing, $destination))->timestamp;
        $this->resetErrorBag('code');

        return true;
    }

    private function checkCode(): bool
    {
        $this->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Enter all six digits.', 'code.required' => 'Enter all six digits.']);

        if (! app(OneTimeCode::class)->verify($this->changing, $this->codeDestination, $this->code)) {
            $this->addError('code', 'This code is invalid or has expired. Check the latest code, or request a new one when the timer ends.');

            return false;
        }

        return true;
    }

    /**
     * "+1 ••• ••• 789" / "va•••@gmail.com" for the code prompts.
     */
    public static function mask(string $channel, string $value): string
    {
        if ($channel === 'phone') {
            return substr($value, 0, 2).' ••• ••• '.substr($value, -3);
        }

        [$local, $domain] = explode('@', $value, 2) + [1 => ''];

        return substr($local, 0, 2).'•••@'.$domain;
    }

    public function render()
    {
        return view('livewire.store.account.account-management', ['user' => Auth::user()]);
    }
}
