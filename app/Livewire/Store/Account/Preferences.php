<?php

namespace App\Livewire\Store\Account;

use App\Services\ConsentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Updates & preferences (Figma 06.09). Each switch is a marketing consent
 * record; nothing is on until the customer turns it on. Order and account
 * messages are always sent and aren't listed here as choices.
 */
#[Layout('layouts::store')]
#[Title('Updates & preferences')]
class Preferences extends Component
{
    /** Switch => consent channel. "email" is the general newsletter, also granted at checkout. */
    public const TOPICS = [
        'recipes' => 'email',
        'rewards' => 'email_rewards',
        'offers' => 'email_offers',
        'whatsappOrders' => 'whatsapp',
        'whatsappCommunity' => 'whatsapp_community',
    ];

    /** @var array<string, bool> */
    public array $choices = [];

    public bool $saved = false;

    public function mount(ConsentService $consents): void
    {
        $user = Auth::user();

        foreach (self::TOPICS as $key => $channel) {
            $this->choices[$key] = $consents->has($channel, $user->id, $user->email);
        }
    }

    public function updatedChoices(): void
    {
        $this->saved = false;
    }

    public function save(ConsentService $consents): void
    {
        $user = Auth::user();
        $canWhatsApp = $user->phone && $user->phone_verified_at;

        foreach (self::TOPICS as $key => $channel) {
            $on = (bool) ($this->choices[$key] ?? false);

            // WhatsApp needs a verified mobile number to send to.
            if (str_starts_with($channel, 'whatsapp') && ! $canWhatsApp) {
                $on = false;
                $this->choices[$key] = false;
            }

            $on
                ? $consents->grant($channel, $user->id, $user->email, 'account')
                : $consents->revoke($channel, $user->id, $user->email);
        }

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.store.account.preferences', ['user' => Auth::user()]);
    }
}
