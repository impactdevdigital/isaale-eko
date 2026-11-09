{{-- Food Stories listing (Figma 07.03, node 1180:72302) --}}
<div class="page-container pt-6 pb-10 md:pt-8">
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5 text-[11px] leading-[14px] font-extrabold text-body-muted">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true" class="text-[10px] font-bold">/</li>
            <li><a href="{{ route('kitchen.index') }}" class="hover:text-ink">Discover</a></li>
            <li aria-hidden="true" class="text-[10px] font-bold">/</li>
            <li class="text-ink" aria-current="page">Food Stories</li>
        </ol>
    </nav>

    <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,900fr)_minmax(0,364fr)] lg:gap-8">
        <div class="flex min-w-0 flex-col gap-8">
            <header class="flex flex-col gap-[3px]">
                <p class="text-[11px] leading-[14px] font-extrabold uppercase tracking-[1.35px] text-[#574B2F]">Stories, people &amp; flavours</p>
                <h1 class="pt-1 text-[32px] leading-[34px] font-bold tracking-[-1.6px] text-ink md:text-[42px] md:leading-10 md:tracking-[-2.4px]">Food Stories</h1>
                <p class="text-[14px] leading-[22px] font-medium text-body-muted">Real people, rich traditions. Everyday moments. Discover stories that celebrate African and Caribbean food culture and community.</p>
            </header>

            @if ($stories->isEmpty())
                <p class="rounded-[4px] bg-[#F6F6F6] px-6 py-10 text-center text-[14px] text-body-muted">New stories are on the way. Check back soon.</p>
            @else
                <div class="flex flex-col gap-6">
                    @foreach ($stories as $story)
                        <article wire:key="story-{{ $story->id }}" class="relative flex min-h-[420px] flex-col justify-end overflow-hidden rounded-xl bg-grayscale-90 p-5 md:min-h-[540px] md:p-6">
                            @if ($story->coverUrl())
                                <img src="{{ $story->coverUrl() }}" alt="" @if (! $loop->first) loading="lazy" @endif class="absolute inset-0 size-full object-cover">
                            @endif
                            <span class="absolute inset-0 bg-linear-to-t from-black/80 via-black/30 to-transparent" aria-hidden="true"></span>

                            <div class="relative flex flex-col gap-[3px] text-white">
                                <h2 class="pt-1 text-[28px] leading-[30px] font-bold tracking-[-1.4px] capitalize md:text-[42px] md:leading-10 md:tracking-[-2.4px]">
                                    <a href="{{ $story->url() }}" class="after:absolute after:inset-0">{{ $story->title }}</a>
                                </h2>
                                @if ($story->excerpt)
                                    <p class="max-w-[720px] text-[14px] leading-[22px] font-bold line-clamp-2">{{ $story->excerpt }}</p>
                                @endif
                                <span class="relative mt-4 inline-flex h-[42px] w-fit items-center rounded-full bg-brand-yellow px-5 text-[14px] leading-[18px] font-extrabold text-ink">Read story</span>
                                <p class="mt-4 flex items-center gap-4 text-[14px]">
                                    <span class="flex items-center gap-1.5 font-extrabold"><x-store.icon name="clock" class="size-4" /> {{ $story->readMinutes() }} min read</span>
                                    <span class="font-medium">{{ $story->dateLabel() }}</span>
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <x-store.stories-pager :paginator="$stories" />
            @endif
        </div>

        <x-store.story-sidebar :trending="$trending" :subscribed="$subscribed" />
    </div>

    <div class="mt-10">
        <x-store.testimonials />
    </div>
</div>
