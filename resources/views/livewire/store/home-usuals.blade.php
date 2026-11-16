<div>
    @if ($products->isNotEmpty())
        <section class="flex flex-col gap-4 pt-10 pb-2" aria-labelledby="usuals-title">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-caption font-extrabold uppercase text-body-muted">Welcome back{{ $firstName ? ', '.$firstName : '' }}</p>
                    <h2 id="usuals-title" class="mt-1 text-h2-mobile md:text-h2">Your Usuals</h2>
                    <p class="mt-1 text-body-sm text-body-muted md:text-body">What you buy most often, at today’s prices.</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('account.buy-again') }}" wire:navigate class="text-[14px] font-bold underline">See all you’ve bought</a>
                    @if ($canReorder)
                        <button type="button" wire:click="reorderUsuals" wire:loading.attr="disabled" class="h-[42px] rounded-full bg-ink px-6 text-[14px] font-bold text-white transition-colors hover:bg-grayscale-90 disabled:opacity-60">
                            Reorder your usuals · {{ money($usualsTotal) }}
                        </button>
                    @endif
                </div>
            </div>
            <div class="flex snap-x gap-2.5 overflow-x-auto [scrollbar-width:none] md:grid md:grid-cols-4 md:overflow-visible lg:grid-cols-6 xl:grid-cols-7">
                @foreach ($products as $product)
                    <x-store.product-card :product="$product" class="w-40 shrink-0 snap-start md:w-auto" wire:key="usual-{{ $product->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
