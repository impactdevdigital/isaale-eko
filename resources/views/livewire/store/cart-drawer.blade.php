<div
    x-data="{ open: false }"
    @cart-open.window="open = true"
    @keydown.escape.window="open = false"
>
    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/20" @click="open = false"></div>

    <aside
        x-show="open"
        x-cloak
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-end="translate-x-full"
        x-trap.noscroll="open"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-title"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[433px] flex-col bg-surface"
    >
        <button type="button" @click="open = false" class="absolute right-4 top-4 flex size-10 items-center justify-center rounded-full bg-surface shadow-[0_0_16px_rgb(0_0_0/0.15)]" aria-label="Close basket">
            <x-store.icon name="close" />
        </button>

        <div class="flex-1 overflow-y-auto px-6 pt-[52px] md:px-8">
            <p class="pb-3 text-caption font-extrabold uppercase text-body-muted">Your basket</p>
            <h2 id="cart-title" class="pb-6 text-h2-mobile capitalize md:text-h2">{{ $lines->isNotEmpty() ? 'Good food is on the way.' : 'Your basket is hungry' }}</h2>

            @forelse ($lines as $id => $line)
                @php($product = $line->variation->product)
                <div wire:key="line-{{ $id }}" @class(['flex flex-col gap-3 py-4', 'border-t border-grayscale-40' => ! $loop->first, 'pt-0' => $loop->first])>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="toggle({{ $id }})" role="checkbox" aria-checked="{{ $line->selected ? 'true' : 'false' }}" aria-label="Include {{ $product->name }} in checkout"
                                @class(['flex size-5 shrink-0 items-center justify-center rounded-sm text-white', 'bg-ink' => $line->selected, 'border border-grayscale-50 bg-surface' => ! $line->selected])>
                            @if ($line->selected)
                                <x-store.icon name="check" />
                            @endif
                        </button>

                        <img src="{{ asset($product->image) }}" alt="" class="size-[72px] shrink-0 rounded-sm object-cover">

                        <div class="flex min-w-0 flex-1 items-end justify-between gap-2">
                            <div class="flex min-w-0 flex-col gap-1">
                                <p class="text-h5 capitalize">{{ $product->name }}</p>
                                <p class="flex items-baseline gap-[7px]">
                                    <span class="text-price text-price-40">{{ money($line->variation->price_cents) }}</span>
                                    @if ($line->variation->previous_price_cents)
                                        <s class="text-body-xs font-normal text-muted">{{ money($line->variation->previous_price_cents) }}</s>
                                    @endif
                                </p>
                                @if ($product->discount_estimate)
                                    <x-store.discount-tag :percent="$product->discount_estimate" compact />
                                @endif
                            </div>

                            @if ($line->available)
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="text-body-xs font-bold">Qty:</span>
                                    <div class="flex h-7 items-center gap-2 rounded-full border-[1.5px] border-grayscale-40 px-2.5">
                                        <button type="button" wire:click="decrement({{ $id }})" aria-label="Decrease quantity"><x-store.icon name="minus" /></button>
                                        <span class="min-w-3 text-center text-body-sm font-bold" aria-live="polite">{{ $line->quantity }}</span>
                                        <button type="button" wire:click="increment({{ $id }})" aria-label="Increase quantity" @disabled($line->quantity >= min(\App\Support\Cart::MAX_QUANTITY, $line->variation->stock_quantity ?? PHP_INT_MAX))><x-store.icon name="plus" class="size-3" /></button>
                                    </div>
                                </div>
                            @else
                                <span class="shrink-0 text-body-xs text-grayscale-60">Item unavailable</span>
                            @endif
                        </div>
                    </div>

                    @if ($line->notice)
                        <p class="text-body-xs text-sale-60">{{ $line->notice }}</p>
                    @endif

                    <div class="flex items-center gap-4 text-[11px] leading-[14px] font-bold">
                        <button type="button" wire:click="remove({{ $id }})" class="flex items-center gap-1 underline">
                            Remove <x-store.icon name="card-remove" />
                        </button>
                        <a href="#" class="flex items-center gap-1 underline">
                            Add To <x-store.icon name="lovely" />
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-lead-mobile font-normal text-body-muted">Add an ingredient or a complete recipe to get started</p>
                <img src="{{ asset('images/store/empty-basket.svg') }}" alt="" class="mx-auto mt-28 w-[300px]">
            @endforelse
        </div>

        <div class="flex flex-col gap-6 border-t border-grayscale-40 px-6 pt-6 pb-8 md:px-8">
            <div class="flex items-center justify-between gap-2.5">
                <span class="text-body">Subtotal</span>
                <strong class="text-[28px] leading-[30px] font-bold tracking-[-1.25px]">{{ money($subtotal) }}</strong>
            </div>

            @if ($canCheckout)
                <a href="{{ route('checkout') }}" class="flex h-[50px] items-center justify-center gap-2 rounded-full bg-ink text-[14px] leading-[18px] font-extrabold text-white transition-colors hover:bg-grayscale-90">
                    Checkout securely <x-store.icon name="arrow-right" />
                </a>
            @else
                <span aria-disabled="true" class="flex h-[50px] cursor-not-allowed items-center justify-center gap-2 rounded-full bg-grayscale-30 text-[14px] leading-[18px] font-extrabold text-white">
                    Checkout securely <x-store.icon name="arrow-right" />
                </span>
            @endif

            @if ($lines->isNotEmpty())
                <a href="{{ route('cart') }}" class="-mt-2 text-center text-button-sm underline">View basket</a>
            @endif
        </div>
    </aside>
</div>
