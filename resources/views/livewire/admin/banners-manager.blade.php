@php
    use App\Livewire\Admin\BannersManager;
    use App\Models\Banner;

    $input = 'w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]';
    $label = 'block text-xs font-bold text-[#111111] mb-1.5';
    $help = 'mt-1 text-[11px] text-[#686868]';
    $error = 'mt-1 text-[11px] font-bold text-[#DF2600]';
    $stateStyles = [
        'Live' => 'bg-[#DAF4E7] text-[#007D49]',
        'Scheduled' => 'bg-[#EBF4FF] text-[#0055D4]',
        'Ended' => 'bg-[#F6F6F6] text-[#686868]',
        'Hidden' => 'bg-[#F6F6F6] text-[#686868]',
    ];
@endphp

<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Homepage Banners</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868] max-w-2xl">
                The two slideshows on the homepage. Add, edit, reorder or hide slides, and schedule them to start and end by themselves. Changes show on the store straight away.
            </p>
        </div>
        <button type="button" wire:click="create('hero')" style="background-color: #DF2600;" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold hover:opacity-90">
            + New Banner
        </button>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-[#E9F8F0] border border-[#A6E3C3] text-xs sm:text-sm font-bold text-[#007D49]" role="status">{{ session('success') }}</div>
    @endif

    @foreach ($groups as $placement => $group)
        <section class="bg-white rounded-2xl border border-[#EEEEEE]" aria-labelledby="placement-{{ $placement }}">
            <div class="flex items-center justify-between gap-3 p-4 sm:p-6 border-b border-[#EEEEEE]">
                <div>
                    <h3 id="placement-{{ $placement }}" class="text-sm font-extrabold text-[#111111]">{{ $group['label'] }}</h3>
                    <p class="text-[11px] text-[#686868]">{{ $group['banners']->filter->isLive()->count() }} showing now · slides rotate in this order</p>
                </div>
                <button type="button" wire:click="create('{{ $placement }}')" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6]">+ Add slide</button>
            </div>

            @forelse ($group['banners'] as $banner)
                <div wire:key="banner-{{ $banner->id }}" class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4 border-b border-[#EEEEEE] last:border-0">
                    <div class="relative w-full sm:w-48 aspect-[24/7] shrink-0 overflow-hidden rounded-xl {{ $banner->tint }}">
                        <img src="{{ $banner->imageUrl() }}" alt="" class="absolute inset-0 size-full object-cover object-right">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $stateStyles[$banner->stateLabel()] }}">{{ $banner->stateLabel() }}</span>
                            @if ($banner->title_fr)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-[#F6F6F6] text-[#686868]">FR</span>
                            @endif
                        </div>
                        <p class="mt-1 font-extrabold text-[#111111] truncate">{{ $banner->title }}</p>
                        <p class="text-xs text-[#686868] truncate">{{ $banner->body }}</p>
                        <p class="mt-1 text-[11px] text-[#686868]">
                            @if ($banner->cta_label)
                                Button “{{ $banner->cta_label }}” → {{ $banner->cta_url }}
                            @else
                                No button
                            @endif
                            @if ($banner->starts_at || $banner->ends_at)
                                · {{ $banner->starts_at ? 'from '.$banner->starts_at->setTimezone(config('shop.timezone'))->format('j M, g:i A') : '' }}
                                {{ $banner->ends_at ? 'until '.$banner->ends_at->setTimezone(config('shop.timezone'))->format('j M, g:i A') : '' }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <button type="button" wire:click="move({{ $banner->id }}, -1)" @disabled($loop->first) class="size-9 rounded-xl border border-[#EEEEEE] text-[#111111] hover:bg-[#F6F6F6] disabled:opacity-30" aria-label="Move {{ $banner->title }} earlier">↑</button>
                        <button type="button" wire:click="move({{ $banner->id }}, 1)" @disabled($loop->last) class="size-9 rounded-xl border border-[#EEEEEE] text-[#111111] hover:bg-[#F6F6F6] disabled:opacity-30" aria-label="Move {{ $banner->title }} later">↓</button>
                        <button type="button" wire:click="toggleActive({{ $banner->id }})" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6]">{{ $banner->is_active ? 'Hide' : 'Show' }}</button>
                        <button type="button" wire:click="edit({{ $banner->id }})" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6]">Edit</button>
                        <button type="button" wire:click="confirmDelete({{ $banner->id }})" class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#DF2600] hover:bg-[#FFF0EC]">Delete</button>
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-[#686868]">No slides here, so this slideshow is hidden on the homepage.</p>
            @endforelse
        </section>
    @endforeach

    {{-- Editor --}}
    @if ($showEditor)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="banner-editor-title">
            <div class="absolute inset-0 bg-black/50" wire:click="closeEditor"></div>
            <div class="relative mx-auto my-6 sm:my-12 w-[calc(100%-2rem)] max-w-3xl">
                <form wire:submit="save" class="bg-white rounded-2xl shadow-2xl">
                    <div class="flex items-center justify-between p-5 border-b border-[#EEEEEE]">
                        <h3 id="banner-editor-title" class="text-lg font-black text-[#111111]">{{ $editingId ? 'Edit banner' : 'New banner' }}</h3>
                        <button type="button" wire:click="closeEditor" class="p-2 rounded-xl text-[#686868] hover:bg-[#FAFAFA]" aria-label="Close">✕</button>
                    </div>

                    <div class="p-5 space-y-5">
                        {{-- Live preview --}}
                        <div class="relative h-40 sm:h-48 overflow-hidden rounded-2xl {{ $tint }}" aria-label="Preview">
                            @if ($previewImage)
                                <img src="{{ $previewImage }}" alt="" class="absolute inset-0 size-full object-cover object-right">
                            @endif
                            <div class="relative flex h-full max-w-[60%] flex-col justify-center gap-1 px-5 {{ (Banner::STYLES[$style] ?? Banner::STYLES['light'])[1] }}">
                                @if ($eyebrow)
                                    <p class="text-[10px] font-black uppercase tracking-[1px]">{{ $eyebrow }}</p>
                                @endif
                                <p class="text-xl sm:text-2xl font-bold leading-tight">{{ $title ?: 'Your headline' }}</p>
                                @if ($body)
                                    <p class="text-xs">{{ $body }}</p>
                                @endif
                                @if ($ctaLabel)
                                    <span class="mt-1 w-fit rounded-full px-3 py-1.5 text-xs font-extrabold {{ (Banner::STYLES[$style] ?? Banner::STYLES['light'])[2] }}">{{ $ctaLabel }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="banner-placement" class="{{ $label }}">Where</label>
                                <select id="banner-placement" wire:model.live="placement" class="{{ $input }}">
                                    @foreach (Banner::PLACEMENTS as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <span class="{{ $label }}">Background colour</span>
                                <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Background colour">
                                    @foreach (BannersManager::TINTS as $class => $name)
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="tint" value="{{ $class }}" class="peer sr-only">
                                            <span class="block size-9 rounded-full {{ $class }} ring-offset-2 peer-checked:ring-2 peer-checked:ring-[#DF2600] peer-focus-visible:ring-2 peer-focus-visible:ring-[#111111]" title="{{ $name }}"></span>
                                            <span class="sr-only">{{ $name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="{{ $help }}">Shows behind the photo while it loads and around the text.</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="banner-style" class="{{ $label }}">Text colour</label>
                                <select id="banner-style" wire:model.live="style" class="{{ $input }}">
                                    @foreach (Banner::STYLES as $value => [$text])
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                                <p class="{{ $help }}">Use dark text on light photos so the headline is easy to read.</p>
                                @error('style') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="banner-image" class="{{ $label }}">Photo</label>
                            <input id="banner-image" type="file" wire:model="imageUpload" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-[#686868] file:mr-3 file:rounded-xl file:border-0 file:bg-[#F6F6F6] file:px-4 file:py-2 file:text-sm file:font-bold file:text-[#111111]">
                            <p wire:loading wire:target="imageUpload" class="{{ $help }}">Uploading…</p>
                            <p class="{{ $help }}">JPG, PNG or WebP, up to 5 MB. Use a wide photo (about 2400 × 700) with the subject on the right: the text sits on the left.</p>
                            @error('imageUpload') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ([
                                ['eyebrow', 'Small heading above', 'e.g. This week only', 60],
                                ['title', 'Headline *', 'e.g. Time for a Pantry Reset?', 60],
                                ['body', 'Short text', 'One line about the offer', 160],
                                ['ctaLabel', 'Button text', 'e.g. Shop now', 30],
                            ] as [$field, $text, $placeholder, $max])
                                <div>
                                    <label for="banner-{{ $field }}" class="{{ $label }}">{{ $text }}</label>
                                    <input id="banner-{{ $field }}" type="text" wire:model.live.debounce.400ms="{{ $field }}" maxlength="{{ $max }}" placeholder="{{ $placeholder }}" class="{{ $input }}">
                                    @error($field) <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label for="banner-ctaUrl" class="{{ $label }}">Button goes to</label>
                            <input id="banner-ctaUrl" type="text" wire:model="ctaUrl" placeholder="/shop or /shop?sort=new or https://…" class="{{ $input }}">
                            <p class="{{ $help }}">Copy the part of the store address after isaleeko.com, e.g. <code>/shop?sort=new</code>, <code>/recipes/jollof-rice</code> or <code>/shop/country/nigeria</code>.</p>
                            @error('ctaUrl') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>

                        <details class="rounded-xl border border-[#EEEEEE] p-4" @if ($titleFr || $bodyFr) open @endif>
                            <summary class="cursor-pointer text-xs font-bold text-[#111111]">French text (optional): shown when shoppers switch to Français</summary>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach ([['eyebrowFr', 'Small heading (FR)'], ['titleFr', 'Headline (FR)'], ['bodyFr', 'Short text (FR)'], ['ctaLabelFr', 'Button text (FR)']] as [$field, $text])
                                    <div>
                                        <label for="banner-{{ $field }}" class="{{ $label }}">{{ $text }}</label>
                                        <input id="banner-{{ $field }}" type="text" wire:model="{{ $field }}" class="{{ $input }}">
                                        @error($field) <p class="{{ $error }}">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </details>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="banner-starts" class="{{ $label }}">Starts (optional)</label>
                                <input id="banner-starts" type="datetime-local" wire:model="startsAt" class="{{ $input }}">
                            </div>
                            <div>
                                <label for="banner-ends" class="{{ $label }}">Ends (optional)</label>
                                <input id="banner-ends" type="datetime-local" wire:model="endsAt" class="{{ $input }}">
                                @error('endsAt') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm font-bold text-[#111111] sm:pt-6">
                                <input type="checkbox" wire:model="isActive" class="rounded accent-[#DF2600]"> Show on homepage
                            </label>
                        </div>
                        <p class="{{ $help }}">Times are Atlantic Time. Leave the dates empty to show the banner until you hide it. A promotion banner with an end date disappears by itself.</p>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 p-5 border-t border-[#EEEEEE] bg-[#FAFAFA] rounded-b-2xl">
                        <button type="button" wire:click="closeEditor" class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm font-bold text-[#111111]">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" style="background-color: #DF2600;" class="px-5 py-2.5 rounded-xl text-white text-sm font-extrabold hover:opacity-90 disabled:opacity-60">Save banner</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($deletingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="banner-delete-title">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('deletingId', null)"></div>
            <div class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-2xl">
                <h3 id="banner-delete-title" class="text-lg font-black text-[#111111]">Delete this banner?</h3>
                <p class="mt-2 text-sm text-[#686868]">It disappears from the homepage straight away. To take it down for now, use Hide instead.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="$set('deletingId', null)" class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-sm font-bold text-[#111111]">Keep it</button>
                    <button type="button" wire:click="delete" style="background-color: #DF2600;" class="px-4 py-2.5 rounded-xl text-white text-sm font-extrabold">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
