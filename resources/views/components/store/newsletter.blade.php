<section class="bg-brand-yellow">
    <div class="flex flex-col gap-6 px-4 py-10 md:px-[30px] lg:flex-row lg:items-center lg:justify-between lg:py-[52px]">
        <div class="flex flex-col gap-0.5 pt-1.5">
            <p class="text-[10px] leading-[13px] font-black tracking-[1px] uppercase">Recipes, offers &amp; community</p>
            <h2 class="pt-1 text-[32px] leading-[40px] font-bold tracking-[-1.4px] md:text-[42px] md:leading-[55px] md:tracking-[-1.8px]">Your pantry misses you already.</h2>
            <p class="text-[12px] leading-4">Fresh finds and good ideas, straight to your inbox.</p>
        </div>

        <form action="#" class="flex h-12 w-full overflow-hidden rounded-sm border border-ink lg:w-[520px]">
            <label for="newsletter-email" class="sr-only">Email address</label>
            <input id="newsletter-email" type="email" name="email" required placeholder="Your email address"
                   class="min-w-0 flex-1 bg-surface px-4 text-[16px] leading-[21px] placeholder:text-[#757575] focus:outline-none">
            <button type="submit" class="flex items-center gap-2 bg-ink px-[26px] text-[16px] leading-[21px] font-extrabold text-white transition-colors hover:bg-grayscale-90">
                Join
                <x-store.icon name="arrow-right" />
            </button>
        </form>
    </div>
</section>
