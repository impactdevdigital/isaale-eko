@php
    use App\Models\Story;
    use App\Models\StoryComment;

    $input = 'w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]';
    $label = 'block text-xs font-bold text-[#111111] mb-1.5';
@endphp

<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Food Stories &amp; Brand Updates</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868]">
                Write stories and store news for the Discover pages. Reader comments wait here until you approve them.
            </p>
        </div>
        @if ($tab === 'stories')
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                <button type="button" wire:click="create('{{ Story::TYPE_FOOD }}')" style="background-color: #DF2600;"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-90 shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    New Food Story
                </button>
                <button type="button" wire:click="create('{{ Story::TYPE_UPDATE }}')"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-[#111111] text-xs sm:text-sm font-extrabold hover:bg-[#F6F6F6]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    New Brand Update
                </button>
            </div>
        @endif
    </div>

    @if (session()->has('success'))
        <div class="p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl flex items-center justify-between text-[#007D49] text-xs sm:text-sm font-semibold">
            <span>{{ session('success') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100 ml-2">Dismiss</button>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex items-center gap-1.5 text-xs font-bold">
        <button type="button" wire:click="$set('tab', 'stories')"
                class="px-3.5 py-2 rounded-lg {{ $tab === 'stories' ? 'bg-[#111111] text-white' : 'text-[#686868] hover:bg-[#F6F6F6]' }}">
            Stories &amp; Updates
        </button>
        <button type="button" wire:click="$set('tab', 'comments')"
                class="px-3.5 py-2 rounded-lg inline-flex items-center gap-2 {{ $tab === 'comments' ? 'bg-[#111111] text-white' : 'text-[#686868] hover:bg-[#F6F6F6]' }}">
            Comments
            @if ($pendingCount)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-[#DF2600] text-white">{{ $pendingCount }} to review</span>
            @endif
        </button>
    </div>

    @if ($tab === 'stories')
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs flex flex-col md:flex-row gap-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search by title..." class="{{ $input }} md:flex-1">
            <select wire:model.live="typeFilter" class="{{ $input }} md:w-48">
                <option value="">All types</option>
                @foreach (Story::TYPES as $value => $name)
                    <option value="{{ $value }}">{{ $name }}s</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="{{ $input }} md:w-40">
                <option value="">Any status</option>
                <option value="{{ Story::STATUS_PUBLISHED }}">Published</option>
                <option value="{{ Story::STATUS_DRAFT }}">Draft</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
            <ul class="divide-y divide-[#EEEEEE]">
                @forelse ($stories as $story)
                    <li wire:key="story-{{ $story->id }}" class="p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                        <span class="w-full sm:w-28 h-20 shrink-0 rounded-lg bg-[#F6F6F6] overflow-hidden">
                            @if ($story->coverUrl())
                                <img src="{{ $story->coverUrl() }}" alt="" class="size-full object-cover">
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $story->isFoodStory() ? 'bg-[#FFF9EA] text-[#D58700]' : 'bg-[#EEF4FF] text-[#0055D4]' }}">{{ Story::TYPES[$story->type] }}</span>
                                @if ($story->isPublished())
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">Published</span>
                                @elseif ($story->status === Story::STATUS_PUBLISHED)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EEF4FF] text-[#0055D4]">Scheduled</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#F6F6F6] text-[#686868]">Draft</span>
                                @endif
                                @if ($story->is_trending)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF0EC] text-[#DF2600]">Trending</span>
                                @endif
                                @if ($story->pending_comments_count)
                                    <button type="button" wire:click="$set('tab', 'comments')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#DF2600] text-white">{{ $story->pending_comments_count }} comment{{ $story->pending_comments_count > 1 ? 's' : '' }} to review</button>
                                @endif
                            </div>
                            <p class="mt-1.5 font-extrabold text-[#111111] truncate">{{ $story->title }}</p>
                            <p class="text-xs text-[#686868]">{{ $story->published_at ? $story->dateLabel() : 'Not published yet' }} · {{ $story->readMinutes() }} min read</p>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <a href="{{ $story->url() }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6]">{{ $story->isPublished() ? 'View' : 'Preview' }}</a>
                            <button type="button" wire:click="edit({{ $story->id }})" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6]">Edit</button>
                            <button type="button" wire:click="togglePublish({{ $story->id }})" class="px-3 py-2 rounded-xl text-xs font-bold {{ $story->status === Story::STATUS_PUBLISHED ? 'border border-[#EEEEEE] text-[#686868] hover:bg-[#F6F6F6]' : 'bg-[#007D49] text-white hover:opacity-90' }}">
                                {{ $story->status === Story::STATUS_PUBLISHED ? 'Unpublish' : 'Publish' }}
                            </button>
                            <button type="button" wire:click="confirmDelete({{ $story->id }})" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#DF2600] hover:bg-[#FFF0EC]">Delete</button>
                        </div>
                    </li>
                @empty
                    <li class="p-10 text-center text-sm text-[#686868]">No stories match these filters yet.</li>
                @endforelse
            </ul>
            @if ($stories->hasPages())
                <div class="p-4 border-t border-[#EEEEEE]">{{ $stories->links() }}</div>
            @endif
        </div>
    @else
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs flex flex-wrap gap-1.5 text-xs font-bold">
            @foreach ([StoryComment::STATUS_PENDING => 'Waiting for approval', StoryComment::STATUS_APPROVED => 'Approved', StoryComment::STATUS_REJECTED => 'Rejected', '' => 'All'] as $value => $name)
                <button type="button" wire:click="$set('commentFilter', '{{ $value }}')"
                        class="px-3.5 py-1.5 rounded-lg {{ $commentFilter === $value ? 'bg-[#111111] text-white' : 'text-[#686868] hover:bg-[#F6F6F6]' }}">{{ $name }}</button>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
            <ul class="divide-y divide-[#EEEEEE]">
                @forelse ($comments as $c)
                    <li wire:key="comment-{{ $c->id }}" class="p-4 sm:p-5 flex flex-col lg:flex-row gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-extrabold text-[#111111]">{{ $c->name }} <span class="font-medium text-[#686868]">· {{ $c->email }}</span></p>
                            <p class="text-xs text-[#686868]">
                                On <a href="{{ $c->story?->url() }}" target="_blank" rel="noopener" class="font-bold text-[#111111] hover:underline">{{ $c->story?->title }}</a>
                                · {{ $c->created_at->setTimezone(config('shop.timezone'))->format('j M Y, H:i') }}
                            </p>
                            <p class="mt-2 text-sm text-[#111111] whitespace-pre-line">{{ $c->body }}</p>
                        </div>
                        <div class="flex flex-wrap items-start gap-2 shrink-0">
                            @if ($c->status !== StoryComment::STATUS_APPROVED)
                                <button type="button" wire:click="approveComment({{ $c->id }})" class="px-3 py-2 rounded-xl bg-[#007D49] text-white text-xs font-bold hover:opacity-90">Approve</button>
                            @endif
                            @if ($c->status !== StoryComment::STATUS_REJECTED)
                                <button type="button" wire:click="rejectComment({{ $c->id }})" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#686868] hover:bg-[#F6F6F6]">{{ $c->status === StoryComment::STATUS_APPROVED ? 'Hide' : 'Reject' }}</button>
                            @endif
                            <button type="button" wire:click="deleteComment({{ $c->id }})" wire:confirm="Delete this comment for good?" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#DF2600] hover:bg-[#FFF0EC]">Delete</button>
                        </div>
                    </li>
                @empty
                    <li class="p-10 text-center text-sm text-[#686868]">{{ $commentFilter === StoryComment::STATUS_PENDING ? 'No comments waiting. You’re all caught up.' : 'No comments here.' }}</li>
                @endforelse
            </ul>
            @if ($comments->hasPages())
                <div class="p-4 border-t border-[#EEEEEE]">{{ $comments->links() }}</div>
            @endif
        </div>
    @endif

    {{-- Editor --}}
    @if ($showEditor)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:p-8" wire:keydown.escape.window="closeEditor">
            <form wire:submit="save" class="w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                <div class="flex items-center justify-between p-5 border-b border-[#EEEEEE]">
                    <h3 class="text-lg font-extrabold text-[#111111]">{{ $editingId ? 'Edit' : 'New' }} {{ Story::TYPES[$type] }}</h3>
                    <button type="button" wire:click="closeEditor" class="text-sm font-bold text-[#686868] hover:text-[#111111]">Close</button>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $label }}" for="story-type">Type</label>
                            <select id="story-type" wire:model.live="type" class="{{ $input }}">
                                @foreach (Story::TYPES as $value => $name)
                                    <option value="{{ $value }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="story-author">Author (optional)</label>
                            <input id="story-author" type="text" wire:model="authorName" class="{{ $input }}" placeholder="e.g. Amina Yusuf">
                        </div>
                    </div>

                    <div>
                        <label class="{{ $label }}" for="story-title">Title</label>
                        <input id="story-title" type="text" wire:model.live.debounce.400ms="title" class="{{ $input }}">
                        @error('title') <p class="mt-1 text-xs text-[#DF2600]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}" for="story-slug">Web address</label>
                        <div class="flex items-center gap-1 text-xs text-[#686868]">
                            <span class="shrink-0">/{{ $type === Story::TYPE_FOOD ? 'food-stories' : 'brand-updates' }}/</span>
                            <input id="story-slug" type="text" wire:model="slug" class="{{ $input }}">
                        </div>
                        @error('slug') <p class="mt-1 text-xs text-[#DF2600]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}" for="story-excerpt">Short summary <span class="font-medium text-[#686868]">(shown on the listing cards)</span></label>
                        <textarea id="story-excerpt" wire:model="excerpt" rows="2" class="{{ $input }}"></textarea>
                        @error('excerpt') <p class="mt-1 text-xs text-[#DF2600]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}" for="story-body">Story</label>
                        <textarea id="story-body" wire:model="body" rows="12" class="{{ $input }} font-mono text-[13px]"></textarea>
                        <p class="mt-1 text-[11px] text-[#686868]">Leave a blank line between paragraphs. Start a line with <code>## </code> for a heading. Wrap words in <code>**double stars**</code> for bold or <code>*single stars*</code> for italics.</p>
                        @error('body') <p class="mt-1 text-xs text-[#DF2600]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="{{ $label }}">Cover photo</span>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <span class="w-full sm:w-40 h-24 shrink-0 rounded-lg bg-[#F6F6F6] overflow-hidden">
                                @if ($coverUpload)
                                    <img src="{{ $coverUpload->temporaryUrl() }}" alt="" class="size-full object-cover">
                                @elseif ($coverImage)
                                    <img src="{{ str_starts_with($coverImage, 'http') ? $coverImage : asset($coverImage) }}" alt="" class="size-full object-cover">
                                @endif
                            </span>
                            <div class="flex-1 space-y-2">
                                <input type="file" wire:model="coverUpload" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-[#686868] file:mr-3 file:rounded-lg file:border-0 file:bg-[#F6F6F6] file:px-3 file:py-2 file:text-xs file:font-bold file:text-[#111111]">
                                <p wire:loading wire:target="coverUpload" class="text-xs text-[#686868]">Uploading…</p>
                                <p class="text-[11px] text-[#686868]">JPG, PNG or WebP, up to 5 MB. Wide photos look best.</p>
                                @error('coverUpload') <p class="text-xs text-[#DF2600]">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <label class="{{ $label }}" for="story-status">Status</label>
                            <select id="story-status" wire:model.live="status" class="{{ $input }}">
                                <option value="{{ Story::STATUS_DRAFT }}">Draft (hidden)</option>
                                <option value="{{ Story::STATUS_PUBLISHED }}">Published</option>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="story-date">Publish date</label>
                            <input id="story-date" type="datetime-local" wire:model="publishedAt" class="{{ $input }}">
                            <p class="mt-1 text-[11px] text-[#686868]">Empty = now. A future date schedules it.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="story-minutes">Read time (minutes)</label>
                            <input id="story-minutes" type="number" min="1" max="120" wire:model="readMinutes" class="{{ $input }}" placeholder="Auto">
                            @error('readMinutes') <p class="mt-1 text-xs text-[#DF2600]">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-6 text-sm text-[#111111]">
                        @if ($type === Story::TYPE_FOOD)
                            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="isTrending" class="rounded accent-[#DF2600]"> Show in Trending Stories</label>
                        @endif
                        <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="allowComments" class="rounded accent-[#DF2600]"> Allow comments</label>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 p-5 border-t border-[#EEEEEE] bg-[#FAFAFA] rounded-b-2xl">
                    <button type="button" wire:click="closeEditor" class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm font-bold text-[#111111]">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" style="background-color: #DF2600;" class="px-5 py-2.5 rounded-xl text-white text-sm font-extrabold hover:opacity-90 disabled:opacity-60">
                        {{ $status === Story::STATUS_PUBLISHED ? 'Save & publish' : 'Save draft' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Delete confirmation --}}
    @if ($deletingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md bg-white rounded-2xl p-6 shadow-xl">
                <h3 class="text-lg font-extrabold text-[#111111]">Delete this post?</h3>
                <p class="mt-2 text-sm text-[#686868]">It disappears from the store straight away and its comments are deleted too. This can’t be undone. To hide it for now, unpublish it instead.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="$set('deletingId', null)" class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-sm font-bold">Keep it</button>
                    <button type="button" wire:click="delete" style="background-color: #DF2600;" class="px-4 py-2.5 rounded-xl text-white text-sm font-extrabold">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
