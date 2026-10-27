<x-layouts::store :title="'Privacy Policy'">
    <div class="page-container py-8 md:py-12">
        <nav aria-label="Breadcrumb" class="mb-6">
            <ol class="flex items-center gap-2 text-body-xs text-body-muted">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-ink">Home</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-bold text-ink" aria-current="page">Privacy Policy</li>
            </ol>
        </nav>

        <header class="mb-10 max-w-3xl border-b border-grayscale-20 pb-6">
            <h1 class="text-[32px] font-bold tracking-tight text-ink md:text-[44px]">Privacy Policy</h1>
            <p class="mt-2 text-body-sm text-body-muted">Last updated: October 2026 · Ìsàlèè Èkó, Saint John, NB, Canada</p>
        </header>

        <article class="prose prose-neutral max-w-3xl text-body text-ink/90 leading-relaxed space-y-8">
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">1. Information We Collect</h2>
                <p>When you visit Ìsàlèè Èkó, place an order, or create an account, we collect information necessary to fulfill your groceries and provide a tailored shopping experience:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1.5 text-body-sm">
                    <li><strong>Contact details:</strong> Full name, email address, phone number, and delivery address.</li>
                    <li><strong>Order &amp; Transaction details:</strong> Items purchased, order notes, fulfilment preferences (delivery or pickup), and payment transaction references.</li>
                    <li><strong>Account Preferences:</strong> Dietary preferences, saved delivery addresses, wishlist items, and newsletter subscriptions.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">2. How We Use Your Information</h2>
                <p>We use your personal information to:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1.5 text-body-sm">
                    <li>Process, pack, and deliver your orders accurately to your door in Saint John and surrounding regions.</li>
                    <li>Send transactional notices such as order confirmations, delivery status updates, and verification codes.</li>
                    <li>Assist you through Asha, your grocery concierge, with culinary recommendations and practical shopping lists.</li>
                    <li>Send recipe inspiration, seasonal produce alerts, and community offers (only if you have subscribed to our newsletter).</li>
                </ul>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">3. Payment Security &amp; Data Protection</h2>
                <p>Your payment information is processed through secure, PCI-DSS compliant payment gateways. Ìsàlèè Èkó does not store raw credit card numbers or banking passwords on our servers.</p>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">4. Customer Rights &amp; Data Retention</h2>
                <p>You have the right to access, review, modify, or request the deletion of your personal data at any time through your <a href="{{ route('account') }}" class="font-bold underline text-ink hover:text-brand-yellow-80">Account Dashboard</a> or by reaching out to our support team.</p>
            </section>

            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">5. Contact Us</h2>
                <p>For questions or requests regarding your privacy, please contact:</p>
                <div class="mt-3 rounded-xl border border-grayscale-20 bg-grayscale-0 p-4 text-body-sm">
                    <p class="font-bold text-ink">Ìsàlèè Èkó Customer Support</p>
                    <p class="text-body-muted">Saint John, New Brunswick, Canada</p>
                    <p class="mt-1 text-body-muted">Email: <a href="mailto:support@isaleeeko.ca" class="underline hover:text-ink">support@isaleeeko.ca</a></p>
                </div>
            </section>
        </article>
    </div>
</x-layouts::store>
