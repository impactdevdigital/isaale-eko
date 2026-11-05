<section class="border-t border-grayscale-30 bg-grayscale-10">
    <div class="page-container py-12 md:py-16 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8 lg:gap-12">
        <div class="max-w-xl space-y-2">
            <span class="block text-[11px] font-bold uppercase tracking-widest text-grayscale-80">Recipes, offers &amp; community</span>
            <h2 class="text-[28px] sm:text-[34px] lg:text-[42px] font-bold tracking-tight text-ink leading-tight">Your pantry misses you already.</h2>
            <p class="text-body-sm text-body-muted">Fresh finds and good ideas, straight to your inbox.</p>
        </div>

        <form action="#" method="POST" class="w-full lg:max-w-[480px] space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="newsletter-first-name" class="sr-only">First Name</label>
                    <input id="newsletter-first-name" type="text" name="first_name" required placeholder="First Name"
                           class="h-11 w-full rounded-xl border border-grayscale-30 bg-white px-4 text-body-sm text-ink placeholder:text-grayscale-60 focus:outline-none focus:border-ink transition-colors">
                </div>
                <div>
                    <label for="newsletter-last-name" class="sr-only">Last Name</label>
                    <input id="newsletter-last-name" type="text" name="last_name" required placeholder="Last Name"
                           class="h-11 w-full rounded-xl border border-grayscale-30 bg-white px-4 text-body-sm text-ink placeholder:text-grayscale-60 focus:outline-none focus:border-ink transition-colors">
                </div>
            </div>

            <div>
                <label for="newsletter-email" class="sr-only">Your email address</label>
                <input id="newsletter-email" type="email" name="email" required placeholder="Your email address"
                       class="h-11 w-full rounded-xl border border-grayscale-30 bg-white px-4 text-body-sm text-ink placeholder:text-grayscale-60 focus:outline-none focus:border-ink transition-colors">
            </div>

            <button type="submit" class="h-11 w-full rounded-full bg-ink text-white font-bold text-button-sm hover:bg-grayscale-90 transition-colors shadow-2xs">
                Join
            </button>

            <p class="text-[11px] text-body-muted leading-relaxed">
                By subscribing you agree to our <a href="{{ route('privacy-policy') }}" class="underline hover:text-ink">Terms &amp; Conditions</a> and <a href="{{ route('privacy-policy') }}" class="underline hover:text-ink">Privacy &amp; Cookies Policy</a>.
            </p>
        </form>
    </div>
</section>
