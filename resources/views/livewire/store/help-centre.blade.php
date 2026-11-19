@php
    $field = 'h-12 w-full rounded-sm border bg-surface px-4 text-[14px] placeholder:text-body-muted focus:border-ink focus:outline-none';
    $border = fn (string $name) => $errors->has($name) ? 'border-price-40' : 'border-grayscale-40';
@endphp

<div class="page-container py-8 md:py-12">
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex items-center gap-2 text-body-xs text-body-muted">
            <li><a href="{{ route('home') }}" class="transition-colors hover:text-ink">Home</a></li>
            <li aria-hidden="true">/</li>
            <li class="font-bold text-ink" aria-current="page">Help &amp; Contact</li>
        </ol>
    </nav>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="max-w-[720px]">
            <header class="mb-8 border-b border-grayscale-20 pb-6">
                <h1 class="text-[32px] font-bold tracking-tight text-ink md:text-[44px]">Report a Problem</h1>
                <p class="mt-2 text-body-sm text-body-muted">Something wrong with an order, or a question for us? Tell us here and our team will get back to you, usually within one working day.</p>
            </header>

            @if ($reference)
                <div class="rounded-lg border border-grayscale-30 p-6" role="status">
                    <span class="flex size-12 items-center justify-center rounded-full bg-brand-yellow-0"><x-store.icon name="check" class="size-6" /></span>
                    <h2 class="mt-4 text-[22px] font-bold text-ink">Thanks, we’ve got it</h2>
                    <p class="mt-2 text-body-sm text-body-muted">Your reference is <strong class="font-mono text-ink">{{ $reference }}</strong>. We’ll reply to {{ $email }}. Please keep any affected items until we’ve been in touch.</p>
                    <a href="{{ route('shop') }}" class="mt-5 inline-flex h-[42px] items-center rounded-full border border-ink px-5 text-[15px] font-bold">Keep shopping</a>
                </div>
            @else
                <form wire:submit="submit" novalidate class="flex flex-col gap-5">
                    <fieldset class="flex flex-col gap-2">
                        <legend class="mb-2 text-[15px] font-bold text-ink">What can we help with?</legend>
                        @foreach (\App\Livewire\Store\HelpCentre::TOPICS as $value => $label)
                            <label class="flex items-center gap-3 rounded-sm border border-grayscale-40 px-4 py-3 text-[14px] has-[:checked]:border-ink">
                                <input type="radio" wire:model.live="topic" value="{{ $value }}" class="size-4 accent-[#111111]">
                                {{ $label }}
                            </label>
                        @endforeach
                    </fieldset>

                    @if ($topic === 'safety')
                        <p class="rounded-sm bg-sale-10 px-4 py-3 text-body-sm text-price-40" role="note"><strong>If someone is having an allergic reaction, call 911 first.</strong> Keep the product and its packaging so we can investigate.</p>
                    @endif

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="help-name" class="mb-1.5 block text-[14px] font-bold">Full name</label>
                            <input id="help-name" type="text" wire:model.blur="name" autocomplete="name" class="{{ $field }} {{ $border('name') }}" @error('name') aria-invalid="true" aria-describedby="help-name-error" @enderror>
                            @error('name') <p id="help-name-error" class="mt-1 text-body-xs font-bold text-price-40">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="help-email" class="mb-1.5 block text-[14px] font-bold">Email</label>
                            <input id="help-email" type="email" wire:model.blur="email" autocomplete="email" class="{{ $field }} {{ $border('email') }}" @error('email') aria-invalid="true" aria-describedby="help-email-error" @enderror>
                            @error('email') <p id="help-email-error" class="mt-1 text-body-xs font-bold text-price-40">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="help-phone" class="mb-1.5 block text-[14px] font-bold">Phone <span class="font-normal text-body-muted">(optional)</span></label>
                            <input id="help-phone" type="tel" wire:model.blur="phone" autocomplete="tel" inputmode="tel" placeholder="(506) 555-0123" class="{{ $field }} border-grayscale-40">
                        </div>
                        <div>
                            <label for="help-order" class="mb-1.5 block text-[14px] font-bold">Order number <span class="font-normal text-body-muted">(if you have one)</span></label>
                            <div class="flex gap-2">
                                <input id="help-order" type="text" wire:model="orderNumber" placeholder="ISA-XXXXXX" class="{{ $field }} {{ $border('orderNumber') }}" @error('orderNumber') aria-invalid="true" aria-describedby="help-order-error" @enderror>
                                @if ($topic !== 'enquiry')
                                    <button type="button" wire:click="findOrder" class="h-12 shrink-0 rounded-sm border border-ink px-4 text-[14px] font-bold hover:bg-ink hover:text-white">Find</button>
                                @endif
                            </div>
                            @error('orderNumber') <p id="help-order-error" class="mt-1 text-body-xs font-bold text-price-40">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if ($order && $topic !== 'enquiry')
                        <fieldset class="rounded-sm border border-grayscale-40">
                            <legend class="mx-4 px-1 text-[14px] font-bold">Which items are affected? <span class="font-normal text-body-muted">Order {{ $order->number }}</span></legend>
                            <ul class="divide-y divide-grayscale-30">
                                @foreach ($order->items as $item)
                                    <li wire:key="help-item-{{ $item->id }}" class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center">
                                        <label class="flex flex-1 items-center gap-3 text-[14px]">
                                            <input type="checkbox" wire:model.live="items.{{ $item->id }}.selected" class="size-4 accent-[#111111]">
                                            <span><strong>{{ $item->name }}</strong>@if ($item->variation_name) <span class="text-body-muted">· {{ $item->variation_name }}</span>@endif</span>
                                        </label>
                                        @if ($items[$item->id]['selected'] ?? false)
                                            <div class="flex gap-2 pl-7 sm:pl-0">
                                                <label class="sr-only" for="help-qty-{{ $item->id }}">Quantity affected</label>
                                                <input id="help-qty-{{ $item->id }}" type="number" min="1" max="{{ $item->quantity }}" wire:model="items.{{ $item->id }}.quantity" class="h-10 w-16 rounded-sm border border-grayscale-40 px-2 text-[14px]">
                                                <label class="sr-only" for="help-type-{{ $item->id }}">What’s wrong</label>
                                                <select id="help-type-{{ $item->id }}" wire:model="items.{{ $item->id }}.issue_type" class="h-10 rounded-sm border border-grayscale-40 px-2 text-[13px]">
                                                    @foreach ($issueTypes as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </fieldset>
                    @endif

                    <div>
                        <label for="help-description" class="mb-1.5 block text-[14px] font-bold">Tell us what happened</label>
                        <textarea id="help-description" wire:model.blur="description" rows="5" maxlength="3000" class="w-full rounded-sm border bg-surface px-4 py-3 text-[14px] focus:border-ink focus:outline-none {{ $border('description') }}" @error('description') aria-invalid="true" aria-describedby="help-description-error" @enderror></textarea>
                        @error('description') <p id="help-description-error" class="mt-1 text-body-xs font-bold text-price-40">{{ $message }}</p> @enderror
                    </div>

                    <div class="hidden" aria-hidden="true">
                        <label for="help-website">Website</label>
                        <input id="help-website" type="text" wire:model="website" tabindex="-1" autocomplete="off">
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="flex h-12 items-center justify-center rounded-full bg-ink text-[15px] font-bold text-white disabled:opacity-60 sm:w-60">
                        <span wire:loading.remove wire:target="submit">Send</span>
                        <span wire:loading wire:target="submit">Sending…</span>
                    </button>
                    <p class="text-body-xs text-body-muted">See our <a href="{{ route('refund-policy') }}" class="font-bold text-ink underline">Refund Policy</a> for report windows and how we resolve problems.</p>
                </form>
            @endif
        </div>

        <aside class="flex flex-col gap-4 lg:pt-2">
            <div class="rounded-lg bg-[#F6F6F6] p-6">
                <h2 class="text-[18px] font-bold text-ink">Contact us</h2>
                <ul class="mt-3 flex flex-col gap-2 text-body-sm">
                    @if ($supportEmail)
                        <li><x-store.icon name="mail" class="mr-1 inline size-4" /> <a href="mailto:{{ $supportEmail }}" class="font-bold underline">{{ $supportEmail }}</a></li>
                    @endif
                    @if ($supportPhone)
                        <li><x-store.icon name="phone" class="mr-1 inline size-4" /> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}" class="font-bold underline">{{ $supportPhone }}</a></li>
                    @endif
                    <li class="text-body-muted">{{ config('shop.pickup.location', 'Saint John, NB, Canada') }}</li>
                </ul>
            </div>
            <div class="rounded-lg border border-grayscale-30 p-6 text-body-sm text-body-muted">
                <h2 class="text-[15px] font-bold text-ink">Good to know</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Report food problems within 24 hours of pickup or delivery.</li>
                    <li>A photo of the item helps; we may ask for one by email.</li>
                    <li>Signed in? Your past orders are under <a href="{{ route('account.orders') }}" class="font-bold text-ink underline">My orders</a>.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
