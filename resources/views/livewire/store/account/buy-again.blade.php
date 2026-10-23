<div>
    <x-store.account-shell active="buy-again" :crumbs="['Your account' => route('account'), 'Buy again' => null]">
        <h1 class="text-h2-mobile md:text-h2">Buy Again</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Your usual groceries, most recent first.</p>

        @if ($products->isEmpty())
            <div class="mt-10 rounded-sm bg-grayscale-0 px-6 py-12 text-center">
                <p class="text-[16px] font-bold">Nothing to reorder yet</p>
                <p class="mt-1 text-body-sm text-body-muted">Products from your orders will show up here for a quick reorder.</p>
                <a href="{{ route('shop') }}" class="mt-5 inline-flex h-10 items-center rounded-full bg-ink px-6 text-[14px] font-bold text-white">Start shopping</a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-store.product-card :product="$product" wire:key="again-{{ $product->id }}" />
                @endforeach
            </div>
        @endif
    </x-store.account-shell>
</div>
