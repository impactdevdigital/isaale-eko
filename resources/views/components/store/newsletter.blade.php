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

        <!-- Form (Figma: max-width 520px, gap 16px, inputs height 42px rounded 4px border #DEDEDE) -->
        <form action="#" method="POST" class="w-full lg:max-w-[520px] space-y-2">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="newsletter-first-name" class="sr-only">First Name</label>
                    <input id="newsletter-first-name" type="text" name="first_name" required placeholder="First Name"
                           class="h-[42px] w-full rounded-[4px] border border-[#DEDEDE] bg-white px-4 text-[14px] leading-[20px] text-[#111111] placeholder:text-[#111111] focus:outline-none focus:border-[#111111] transition-colors">
                </div>
                <div>
                    <label for="newsletter-last-name" class="sr-only">Last Name</label>
                    <input id="newsletter-last-name" type="text" name="last_name" required placeholder="Last Name"
                           class="h-[42px] w-full rounded-[4px] border border-[#DEDEDE] bg-white px-4 text-[14px] leading-[20px] text-[#111111] placeholder:text-[#111111] focus:outline-none focus:border-[#111111] transition-colors">
                </div>
            </div>

            <div>
                <label for="newsletter-email" class="sr-only">Your email address</label>
                <input id="newsletter-email" type="email" name="email" required placeholder="Your email address"
                       class="h-[42px] w-full rounded-[4px] border border-[#DEDEDE] bg-white px-4 text-[14px] leading-[20px] text-[#111111] placeholder:text-[#111111] focus:outline-none focus:border-[#111111] transition-colors">
            </div>

            <!-- Submit Button (Figma: height 42px, rounded 99px, bg #111111, 14px/18px font-extrabold text-white) -->
            <button type="submit" class="h-[42px] w-full rounded-full bg-[#111111] text-white font-extrabold text-[14px] leading-[18px] hover:bg-grayscale-90 transition-colors shadow-2xs">
                Join
            </button>

            <!-- Disclaimer (Figma: 11px/14px, font-normal, text #686868) -->
            <p class="text-[11px] leading-[14px] text-[#686868] pt-1">
                By subscribing you agree to our <a href="{{ route('privacy-policy') }}" class="underline hover:text-ink">Terms &amp; Conditions</a> and <a href="{{ route('privacy-policy') }}" class="underline hover:text-ink">Privacy &amp; Cookies Policy</a>.
            </p>
        </form>
    </div>
</section>
