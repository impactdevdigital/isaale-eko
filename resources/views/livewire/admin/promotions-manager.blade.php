@php
    use App\Models\Promotion;

    $input = 'w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]';
    $label = 'block text-xs font-bold text-[#111111] mb-1.5';
    $help = 'mt-1 text-[11px] text-[#686868]';
    $error = 'mt-1 text-[11px] font-bold text-[#DF2600]';
    $stateStyles = [
        'live' => 'bg-[#DAF4E7] text-[#007D49]',
        'scheduled' => 'bg-[#E0F1FF] text-[#007FE8]',
        'draft' => 'bg-[#F6F6F6] text-[#686868]',
        'paused' => 'bg-[#FFF8E6] text-[#B76E00]',
        'capped' => 'bg-[#FFF8E6] text-[#B76E00]',
        'ended' => 'bg-[#F6F6F6] text-[#686868]',
    ];
    $firstOrder = $template === Promotion::TEMPLATE_FIRST_ORDER;
@endphp

<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Promotions</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868]">
                Build offers from templates. Every promotion has an end date and ends by itself. One discount per order: shoppers get the better of a promotion or a promo/reward code.
            </p>
        </div>
        @if ($step === 'list')
            <button type="button" wire:click="create" style="background-color: #DF2600;"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-90 shadow-xs shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                Create promotion
            </button>
        @endif
    </div>

    @if (session()->has('success'))
        <div class="p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl text-[#007D49] text-xs sm:text-sm font-semibold">{{ session('success') }}</div>
    @endif

    {{-- Step: list --}}
    @if ($step === 'list')
        <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
            @forelse ($promotions as $promotion)
                @php($state = $promotion->state())
                <div wire:key="promo-{{ $promotion->id }}" class="p-4 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-[#EEEEEE] last:border-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-extrabold text-[#111111]">{{ $promotion->name }}</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $stateStyles[$state] }}">{{ $state }}</span>
                        </div>
                        <p class="mt-1 text-xs text-[#686868]">
                            {{ Promotion::TEMPLATES[$promotion->template] }} · {{ $promotion->offerLabel() }} · {{ Promotion::AUDIENCES[$promotion->audience] }} · {{ Promotion::OBJECTIVES[$promotion->objective] }}
                        </p>
                        <p class="mt-0.5 text-xs text-[#686868]">
                            {{ $promotion->starts_at->timezone(config('shop.timezone'))->format('j M, g:i A') }} → {{ $promotion->ends_at->timezone(config('shop.timezone'))->format('j M, g:i A') }}
                            · {{ $promotion->redeemed }} {{ $promotion->max_redemptions ? 'of '.$promotion->max_redemptions : '' }} redeemed
                            {{ $promotion->per_customer_limit ? '· '.$promotion->per_customer_limit.' per customer' : '' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($state === 'draft')
                            <button type="button" wire:click="edit({{ $promotion->id }})" class="px-3 py-1.5 rounded-lg border border-[#EEEEEE] text-xs font-bold hover:bg-[#F6F6F6]">Edit</button>
                            <button type="button" wire:click="delete({{ $promotion->id }})" wire:confirm="Delete this draft?" class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#DF2600] hover:bg-[#FFF0EC]">Delete</button>
                        @elseif (in_array($state, ['live', 'scheduled', 'capped'], true))
                            <button type="button" wire:click="pause({{ $promotion->id }})" class="px-3 py-1.5 rounded-lg border border-[#EEEEEE] text-xs font-bold hover:bg-[#F6F6F6]">Pause</button>
                            <button type="button" wire:click="endNow({{ $promotion->id }})" wire:confirm="End this promotion now? It can’t be restarted." class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#DF2600] hover:bg-[#FFF0EC]">End now</button>
                        @elseif ($state === 'paused')
                            <button type="button" wire:click="resume({{ $promotion->id }})" class="px-3 py-1.5 rounded-lg border border-[#EEEEEE] text-xs font-bold hover:bg-[#F6F6F6]">Resume</button>
                            <button type="button" wire:click="endNow({{ $promotion->id }})" wire:confirm="End this promotion now? It can’t be restarted." class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#DF2600] hover:bg-[#FFF0EC]">End now</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <p class="text-sm font-bold text-[#111111]">No promotions yet</p>
                    <p class="mt-1 text-xs text-[#686868]">Start with an item discount or a first online order offer.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Step: choose a template --}}
    @if ($step === 'template')
        <div class="grid gap-4 md:grid-cols-2">
            <button type="button" wire:click="chooseTemplate('{{ Promotion::TEMPLATE_ITEM_DISCOUNT }}')" class="text-left p-6 rounded-2xl border border-[#EEEEEE] bg-white hover:border-[#DF2600] shadow-xs">
                <p class="text-base font-extrabold text-[#111111]">Item discount</p>
                <p class="mt-1 text-xs text-[#686868]">Percent or dollar off chosen products or a collection, between two dates. Shown on the product page and taken off in the basket.</p>
            </button>
            <button type="button" wire:click="chooseTemplate('{{ Promotion::TEMPLATE_FIRST_ORDER }}')" class="text-left p-6 rounded-2xl border border-[#EEEEEE] bg-white hover:border-[#DF2600] shadow-xs">
                <p class="text-base font-extrabold text-[#111111]">First online order offer</p>
                <p class="mt-1 text-xs text-[#686868]">Money off a customer’s first order. Only customers with no previous order qualify; we check by account and email at checkout.</p>
            </button>
            <p class="md:col-span-2 text-[11px] text-[#686868]">Coming next: spend $X get $Y off, bundles and win-back codes for lapsed customers.</p>
            <div><button type="button" wire:click="back" class="text-xs font-bold underline">Cancel</button></div>
        </div>
    @endif

    {{-- Step: fill the required fields --}}
    @if ($step === 'form')
        <form wire:submit="preview" class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs p-4 sm:p-6 space-y-5">
            <p class="text-xs font-bold uppercase tracking-wider text-[#686868]">{{ Promotion::TEMPLATES[$template] }}</p>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="p-name" class="{{ $label }}">Name</label>
                    <input id="p-name" type="text" wire:model="name" class="{{ $input }}" placeholder="{{ $firstOrder ? 'Welcome $5 off' : 'Jollof week' }}">
                    @error('name') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    <p class="{{ $help }}">Shoppers see this name in their order summary.</p>
                </div>
                <div>
                    <label for="p-objective" class="{{ $label }}">Objective</label>
                    <select id="p-objective" wire:model="objective" class="{{ $input }}">
                        @foreach (Promotion::OBJECTIVES as $value => $text)
                            <option value="{{ $value }}">{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="p-type" class="{{ $label }}">Discount</label>
                    <select id="p-type" wire:model.live="discountType" class="{{ $input }}">
                        <option value="percent">Percent off</option>
                        <option value="fixed">Dollars off</option>
                    </select>
                </div>
                <div>
                    <label for="p-value" class="{{ $label }}">{{ $discountType === 'percent' ? 'Percent (1–90)' : 'Dollars off' }}{{ $firstOrder ? '' : ' per item' }}</label>
                    <input id="p-value" type="text" inputmode="decimal" wire:model="discountValue" class="{{ $input }}" placeholder="{{ $discountType === 'percent' ? '10' : '5.00' }}">
                    @error('discountValue') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="p-audience" class="{{ $label }}">Audience</label>
                    @if ($firstOrder)
                        <p class="px-3 py-2.5 rounded-xl bg-[#F6F6F6] text-sm">First-time customers</p>
                    @else
                        <select id="p-audience" wire:model="audience" class="{{ $input }}">
                            @foreach (Promotion::AUDIENCES as $value => $text)
                                <option value="{{ $value }}">{{ $text }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            @unless ($firstOrder)
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="p-search" class="{{ $label }}">Products</label>
                        <input id="p-search" type="search" wire:model.live.debounce.300ms="productSearch" class="{{ $input }}" placeholder="Search products to add">
                        @if ($searchResults->isNotEmpty())
                            <ul class="mt-1 rounded-xl border border-[#EEEEEE] divide-y divide-[#EEEEEE]">
                                @foreach ($searchResults as $product)
                                    <li><button type="button" wire:click="addProduct({{ $product->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-[#F6F6F6]">+ {{ $product->name }}</button></li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($chosenProducts as $product)
                                <span wire:key="chosen-{{ $product->id }}" class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 rounded-full bg-[#F6F6F6] text-xs font-bold">
                                    {{ $product->name }}
                                    <button type="button" wire:click="removeProduct({{ $product->id }})" class="size-5 rounded-full hover:bg-[#EEEEEE]" aria-label="Remove {{ $product->name }}">×</button>
                                </span>
                            @endforeach
                        </div>
                        @error('productIds') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="p-collection" class="{{ $label }}">Or a whole collection</label>
                        <select id="p-collection" wire:model="collectionId" class="{{ $input }}">
                            <option value="">No collection</option>
                            @foreach ($collections as $collection)
                                <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endunless

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="p-start" class="{{ $label }}">Starts</label>
                    <input id="p-start" type="datetime-local" wire:model="startsAt" class="{{ $input }}">
                    @error('startsAt') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="p-end" class="{{ $label }}">Ends (required)</label>
                    <input id="p-end" type="datetime-local" wire:model="endsAt" class="{{ $input }}">
                    @error('endsAt') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="p-min" class="{{ $label }}">Minimum basket ($, optional)</label>
                    <input id="p-min" type="text" inputmode="decimal" wire:model="minSubtotal" class="{{ $input }}" placeholder="25.00">
                    @error('minSubtotal') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="p-limit" class="{{ $label }}">Limit per customer</label>
                    <input id="p-limit" type="text" inputmode="numeric" wire:model="perCustomerLimit" class="{{ $input }}" placeholder="No limit">
                    @error('perCustomerLimit') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    <p class="{{ $help }}">Orders per customer that can use it.</p>
                </div>
                <div>
                    <label for="p-cap" class="{{ $label }}">Budget cap (optional)</label>
                    <input id="p-cap" type="text" inputmode="numeric" wire:model="maxRedemptions" class="{{ $input }}" placeholder="No cap">
                    @error('maxRedemptions') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    <p class="{{ $help }}">Most orders it can be used on in total.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" style="background-color: #DF2600;" class="px-5 py-2.5 rounded-xl text-white text-sm font-extrabold hover:opacity-90">Preview</button>
                <button type="button" wire:click="back" class="text-xs font-bold underline">Cancel</button>
            </div>
        </form>
    @endif

    {{-- Step: preview, then activate --}}
    @if ($step === 'preview' && $preview)
        <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs p-4 sm:p-6 space-y-5">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[#686868]">Preview</p>
                <h3 class="mt-1 text-lg font-extrabold text-[#111111]">{{ $name }}</h3>
                <p class="text-xs text-[#686868]">{{ Promotion::TEMPLATES[$template] }} · {{ Promotion::OBJECTIVES[$objective] }} · {{ \Carbon\Carbon::parse($startsAt)->format('j M, g:i A') }} → {{ \Carbon\Carbon::parse($endsAt)->format('j M, g:i A') }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="p-4 rounded-xl bg-[#F6F6F6]">
                    <p class="text-xs font-bold text-[#686868]">Who is eligible</p>
                    <p class="mt-1 text-2xl font-extrabold text-[#111111]">{{ number_format($preview['eligible']) }}</p>
                    <p class="text-xs text-[#686868]">{{ $preview['audienceLabel'] }} with an account today{{ $audience === 'everyone' && ! $firstOrder ? ', plus guests' : '' }}.</p>
                    @if ($preview['productCount'] !== null)
                        <p class="mt-1 text-xs text-[#686868]">Covers {{ $preview['productCount'] }} {{ str('product')->plural($preview['productCount']) }}.</p>
                    @endif
                </div>
                @if ($preview['example'])
                    <div class="p-4 rounded-xl bg-[#DAF4E7]">
                        <p class="text-xs font-bold text-[#007D49]">Example price</p>
                        <p class="mt-1 text-sm text-[#111111]">{{ $preview['example']['label'] }}</p>
                        <p class="mt-1 text-2xl font-extrabold text-[#111111]">
                            <s class="text-base text-[#686868] font-bold">{{ money($preview['example']['before']) }}</s> {{ money($preview['example']['after']) }}
                        </p>
                    </div>
                @endif
            </div>

            <p class="text-[11px] text-[#686868]">
                {{ $perCustomerLimit !== '' ? $perCustomerLimit.' per customer' : 'No limit per customer' }} ·
                {{ $maxRedemptions !== '' ? 'capped at '.$maxRedemptions.' orders' : 'no budget cap' }}
                {{ $minSubtotal !== '' ? '· minimum basket $'.$minSubtotal : '' }}.
                It won’t combine with promo or reward codes; shoppers get whichever saves more.
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" wire:click="save(true)" style="background-color: #DF2600;" class="px-5 py-2.5 rounded-xl text-white text-sm font-extrabold hover:opacity-90">Activate</button>
                <button type="button" wire:click="save(false)" class="px-5 py-2.5 rounded-xl border border-[#EEEEEE] text-sm font-extrabold hover:bg-[#F6F6F6]">Save as draft</button>
                <button type="button" wire:click="back" class="text-xs font-bold underline">Back to edit</button>
            </div>
        </div>
    @endif
</div>
