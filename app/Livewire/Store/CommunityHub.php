<?php

namespace App\Livewire\Store;

use App\Services\ConsentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Community Hub "coming soon" teaser (Figma 07.07, node 1180:71996):
 * launch-updates email signup and the WhatsApp community link.
 */
#[Layout('layouts::store')]
#[Title('Community Hub')]
class CommunityHub extends Component
{
    public string $email = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate(
            ['email' => ['required', 'email:rfc', 'max:255']],
            ['email.required' => 'Enter your email address.', 'email.email' => 'Enter a valid email address.'],
        );

        $key = 'community-subscribe:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Try again in a few minutes.');

            return;
        }

        RateLimiter::hit($key, 600);

        app(ConsentService::class)->grant('email', Auth::id(), strtolower(trim($this->email)), 'community_hub');

        $this->reset('email');
        $this->subscribed = true;
    }

    public function render()
    {
        return view('livewire.store.community-hub', [
            'whatsappUrl' => config('shop.whatsapp_community_url'),
        ]);
    }
}
