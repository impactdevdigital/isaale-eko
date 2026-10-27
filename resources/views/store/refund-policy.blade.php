<x-layouts::store :title="'Refund Policy'">
    <div class="page-container py-8 md:py-12">
        <nav aria-label="Breadcrumb" class="mb-6">
            <ol class="flex items-center gap-2 text-body-xs text-body-muted">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-ink">Home</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-bold text-ink" aria-current="page">Refund Policy</li>
            </ol>
        </nav>

        <header class="mb-10 max-w-3xl border-b border-grayscale-20 pb-6">
            <h1 class="text-[32px] font-bold tracking-tight text-ink md:text-[44px]">Refund &amp; Returns Policy</h1>
            <p class="mt-2 text-body-sm text-body-muted">Last updated: October 2026 · Ìsàlèè Èkó, Saint John, NB, Canada</p>
        </header>

        <article class="prose prose-neutral max-w-3xl text-body text-ink/90 leading-relaxed space-y-8">
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">1. Freshness &amp; Authenticity Guarantee</h2>
                <p>At Ìsàlèè Èkó, we take pride in delivering the highest quality African and Caribbean groceries. If an item you received does not meet our high standards for freshness, authenticity, or condition, we are here to make it right.</p>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">2. Perishable &amp; Fresh Goods</h2>
                <p>Due to health and safety standards, fresh produce, vegetables, baked goods, meat, fish, and frozen goods are inspected carefully before delivery. If any perishable item arrives damaged or unsatisfactory:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1.5 text-body-sm">
                    <li>Please inspect your groceries upon receipt.</li>
                    <li>Notify us within <strong>24 hours</strong> of delivery or store collection.</li>
                    <li>Attach a clear photo of the affected item so our team can assess the issue.</li>
                    <li>We will issue an immediate refund or replacement on your next order.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">3. Non-Perishable Pantry Items</h2>
                <p>Unopened shelf-stable items (such as flours, grains, spices, canned goods, dried fish/crayfish, and bottled sauces) may be returned within <strong>14 days</strong> of purchase, provided the packaging is unopened, undamaged, and with original seals intact.</p>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">4. Missing, Incorrect, or Substitute Items</h2>
                <p>If an item was omitted from your order or an unapproved substitution was made, please report it immediately through your <a href="{{ route('account.orders') }}" class="font-bold underline text-ink hover:text-brand-yellow-80">Order History</a>. We will promptly refund the missing item to your original payment method.</p>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">5. Refund Processing Time</h2>
                <p>Approved refunds are processed to your original payment method (Credit Card, Debit, or Interac) within <strong>3 to 5 business days</strong>, depending on your financial institution. Store credit can also be credited instantly to your account upon request.</p>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">6. Contact Support</h2>
                <p>To request a refund or report a damaged item, please contact our Saint John team:</p>
                <div class="mt-3 rounded-xl border border-grayscale-20 bg-grayscale-0 p-4 text-body-sm">
                    <p class="font-bold text-ink">Ìsàlèè Èkó Customer Service</p>
                    <p class="text-body-muted">Saint John, New Brunswick, Canada</p>
                    <p class="mt-1 text-body-muted">Email: <a href="mailto:support@isaleeeko.ca" class="underline hover:text-ink">support@isaleeeko.ca</a></p>
                </div>
            </section>
        </article>
    </div>
</x-layouts::store>
