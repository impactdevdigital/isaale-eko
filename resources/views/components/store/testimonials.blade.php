<section class="mb-12" aria-labelledby="testimonials-heading">
    <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 items-stretch"
         x-data="{
             current: 0,
             playing: true,
             timer: null,
             reviews: [
                 {
                     quote: 'Food has always been how I show love. It’s how I keep my family close, no matter where we are in the world.',
                     author: 'Rwame Okafor'
                 },
                 {
                     quote: 'Fresh, familiar and always packed with care. Finding genuine ingredients here made all the difference.',
                     author: 'Amara Diallo'
                 },
                 {
                     quote: 'I found all the ingredients I missed from home. Prompt delivery and authentic flavours every time.',
                     author: 'Kofi Mensah'
                 },
                 {
                     quote: 'Reliable delivery and a brilliant range. Everything arrived fresh and beautifully packaged.',
                     author: 'Fatima Al-Hassan'
                 }
             ],
             init() {
                 this.timer = setInterval(() => {
                     if (this.playing) {
                         this.next();
                     }
                 }, 6000);
             },
             togglePlay() {
                 this.playing = !this.playing;
             },
             next() {
                 this.current = (this.current + 1) % this.reviews.length;
             },
             prev() {
                 this.current = (this.current - 1 + this.reviews.length) % this.reviews.length;
             },
             goTo(index) {
                 this.current = index;
             }
         }">
        {{-- Left yellow card --}}
        <div class="flex flex-col justify-between rounded-2xl bg-brand-yellow p-6 sm:p-8 shrink-0 lg:w-[360px] lg:min-h-[296px]">
            <div>
                <p id="testimonials-heading" class="text-[11px] leading-[18px] font-black uppercase tracking-[1px] text-ink">Why customers return</p>
                <h2 class="mt-4 text-[32px] sm:text-[36px] lg:text-[40px] font-bold leading-[38px] lg:leading-[40px] tracking-tight text-ink">A little taste of home, delivered</h2>
            </div>

            <div class="flex -space-x-2.5 pt-6 sm:pt-8" aria-label="Customer community">
                <img class="size-10 rounded-full border-2 border-white object-cover shadow-sm" src="{{ asset('images/store/testimonials/avatar-1.webp') }}" alt="" loading="lazy">
                <img class="size-10 rounded-full border-2 border-white object-cover shadow-sm" src="{{ asset('images/store/testimonials/avatar-2.webp') }}" alt="" loading="lazy">
                <img class="size-10 rounded-full border-2 border-white object-cover shadow-sm" src="{{ asset('images/store/testimonials/avatar-3.webp') }}" alt="" loading="lazy">
                <img class="size-10 rounded-full border-2 border-white object-cover shadow-sm" src="{{ asset('images/store/testimonials/avatar-4.webp') }}" alt="" loading="lazy">
                <img class="size-10 rounded-full border-2 border-white object-cover shadow-sm" src="{{ asset('images/store/testimonials/avatar-5.webp') }}" alt="" loading="lazy">
                <img class="size-10 rounded-full border-2 border-white object-cover shadow-sm" src="{{ asset('images/store/testimonials/avatar-6.webp') }}" alt="" loading="lazy">
            </div>
        </div>

        {{-- Right testimonial quote card --}}
        <div class="flex flex-1 flex-col justify-between rounded-2xl border border-grayscale-30 bg-surface p-6 sm:p-8 lg:px-14 lg:py-10">
            <span class="text-brand-yellow text-[56px] sm:text-[72px] lg:text-[84px] font-bold leading-none select-none" aria-hidden="true">“</span>

            <div class="my-3 flex min-h-[96px] sm:min-h-[120px] items-center">
                <blockquote class="text-[19px] sm:text-[23px] lg:text-[28px] font-bold leading-[27px] sm:leading-[32px] lg:leading-[36px] tracking-tight text-ink transition-opacity duration-200"
                            x-text="reviews[current].quote"></blockquote>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-grayscale-10">
                <p class="text-[12px] font-black uppercase tracking-wider text-ink"
                   x-text="'- ' + reviews[current].author"></p>

                <div class="flex items-center gap-2">
                    <button type="button" @click="prev()"
                            class="flex size-9 items-center justify-center rounded-full border border-grayscale-30 text-ink transition-colors hover:bg-grayscale-10"
                            aria-label="Previous review">
                        <x-store.icon name="arrow-left" class="size-4" />
                    </button>

                    <div class="flex h-9 items-center gap-3 rounded-full border border-grayscale-30 px-3.5">
                        <button type="button" @click="togglePlay()"
                                class="text-ink transition-opacity hover:opacity-70"
                                :aria-label="playing ? 'Pause slideshow' : 'Play slideshow'">
                            <template x-if="playing">
                                <svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="6" y="4" width="4" height="16" />
                                    <rect x="14" y="4" width="4" height="16" />
                                </svg>
                            </template>
                            <template x-if="!playing">
                                <svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                    <polygon points="5 3 19 12 5 21 5 3" />
                                </svg>
                            </template>
                        </button>
                        <span class="h-3 w-px bg-grayscale-30" aria-hidden="true"></span>
                        <div class="flex items-center gap-2">
                            <template x-for="(review, index) in reviews" :key="index">
                                <button type="button" @click="goTo(index)"
                                        :class="current === index ? 'w-6 bg-ink' : 'size-2 bg-grayscale-40 hover:bg-grayscale-60'"
                                        class="h-2 rounded-full transition-all duration-300"
                                        :aria-label="'Go to review ' + (index + 1)"></button>
                            </template>
                        </div>
                    </div>

                    <button type="button" @click="next()"
                            class="flex size-9 items-center justify-center rounded-full border border-grayscale-30 text-ink transition-colors hover:bg-grayscale-10"
                            aria-label="Next review">
                        <x-store.icon name="arrow-right" class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
