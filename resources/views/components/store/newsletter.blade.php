<section class="bg-brand-yellow">
    <div class="flex flex-col gap-6 px-4 py-8 md:px-[30px] md:py-10 lg:flex-row lg:items-center lg:justify-between lg:py-12">
        <div class="flex flex-col gap-1">
            <p class="text-[10px] leading-[13px] font-black tracking-[1px] uppercase text-ink">Recipes, offers &amp; community</p>
            <h2 class="pt-0.5 text-[24px] leading-[30px] font-bold tracking-tight text-ink sm:text-[28px] sm:leading-[34px] md:text-[36px] md:leading-[44px] lg:text-[42px] lg:leading-[52px]">Your pantry misses you already.</h2>
            <p class="text-[12px] leading-4 text-ink/80">Fresh finds and good ideas, straight to your inbox.</p>
        </div>

        <form action="#" method="POST" class="flex w-full flex-col gap-3 lg:w-[520px]">
            @csrf
            <div class="flex flex-col gap-2.5 sm:flex-row sm:gap-3">
                <label for="newsletter-name" class="sr-only">Full Name</label>
                <input id="newsletter-name" type="text" name="name" required placeholder="Full Name"
                       class="h-[46px] w-full shrink-0 rounded-full border border-grayscale-30 bg-surface px-5 text-[14px] text-ink placeholder:text-grayscale-60 focus:border-ink focus:outline-none sm:w-[200px]">

                <label for="newsletter-email" class="sr-only">Your email address</label>
                <input id="newsletter-email" type="email" name="email" required placeholder="Your email address"
                       class="h-[46px] w-full shrink-0 rounded-full border border-grayscale-30 bg-surface px-5 text-[14px] text-ink placeholder:text-grayscale-60 focus:border-ink focus:outline-none sm:flex-1">
            </div>

            <button type="submit" class="flex h-[46px] w-full shrink-0 items-center justify-center gap-2 rounded-full bg-ink px-6 text-[14px] leading-[18px] font-extrabold text-white transition-colors hover:bg-grayscale-90 active:scale-[0.99]">
                Join
                <x-store.icon name="arrow-right" class="size-4" />
            </button>
        </form>
    </div>
</section>
