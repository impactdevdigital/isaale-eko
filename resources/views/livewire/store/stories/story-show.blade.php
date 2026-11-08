{{-- Food Story / Brand Update detail (Figma 07.04, node 1180:71841) --}}
@php
    $isFood = $story->isFoodStory();
    $sectionLabel = $isFood ? 'Food Stories' : 'Brand Updates';
    $sectionUrl = $isFood ? route('stories.index') : route('updates.index');
@endphp

<div class="page-container pt-6 pb-10 md:pt-8">
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1.5 text-[11px] leading-[14px] font-extrabold text-body-muted">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true" class="text-[10px] font-bold">/</li>
            <li><a href="{{ route('kitchen.index') }}" class="hover:text-ink">Discover</a></li>
            <li aria-hidden="true" class="text-[10px] font-bold">/</li>
            <li><a href="{{ $sectionUrl }}" class="text-ink hover:underline">{{ $sectionLabel }}</a></li>
        </ol>
    </nav>

    @if (! $story->isPublished())
        <p class="mt-6 rounded-[4px] bg-brand-yellow-0 px-4 py-3 text-[14px] font-bold" role="status">Preview: this {{ $isFood ? 'story' : 'update' }} isn’t published yet. Only staff can see it.</p>
    @endif

    <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,900fr)_minmax(0,364fr)] lg:gap-8">
        <article class="min-w-0">
            <header>
                <h1 class="text-[32px] leading-[34px] font-bold tracking-[-1.6px] text-ink capitalize md:text-[42px] md:leading-10 md:tracking-[-2.4px]">{{ $story->title }}</h1>
                <p class="mt-3 flex items-center gap-4 text-[14px] text-body-muted">
                    <span class="flex items-center gap-1.5 font-extrabold"><x-store.icon name="clock" class="size-4" /> {{ $story->readMinutes() }} min read</span>
                    <span class="h-[22px] w-px bg-grayscale-70" aria-hidden="true"></span>
                    <span class="font-medium">{{ $story->dateLabel() }}</span>
                </p>
            </header>

            @if (! $isFood && $story->coverUrl())
                <img src="{{ $story->coverUrl() }}" alt="" class="mt-8 aspect-[511/200] w-full rounded-[4px] object-cover">
            @endif

            <div class="story-body mt-10 text-[14px] leading-5 text-body-muted [&_a]:font-bold [&_a]:text-ink [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-brand-yellow [&_blockquote]:pl-4 [&_em]:text-ink [&_h2]:mt-8 [&_h2]:text-[16px] [&_h2]:leading-6 [&_h2]:font-extrabold [&_h2]:text-ink [&_h3]:mt-6 [&_h3]:font-extrabold [&_h3]:text-ink [&_li]:mt-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mt-4 [&_strong]:text-ink [&_ul]:list-disc [&_ul]:pl-5 [&>*:first-child]:mt-0">
                {{ $story->bodyHtml() }}
            </div>

            @if ($story->allow_comments || $comments->isNotEmpty())
                <section class="mt-16" aria-labelledby="comments-title">
                    @if ($comments->isNotEmpty())
                        <h2 id="comments-title" class="text-[18px] leading-6 font-extrabold text-ink">{{ $comments->count() }} {{ str('Comment')->plural($comments->count()) }}</h2>
                        <ul class="mt-4 divide-y divide-grayscale-30 border-y border-grayscale-30">
                            @foreach ($comments as $c)
                                <li class="py-4" wire:key="comment-{{ $c->id }}">
                                    <p class="text-[14px] font-extrabold text-ink">{{ $c->name }} <span class="ml-2 text-[12px] font-medium text-body-muted">{{ $c->created_at->setTimezone(config('shop.timezone'))->format('j F Y') }}</span></p>
                                    <p class="mt-1 text-[14px] leading-5 whitespace-pre-line text-body-muted">{{ $c->body }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($story->allow_comments)
                        <div @class(['mt-10' => $comments->isNotEmpty()])
                             x-data="{
                                 remember: false,
                                 init() {
                                     try {
                                         const saved = JSON.parse(localStorage.getItem('isaale-comment-author') || 'null');
                                         if (saved && ! $wire.commentName) { $wire.commentName = saved.name || ''; $wire.commentEmail = saved.email || ''; this.remember = true; }
                                     } catch (e) {}
                                 },
                                 save() {
                                     try {
                                         if (this.remember) localStorage.setItem('isaale-comment-author', JSON.stringify({ name: $wire.commentName, email: $wire.commentEmail }));
                                         else localStorage.removeItem('isaale-comment-author');
                                     } catch (e) {}
                                 },
                             }">
                            <h2 @if ($comments->isEmpty()) id="comments-title" @endif class="text-[18px] leading-6 font-extrabold text-ink">Leave a Reply</h2>
                            <p class="mt-2 text-[14px] leading-[22px] font-medium text-body-muted">Your email address will not be published. Required fields are marked *</p>

                            @if ($commentSent)
                                <p class="mt-6 flex items-center gap-2 rounded-[4px] bg-[#DAF4E7] px-4 py-3 text-[14px] font-bold text-[#007D49]" role="status">
                                    <x-store.icon name="check" class="size-4" /> Thanks! Your comment will appear once our team has approved it.
                                </p>
                            @endif

                            <form wire:submit="postComment" @submit="save()" class="mt-6 flex flex-col gap-4" novalidate>
                                <div>
                                    <label for="comment-body" class="text-[14px] leading-[22px] font-medium text-ink">Comment *</label>
                                    <div class="relative mt-1.5">
                                        <textarea id="comment-body" wire:model="comment" rows="5" maxlength="{{ \App\Models\StoryComment::MAX_LENGTH }}" placeholder="Type Here..."
                                                  class="block h-[130px] w-full resize-none rounded-md border border-[#E2E8F0] bg-surface px-3 pt-2 pb-6 text-[14px] leading-5 text-ink placeholder:text-[#6B7280] focus:border-ink focus:outline-none"></textarea>
                                        <span class="pointer-events-none absolute right-3 bottom-1.5 text-[10px] text-[#6B7280]" x-text="($wire.comment || '').length + '/{{ \App\Models\StoryComment::MAX_LENGTH }}'">0/{{ \App\Models\StoryComment::MAX_LENGTH }}</span>
                                    </div>
                                    @error('comment') <p class="mt-1 text-body-xs text-price-40">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2 sm:gap-2.5">
                                    <div>
                                        <label for="comment-name" class="text-[14px] leading-[22px] font-medium text-ink">Name *</label>
                                        <input id="comment-name" type="text" wire:model="commentName" autocomplete="name"
                                               class="mt-1.5 h-[42px] w-full rounded-lg border border-[#E2E8F0] bg-surface px-4 text-[14px] text-ink focus:border-ink focus:outline-none">
                                        @error('commentName') <p class="mt-1 text-body-xs text-price-40">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="comment-email" class="text-[14px] leading-[22px] font-medium text-ink">Email *</label>
                                        <input id="comment-email" type="email" wire:model="commentEmail" autocomplete="email"
                                               class="mt-1.5 h-[42px] w-full rounded-lg border border-[#E2E8F0] bg-surface px-4 text-[14px] text-ink focus:border-ink focus:outline-none">
                                        @error('commentEmail') <p class="mt-1 text-body-xs text-price-40">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                {{-- Spam trap: people never see or fill this. --}}
                                <div class="hidden" aria-hidden="true">
                                    <label for="comment-website">Website</label>
                                    <input id="comment-website" type="text" wire:model="website" tabindex="-1" autocomplete="off">
                                </div>

                                <label class="flex items-start gap-2.5 text-[14px] leading-6 text-[#1A202C]">
                                    <input type="checkbox" x-model="remember" class="mt-1 size-4 rounded border-[#E2E8F0] accent-ink">
                                    Save my name, and email in this browser for the next time I comment.
                                </label>

                                <button type="submit" wire:loading.attr="disabled" wire:target="postComment"
                                        class="h-[42px] w-fit rounded-full bg-ink px-5 text-[14px] leading-[18px] font-extrabold text-white transition-colors hover:bg-grayscale-90 disabled:opacity-60">
                                    Post comment
                                </button>
                            </form>
                        </div>
                    @endif
                </section>
            @endif
        </article>

        <x-store.story-sidebar :trending="$trending" :subscribed="$subscribed" class="lg:pt-0" />
    </div>

    <div class="mt-10">
        <x-store.testimonials />
    </div>
</div>
