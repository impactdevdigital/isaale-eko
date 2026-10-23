<?php

namespace App\Livewire\Store\Account;

use App\Livewire\Store\Checkout;
use App\Models\Address;
use App\Services\Checkout\DeliveryCheck;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Address book (Figma 06 "01 Address book"): saved Canadian delivery
 * addresses, one of them the default that checkout picks first.
 */
#[Layout('layouts::store')]
#[Title('Address book')]
class AddressBook extends Component
{
    /** form or delete */
    public ?string $modal = null;

    /** The address being edited or deleted; null when adding. */
    #[Locked]
    public ?int $editingId = null;

    public string $country = 'CA';

    public string $recipient = '';

    public string $recipientPhone = '';

    public string $street = '';

    public string $city = '';

    public string $postalCode = '';

    public bool $makeDefault = false;

    public function add(): void
    {
        $this->reset('editingId', 'recipient', 'recipientPhone', 'street', 'city', 'postalCode');
        $this->recipient = Auth::user()->name;
        $this->recipientPhone = Auth::user()->phone ?? '';
        $this->makeDefault = ! Auth::user()->addresses()->exists();
        $this->resetErrorBag();
        $this->modal = 'form';
    }

    public function edit(int $id): void
    {
        $address = $this->find($id);

        $this->editingId = $address->id;
        $this->country = $address->country;
        $this->recipient = $address->recipient_name;
        $this->recipientPhone = $address->phone;
        $this->street = $address->street;
        $this->city = $address->city;
        $this->postalCode = $address->postal_code;
        $this->makeDefault = $address->is_default;
        $this->resetErrorBag();
        $this->modal = 'form';
    }

    public function save(): void
    {
        $this->validate([
            'country' => ['required', 'in:CA'],
            'recipient' => ['required', 'string', 'max:120'],
            'recipientPhone' => ['required', 'regex:/^\+?[0-9 ()-]{10,20}$/'],
            'street' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'postalCode' => ['required', 'string', 'max:10'],
        ], ['recipientPhone.regex' => 'Enter a phone number with area code.']);

        $postal = DeliveryCheck::normalisePostalCode($this->postalCode);

        if (! $postal) {
            $this->addError('postalCode', 'Check the postal code — Canadian codes look like E2L 4L1.');

            return;
        }

        $user = Auth::user();
        $values = [
            'recipient_name' => trim($this->recipient),
            'phone' => Checkout::normalisePhone($this->recipientPhone),
            'street' => trim($this->street),
            'city' => trim($this->city),
            'region' => 'NB',
            'postal_code' => $postal,
            'country' => $this->country,
        ];

        DB::transaction(function () use ($user, $values) {
            $address = $this->editingId
                ? tap($this->find($this->editingId))->update($values)
                : $user->addresses()->create($values);

            // The first address is always the default.
            if ($this->makeDefault || ! $user->addresses()->where('is_default', true)->exists()) {
                $this->setDefault($address->id);
            }
        });

        $this->close();
    }

    public function makeDefaultAddress(int $id): void
    {
        $this->setDefault($this->find($id)->id);
    }

    public function confirmDelete(int $id): void
    {
        $this->editingId = $this->find($id)->id;
        $this->modal = 'delete';
    }

    public function delete(): void
    {
        $address = $this->find($this->editingId);
        $address->delete();

        // Keep a default while any address is left.
        if ($address->is_default && $next = Auth::user()->addresses()->latest()->first()) {
            $this->setDefault($next->id);
        }

        $this->close();
    }

    public function close(): void
    {
        $this->modal = null;
        $this->editingId = null;
        $this->resetErrorBag();
    }

    private function setDefault(int $id): void
    {
        Address::where('user_id', Auth::id())->update(['is_default' => false]);
        Address::whereKey($id)->update(['is_default' => true]);
    }

    private function find(?int $id): Address
    {
        return Auth::user()->addresses()->findOrFail($id);
    }

    public function render()
    {
        $delivery = app(DeliveryCheck::class);

        return view('livewire.store.account.address-book', [
            'addresses' => Auth::user()->addresses()->get()
                ->each(fn (Address $a) => $a->setAttribute('delivers', $delivery->check($a->toSnapshot())['status'] === 'ok')),
        ]);
    }
}
