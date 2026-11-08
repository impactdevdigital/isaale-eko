<?php

namespace App\Livewire\Store\Stories\Concerns;

use App\Services\ConsentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * "Get food stories in your inbox" box in the stories sidebar (Figma 07.03/07.04).
 * Records email consent; the email tool picks subscribers up from the consents table.
 */
trait SubscribesToStories
{
    public string $subscribeName = '';

    public string $subscribeEmail = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate(
            ['subscribeName' => ['required', 'string', 'max:120'], 'subscribeEmail' => ['required', 'email:rfc', 'max:255']],
            ['subscribeName.required' => 'Enter your name.', 'subscribeEmail.required' => 'Enter your email address.', 'subscribeEmail.email' => 'Enter a valid email address.'],
        );

        $key = 'stories-subscribe:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('subscribeEmail', 'Too many attempts. Try again in a few minutes.');

            return;
        }

        RateLimiter::hit($key, 600);

        app(ConsentService::class)->grant('email', Auth::id(), strtolower(trim($this->subscribeEmail)), 'food_stories');

        $this->reset('subscribeName', 'subscribeEmail');
        $this->subscribed = true;
    }
}
