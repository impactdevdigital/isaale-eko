<div>
    <x-store.account-shell active="addresses" :crumbs="['Your account' => route('account'), 'Address book' => null]">
        <h1 class="text-h2-mobile md:text-h2">Address Book</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Save delivery addresses for a quicker checkout.</p>

        @if ($addresses->isEmpty())
            <div class="mt-16 flex flex-col items-center text-center">
                <span class="flex size-14 items-center justify-center rounded-full border-2 border-grayscale-40 text-grayscale-50">
                    <x-store.icon name="check" class="size-6" />
                </span>
                <p class="mt-4 text-[16px] font-bold text-grayscale-80">No address added yet</p>
                <p class="mt-1 text-body-sm text-body-muted">Add your delivery address so it’s ready when you check out.</p>
                <button type="button" wire:click="add" class="mt-6 flex h-[42px] items-center gap-2 rounded-full bg-ink px-6 text-[15px] font-bold text-white">
                    <x-store.icon name="plus" class="size-4" /> Add address
                </button>
            </div>
        @else
            <button type="button" wire:click="add" class="mt-8 flex h-[42px] items-center gap-2 rounded-full bg-ink px-6 text-[15px] font-bold text-white">
                <x-store.icon name="plus" class="size-4" /> Add address
            </button>

            <ul class="mt-4 flex flex-col gap-4">
                @foreach ($addresses as $address)
                    <li wire:key="address-{{ $address->id }}"
                        @class([
                            'rounded-sm p-[2px]',
                            'bg-[repeating-linear-gradient(135deg,var(--color-price-40)_0_14px,transparent_14px_22px,var(--color-brand-blue)_22px_36px,transparent_36px_44px)]' => $address->is_default,
                            'bg-grayscale-40' => ! $address->is_default,
                        ])>
                        <div class="rounded-[3px] bg-surface">
                            <dl class="grid gap-4 px-5 py-5 text-[15px] md:grid-cols-[180px_1fr_140px]">
                                <div>
                                    <dt class="text-[13px] text-body-muted">Full Name</dt>
                                    <dd class="mt-1.5">{{ $address->recipient_name }}</dd>
                                    <dd class="mt-0.5 text-[13px] text-body-muted">{{ $address->phone }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[13px] text-body-muted">Delivery Address</dt>
                                    <dd class="mt-1.5">{{ $address->street }}, {{ $address->city }}, Canada</dd>
                                </div>
                                <div>
                                    <dt class="text-[13px] text-body-muted">Postcode</dt>
                                    <dd class="mt-1.5">{{ $address->postal_code }}</dd>
                                </div>
                            </dl>
                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-grayscale-30 px-5 py-3 text-[13px]">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($address->is_default)
                                        <span class="rounded-[4px] bg-grayscale-10 px-2 py-0.5 font-bold">Default</span>
                                    @else
                                        <button type="button" wire:click="makeDefaultAddress({{ $address->id }})" class="font-bold underline">Set as default</button>
                                    @endif
                                    @unless ($address->delivers)
                                        <span class="rounded-[4px] bg-sale-10 px-2 py-0.5 text-price-40">Outside our delivery area — pickup only</span>
                                    @endunless
                                </div>
                                <div class="flex gap-4 font-bold">
                                    <button type="button" wire:click="edit({{ $address->id }})">Edit address</button>
                                    <button type="button" wire:click="confirmDelete({{ $address->id }})">Delete</button>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-store.account-shell>

    @if ($modal === 'form')
        <x-store.modal :title="$editingId ? 'Edit address' : 'Add address'" subtitle="Enter a Canadian delivery address. All fields are required unless marked optional." width="max-w-[680px]">
            <form wire:submit="save" class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="field-country" class="mb-2 block text-[11px] font-medium uppercase tracking-[0.3px] text-grayscale-80">Country / Region</label>
                    <select id="field-country" wire:model="country" class="h-11 w-full rounded-sm border border-grayscale-30 bg-surface px-4 text-[16px]">
                        <option value="CA">Canada</option>
                    </select>
                </div>
                <x-store.field model="recipient" label="Recipient / contact name" autocomplete="name" />
                <x-store.field model="recipientPhone" label="Phone number · courier contact" type="tel" autocomplete="tel" class="sm:col-span-2" />
                <x-store.field model="street" label="Street address" autocomplete="street-address" class="sm:col-span-2" />
                <x-store.field model="city" label="City" autocomplete="address-level2" />
                <x-store.field model="postalCode" label="Postal code" autocomplete="postal-code" placeholder="E2L 4L1" />
                <div class="sm:col-span-2">
                    <x-store.check model="makeDefault" label="Use as my default delivery address" />
                </div>
                <div class="flex justify-end gap-3 border-t border-grayscale-30 pt-5 sm:col-span-2">
                    <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                    <button type="submit" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">{{ $editingId ? 'Save changes' : 'Add Address' }}</button>
                </div>
            </form>
        </x-store.modal>
    @endif

    @if ($modal === 'delete')
        <x-store.modal title="Delete this address?" subtitle="It will be removed from your address book. Past orders keep the address they were delivered to." width="max-w-[480px]">
            <div class="flex justify-end gap-3">
                <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                <button type="button" wire:click="delete" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">Delete address</button>
            </div>
        </x-store.modal>
    @endif
</div>
