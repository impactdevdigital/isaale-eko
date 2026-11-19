<section class="border-t border-[#DEDEDE] bg-[#F6F6F6]">
    <div class="page-container pt-[52px] pb-[52px] flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8 lg:gap-12 border-b border-[#DEDEDE]">
        <div class="max-w-[776px] space-y-2">
            <!-- Eyebrow (Figma: uppercase, 10px/13px, font-black, letter-spacing 1px, #111111) -->
            <span class="block text-[10px] leading-[13px] font-black uppercase tracking-[1px] text-[#111111]">
                Recipes, offers &amp; community
            </span>
            <!-- Heading (Figma: 42px/55px, font-bold, letter-spacing -1.8px, #111111) -->
            <h2 class="text-[28px] sm:text-[34px] lg:text-[42px] leading-[1.1] lg:leading-[55px] font-bold tracking-[-1.8px] text-[#111111]">
                Your pantry misses you already.
            </h2>
            <!-- Subtitle (Figma: 12px/16px, font-normal, #111111) -->
            <p class="text-[12px] leading-[16px] font-normal text-[#111111]">
                Fresh finds and good ideas, straight to your inbox.
            </p>
        </div>

        @if ($subscribed)
            <div class="w-full lg:max-w-[520px] border border-[#DEDEDE] bg-white px-5 py-4" role="status">
                <p class="text-[14px] leading-[20px] font-extrabold text-[#111111]">You’re on the list.</p>
                <p class="mt-1 text-[12px] leading-[16px] text-[#686868]">Look out for fresh finds, recipes and offers in your inbox. You can unsubscribe at any time.</p>
            </div>
        @else
            <!-- Form (Figma: max-width 520px, gap 16px, inputs height 42px, square corners (client request), border #DEDEDE) -->
            <form wire:submit="subscribe" novalidate class="w-full lg:max-w-[520px] space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="newsletter-first-name" class="sr-only">First Name</label>
                        <input id="newsletter-first-name" type="text" wire:model="firstName" autocomplete="given-name" required placeholder="First Name"
                               @error('firstName') aria-invalid="true" aria-describedby="newsletter-first-name-error" @enderror
                               class="h-[42px] w-full rounded-none border border-[#DEDEDE] bg-white px-4 text-[14px] leading-[20px] text-[#111111] placeholder:text-[#111111] focus:outline-none focus:border-[#111111] transition-colors">
                        @error('firstName') <p id="newsletter-first-name-error" class="mt-1 text-[11px] font-bold text-price-40">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="newsletter-last-name" class="sr-only">Last Name</label>
                        <input id="newsletter-last-name" type="text" wire:model="lastName" autocomplete="family-name" required placeholder="Last Name"
                               @error('lastName') aria-invalid="true" aria-describedby="newsletter-last-name-error" @enderror
                               class="h-[42px] w-full rounded-none border border-[#DEDEDE] bg-white px-4 text-[14px] leading-[20px] text-[#111111] placeholder:text-[#111111] focus:outline-none focus:border-[#111111] transition-colors">
                        @error('lastName') <p id="newsletter-last-name-error" class="mt-1 text-[11px] font-bold text-price-40">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="newsletter-email" class="sr-only">Your email address</label>
                    <input id="newsletter-email" type="email" wire:model="email" autocomplete="email" required placeholder="Your email address"
                           @error('email') aria-invalid="true" aria-describedby="newsletter-email-error" @enderror
                           class="h-[42px] w-full rounded-none border border-[#DEDEDE] bg-white px-4 text-[14px] leading-[20px] text-[#111111] placeholder:text-[#111111] focus:outline-none focus:border-[#111111] transition-colors">
                    @error('email') <p id="newsletter-email-error" class="mt-1 text-[11px] font-bold text-price-40">{{ $message }}</p> @enderror
                </div>

                <!-- Submit Button (Figma: height 42px, square corners (client request), bg #111111, 14px/18px font-extrabold text-white) -->
                <button type="submit" wire:loading.attr="disabled" wire:target="subscribe"
                        class="h-[42px] w-full rounded-none bg-[#111111] text-white font-extrabold text-[14px] leading-[18px] hover:bg-grayscale-90 transition-colors shadow-2xs disabled:opacity-60">
                    <span wire:loading.remove wire:target="subscribe">Join</span>
                    <span wire:loading wire:target="subscribe">Joining…</span>
                </button>

                <!-- Disclaimer (Figma: 11px/14px, font-normal, text #686868) -->
                <p class="text-[11px] leading-[14px] text-[#686868] pt-1">
                    By subscribing you agree to our <a href="{{ route('privacy-policy') }}" class="underline hover:text-ink">Terms &amp; Conditions</a> and <a href="{{ route('privacy-policy') }}" class="underline hover:text-ink">Privacy &amp; Cookies Policy</a>.
                </p>
            </form>
        @endif
    </div>
</section>
