{{--
    Save this basket. "card" sits on the cart page; "inline" on the order
    confirmation and order details pages (saves that order's items).
--}}
<div>
    @if ($hasItems || $savedAs)
        @if ($variant === 'card')
            <section id="save-basket" class="scroll-mt-6 rounded-sm border border-grayscale-40 p-5" aria-labelledby="save-basket-title">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-grayscale-20">
                        <x-store.icon name="bookmark" class="size-5" />
                    </span>
                    <div>
                        <h2 id="save-basket-title" class="text-[16px] font-extrabold">Save this basket</h2>
                        <p class="text-body-xs text-body-muted">Name it, then rebuild it any time at that day’s prices.</p>
                    </div>
                </div>

                @if ($savedAs)
                    <div class="mt-4 rounded-sm bg-stock-10 px-4 py-3 text-body-sm text-stock-50" role="status">
                        <p class="font-bold">Saved “{{ $savedAs }}”.</p>
                        <p class="mt-1">
                            <a href="{{ route('account.saved-baskets') }}" wire:navigate class="font-bold underline">View saved baskets</a>
                            <span aria-hidden="true">·</span>
                            <button type="button" wire:click="again" class="font-bold underline">Save another</button>
                        </p>
                    </div>
                @else
                    @auth
                        <form wire:submit="save" class="mt-4 flex items-start gap-2">
                            <div class="flex-1">
                                <label for="save-basket-name" class="sr-only">Basket name</label>
                                <input id="save-basket-name" type="text" wire:model="basketName" maxlength="60" placeholder="e.g. Weekly shop"
                                       @class(['h-11 w-full rounded-full border bg-surface px-4 text-[14px] focus:border-ink focus:outline-none', 'border-price-40' => $errors->has('basketName'), 'border-grayscale-40' => ! $errors->has('basketName')])>
                                @error('basketName')
                                    <p class="mt-1 text-body-xs text-price-40">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="h-11 shrink-0 rounded-full bg-ink px-5 text-[14px] font-bold text-white transition-colors hover:bg-grayscale-90">Save basket</button>
                        </form>
                    @else
                        <button type="button" x-data @click="$dispatch('sign-in-open')" class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-full border border-ink text-[14px] font-bold transition-colors hover:bg-ink hover:text-white">
                            <x-store.icon name="bookmark" class="size-4" /> Sign in to save this basket
                        </button>
                        <p class="mt-2 text-center text-body-xs text-body-muted">You can still check out as a guest.</p>
                    @endauth
                @endif
            </section>
        @else
            <div x-data="{ open: @js($errors->has('basketName')) }" class="flex flex-col items-start gap-2">
                @if ($savedAs)
                    <p class="text-body-sm text-stock-50" role="status">
                        <strong>Saved “{{ $savedAs }}”.</strong>
                        <a href="{{ route('account.saved-baskets') }}" wire:navigate class="font-bold underline">View saved baskets</a>
                    </p>
                @else
                    <button type="button" x-show="! open" @click="open = true; $nextTick(() => $refs.name.focus())"
                            class="inline-flex items-center gap-2 rounded-full border border-ink px-4 py-1.5 text-[13px] font-bold transition-colors hover:bg-ink hover:text-white">
                        <x-store.icon name="bookmark" class="size-4" /> Save as a basket
                    </button>
                    <form x-show="open" x-cloak wire:submit="save" class="flex flex-wrap items-center gap-2">
                        <label for="save-order-basket-name" class="sr-only">Basket name</label>
                        <input id="save-order-basket-name" x-ref="name" type="text" wire:model="basketName" maxlength="60"
                               class="h-9 w-[220px] rounded-full border border-grayscale-40 px-4 text-[13px] focus:border-ink focus:outline-none">
                        <button type="submit" class="h-9 rounded-full bg-ink px-4 text-[13px] font-bold text-white">Save</button>
                        <button type="button" @click="open = false" class="text-[12px] text-body-muted underline">Cancel</button>
                    </form>
                    @error('basketName')
                        <p class="text-body-xs text-price-40">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        @endif
    @endif
</div>
