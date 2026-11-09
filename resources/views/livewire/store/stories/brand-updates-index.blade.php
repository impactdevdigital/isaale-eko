{{-- Brand Updates listing (Figma 07.05, node 1180:72186) --}}
<div class="page-container pt-6 pb-10 md:pt-8">
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5 text-[11px] leading-[14px] font-extrabold text-body-muted">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true" class="text-[10px] font-bold">/</li>
            <li><a href="{{ route('kitchen.index') }}" class="hover:text-ink">Discover</a></li>
            <li aria-hidden="true" class="text-[10px] font-bold">/</li>
            <li class="text-ink" aria-current="page">Brand Updates</li>
        </ol>
    </nav>

    <header class="relative mt-8 overflow-hidden rounded-[4px] bg-brand-yellow-0 md:h-[250px]">
        <img src="{{ asset('images/store/updates/brand-updates-hero.webp') }}" alt="" class="absolute inset-0 hidden size-full object-cover md:block">
        <div class="relative flex flex-col gap-2 px-6 py-8 md:w-1/2 md:px-[30px] md:py-[51px]">
            <p class="text-[11px] leading-[14px] font-extrabold uppercase tracking-[1.35px] text-[#574B2F]">Discover</p>
            <h1 class="text-[32px] leading-[34px] font-bold tracking-[-1.6px] text-ink md:text-[42px] md:leading-10 md:tracking-[-2.4px]">Brand Updates</h1>
            <p class="max-w-[510px] text-[14px] leading-[22px] font-medium text-body-muted md:text-[16px] md:leading-[26px]">
                Stay informed. Stay connected.<br>
                Get the latest news, store updates, holiday hours, new product launches and everything happening at Ìsàlèè Èkó.
            </p>
        </div>
    </header>

    @if ($updates->isEmpty())
        <p class="mt-12 rounded-[4px] bg-[#F6F6F6] px-6 py-10 text-center text-[14px] text-body-muted">No updates yet. Check back soon.</p>
    @else
        <div class="mt-12 flex flex-col divide-y divide-[#DEDEDE]">
            @foreach ($updates as $update)
                <article wire:key="update-{{ $update->id }}" class="group relative grid gap-4 py-4 first:pt-0 md:grid-cols-[511px_1fr] md:items-start">
                    <span class="block aspect-[511/190] overflow-hidden rounded-[4px] bg-grayscale-20">
                        @if ($update->coverUrl())
                            <img src="{{ $update->coverUrl() }}" alt="" @if ($loop->index > 1) loading="lazy" @endif class="size-full object-cover transition-transform duration-300 group-hover:scale-[1.02]">
                        @endif
                    </span>
                    <div class="flex flex-col gap-2">
                        <p class="text-[12px] leading-[18px] font-medium text-ink">{{ $update->dateLabel() }}</p>
                        <h2 class="text-[22px] leading-[24px] font-bold tracking-[-1px] text-ink capitalize md:text-[28px] md:leading-7">
                            <a href="{{ $update->url() }}" class="after:absolute after:inset-0 group-hover:underline">{{ $update->title }}</a>
                        </h2>
                        @if ($update->excerpt)
                            <p class="max-w-[700px] text-[14px] leading-5 text-[#575042] line-clamp-3">{{ $update->excerpt }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <x-store.stories-pager :paginator="$updates" class="mt-6" />
    @endif

    <div class="mt-10">
        <x-store.testimonials />
    </div>
</div>
