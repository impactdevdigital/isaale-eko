<div>
    <x-store.account-shell active="wishlist" :crumbs="['Your account' => route('account'), 'Wishlist' => null]">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-h2-mobile md:text-h2">Wishlist</h1>
                <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Favourites you’ve saved for later.</p>
            </div>
            @if ($products->contains->inStock())
                <button type="button" wire:click="addAll" class="h-[42px] rounded-full bg-ink px-6 text-[15px] font-bold text-white">Add all to basket</button>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="mt-10 rounded-sm bg-grayscale-0 px-6 py-12 text-center">
                <x-store.icon name="heart" class="mx-auto size-8 text-grayscale-50" />
                <p class="mt-3 text-[16px] font-bold">Nothing saved yet</p>
                <p class="mt-1 text-body-sm text-body-muted">Tap the heart on any product to keep it here.</p>
                <a href="{{ route('shop') }}" class="mt-5 inline-flex h-10 items-center rounded-full bg-ink px-6 text-[14px] font-bold text-white">Browse the shop</a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <div wire:key="wish-{{ $product->id }}" class="relative">
                        <x-store.product-card :product="$product" class="h-full" />
                        <button type="button" wire:click="remove({{ $product->id }})" class="absolute right-2 top-2 z-10 flex size-8 items-center justify-center rounded-full bg-surface text-price-40 shadow-[0_0_8px_rgb(0_0_0/0.15)]" aria-label="Remove {{ $product->name }} from wishlist">
                            <x-store.icon name="close" class="size-4" />
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-store.account-shell>
</div>
