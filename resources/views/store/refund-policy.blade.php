<x-layouts::store :title="'Returns, Refunds & Order Issues Policy'">
    <div class="page-container py-8 md:py-12">
        <nav aria-label="Breadcrumb" class="mb-6">
            <ol class="flex items-center gap-2 text-body-xs text-body-muted">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-ink">Home</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-bold text-ink" aria-current="page">Refund Policy</li>
            </ol>
        </nav>

        <header class="mb-10 max-w-3xl border-b border-grayscale-20 pb-6">
            <h1 class="text-[32px] font-bold tracking-tight text-ink md:text-[44px]">Returns, Refunds &amp; Order Issues Policy</h1>
            <p class="mt-2 text-body-sm text-body-muted">Last updated: October 2026 &middot; &Igrave;s&agrave;l&egrave;&egrave; &Egrave;k&oacute;, Saint John, NB, Canada</p>
            <p class="mt-3 text-body-sm text-body-muted italic">This policy applies to orders placed online. Purchases made in the store are handled at the store.</p>
        </header>

        <article class="max-w-3xl text-body text-ink/90 leading-relaxed space-y-8">
            <section class="rounded-xl border border-grayscale-30 bg-grayscale-0 p-5 text-body-sm leading-relaxed">
                <p>At <strong>&Igrave;s&agrave;l&egrave;&egrave; &Egrave;k&oacute;</strong>, we want you to receive exactly what you ordered, in good condition, and with the quality you expect.</p>
                <p class="mt-2">Because we sell food and other products that may be perishable or unsuitable for resale once they leave our store, we do not accept returns or refunds simply because you changed your mind. However, if there is a problem with your order or a product you received, we will work with you to find a fair resolution.</p>
            </section>

            <!-- 1. How long you have to report an issue -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">1. How long you have to report an issue</h2>
                <p>Please report any problem with your order within the window below. The time is counted from when your order is marked as delivered by our driver, or marked as collected if you pick it up.</p>

                <div class="overflow-x-auto my-4 rounded-sm border border-grayscale-30">
                    <table class="w-full text-left text-body-sm">
                        <thead class="bg-grayscale-0 font-bold border-b border-grayscale-30 text-ink">
                            <tr>
                                <th scope="col" class="px-4 py-3">Product type</th>
                                <th scope="col" class="px-4 py-3">Report the issue within</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-grayscale-30">
                            <tr>
                                <td class="px-4 py-3 font-medium">Food and perishable products</td>
                                <td class="px-4 py-3"><strong>24 hours</strong></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium">Non-perishable groceries</td>
                                <td class="px-4 py-3"><strong>24 hours</strong></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium">Non-food merchandise</td>
                                <td class="px-4 py-3"><strong>7 days</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-body-sm mt-3">Reporting promptly lets us check the product, packaging and order details while they can still be verified. Your order page shows the exact deadline for each item.</p>
                <p class="text-body-sm mt-2">If you believe the delivery time recorded for your order is wrong, contact us and we will review it.</p>
                <p class="text-body-sm mt-2">If the reporting window has closed, please contact us. We may still review your issue case by case. Nothing in this policy limits any rights you have under applicable law (see Section 10).</p>

                <div class="mt-4 rounded-lg border border-price-20 bg-sale-10/40 p-4 text-body-sm text-price-50">
                    <p class="font-bold">Food safety or allergen concerns:</p>
                    <p class="mt-1">Should be reported immediately by phone or WhatsApp at <a href="tel:+15063337474" class="font-bold underline text-price-60">+1 506-333-7474</a>, rather than through the standard reporting process.</p>
                </div>
            </section>

            <!-- 2. Food and edible products -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">2. Food and edible products</h2>
                <p>Food and edible products are not eligible for change-of-mind returns, refunds or exchanges. For example, we cannot provide a refund simply because:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1.5 text-body-sm">
                    <li>you no longer want the product;</li>
                    <li>you bought the wrong item;</li>
                    <li>you changed your mind about the brand or flavour; or</li>
                    <li>you decided you would prefer a different product after receiving your order.</li>
                </ul>
                <p class="text-body-sm mt-3">This helps us protect food safety and maintain the quality and traceability of what we sell.</p>
            </section>

            <!-- 3. When we can make it right -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">3. When we can make it right</h2>
                <p>If there is a genuine problem with an item or with how your order was fulfilled, you may be eligible for a refund, replacement or exchange. This includes situations where:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1.5 text-body-sm">
                    <li>you received the wrong product or the wrong quantity;</li>
                    <li>an item was missing from your order;</li>
                    <li>an item arrived damaged;</li>
                    <li>an item is spoiled, defective or otherwise not fit for its intended use;</li>
                    <li>the product does not match what you ordered;</li>
                    <li>your order did not arrive within the delivery timeframe confirmed for your order, or did not arrive at all; or</li>
                    <li>there was another fulfillment error on our part.</li>
                </ul>
                <p class="mt-4">We will offer the resolution options available for your issue, and you can choose the one you prefer. Where a replacement is not possible, we will offer an exchange or refund, as appropriate.</p>

                <div class="mt-4 space-y-4">
                    <div class="rounded-lg border border-grayscale-30 p-4">
                        <h3 class="font-bold text-ink">Refund</h3>
                        <p class="mt-1 text-body-sm">We will refund the affected item to the original payment method used for the order. A refund is initiated when we have sent the refund request to our payment provider, and processed when the provider has completed it. Once processed, the time it takes for funds to appear in your account depends on your bank or payment provider, and we cannot control it.</p>
                    </div>

                    <div class="rounded-lg border border-grayscale-30 p-4">
                        <h3 class="font-bold text-ink">Replacement</h3>
                        <p class="mt-1 text-body-sm">Where available, we will replace the affected item with the same product. If the same product is unavailable, we will offer an exchange or a refund instead.</p>
                    </div>

                    <div class="rounded-lg border border-grayscale-30 p-4">
                        <h3 class="font-bold text-ink">Exchange</h3>
                        <p class="mt-1 text-body-sm">You can exchange the affected item for another eligible product that is available when the exchange is processed. If the new item costs more, we will charge you the difference. If it costs less, we will refund the difference. If you would rather not pay the difference, a refund is available instead.</p>
                    </div>
                </div>

                <div class="mt-4 rounded-lg bg-grayscale-0 border border-grayscale-20 p-4 text-body-sm">
                    <p class="font-bold text-ink">Problem items and returns</p>
                    <p class="mt-1">For issues with damaged, spoiled or incorrect food, we will not usually ask you to send the item back. We may ask for photos, or advise you to keep or dispose of the item. Please do not dispose of a product involved in a quality or safety complaint unless our team tells you to.</p>
                </div>
            </section>

            <!-- 4. Substitutions for unavailable items -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">4. Substitutions for unavailable items</h2>
                <p>Sometimes an item becomes unavailable while we are picking your order. We do not make silent substitutions.</p>
                <p class="mt-2 text-body-sm">If an item is unavailable, we will first try to reach you by phone call during the fulfilment process. If we cannot reach you, we will send you an email or SMS. If we do not receive a response within <strong>15 minutes</strong>, or before the order moves into its delivery/collection stage, whichever comes first, we may proceed with a refund for the unavailable item.</p>
                <p class="mt-2 text-body-sm">If you approve a substitute, we will add it to your order. We will show you its price before you approve. If it costs more than the original item, we will charge you the difference. If it costs less, we will refund the difference.</p>
                <p class="mt-2 text-body-sm">If you do not want a substitute, we will remove the item and initiate a refund for it.</p>
                <p class="mt-2 text-body-sm">If we cannot reach you at that time, we will proceed with the rest of your order and initiate a refund for the unavailable item.</p>
                <p class="mt-2 text-body-sm">If the missing item materially affects the rest of your order, we may contact you again before completing it.</p>
            </section>

            <!-- 5. If you approved a substitution -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">5. If you approved a substitution</h2>
                <p>Once you approve a substitution, the substitute becomes the product you agreed to receive. Deciding after delivery that you would have preferred another brand or product is treated as a change of mind and is not eligible for a refund or exchange.</p>
                <p class="mt-2 text-body-sm">If the approved substitute itself is damaged, defective, spoiled or incorrect, please report it and we will assess it under this policy.</p>
            </section>

            <!-- 6. How to report an issue -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">6. How to report an issue</h2>
                <p>In the app or on our website: <strong>My Orders &rarr; Select Order &rarr; Report a Problem with This Order</strong></p>
                <p class="mt-2 text-body-sm">Without an account, or if you prefer, send an email to <a href="mailto:support@isaleeeko.com" class="font-bold underline text-ink hover:text-brand-yellow-80">support@isaleeeko.com</a> or message us on WhatsApp at <a href="https://wa.me/15063337474" class="font-bold underline text-ink hover:text-brand-yellow-80">+1 506-333-7474</a>, including your order number.</p>
                <p class="mt-3 text-body-sm">You may be asked to provide:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1.5 text-body-sm">
                    <li>your order number;</li>
                    <li>the affected product;</li>
                    <li>a description of the problem;</li>
                    <li>photographs of the product, packaging, label or damage where relevant; and</li>
                    <li>any other information reasonably needed to investigate.</li>
                </ul>
                <p class="mt-3 text-body-sm">We aim to respond within <strong>24 hours</strong>. Please do not return or dispose of a product involved in a quality or safety complaint unless our team advises you to.</p>
            </section>

            <!-- 7. Refund processing -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">7. Refund processing</h2>
                <p>When a refund is approved, we will initiate it through the payment method used for the original transaction. Online orders are paid by card or other online payment methods, so refunds always go back to that same method. <strong>We do not offer store credit.</strong></p>
                <p class="mt-2 text-body-sm">Our confirmation will tell you whether the refund has been initiated or processed, using the meanings in Section 3. We cannot guarantee when funds will appear in your account, as timing varies by payment provider and financial institution.</p>
            </section>

            <!-- 8. Non-food products and returns -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">8. Non-food products and returns</h2>
                <p>Unless a product or category page says otherwise, eligible non-food items may be returned or exchanged for change of mind within <strong>7 days</strong> of delivery or collection, if they are unused, in their original packaging, and accompanied by proof of purchase. To start a return, use <strong>My Orders &rarr; Select Order &rarr; Return an Item</strong>, or contact us.</p>
                <p class="mt-2 text-body-sm">For a change-of-mind return, you can either pay for the return shipping or bring the item to our store. Once we have received and checked the item, you can choose a refund or an exchange, and exchanges work as described in Section 3.</p>
                <p class="mt-2 text-body-sm">If a non-food item arrived damaged, defective or incorrect, that is not a change-of-mind return. Report it under Section 3 and we will cover any cost of returning it.</p>
            </section>

            <!-- 9. Late and undelivered orders -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">9. Late and undelivered orders</h2>
                <p>If your order has not arrived, or arrived much later than we confirmed, use <strong>Report a Problem with This Order</strong>. We will look into what happened and offer a resolution, which may include a refund of the affected items or the delivery fee.</p>
            </section>

            <!-- 10. Your legal rights -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">10. Your legal rights</h2>
                <p>This policy describes our ordinary process for returns, refunds, exchanges and order issues. It does not remove or limit any consumer rights, cancellation rights or remedies that apply under Canadian federal or provincial law, including any cancellation rights that apply to internet or distance-sale contracts in your province.</p>
                <p class="mt-2 text-body-sm">Where applicable law gives you a right that differs from this policy, the law prevails.</p>
            </section>

            <!-- 11. Our commitment -->
            <section>
                <h2 class="text-[20px] font-bold text-ink mb-3">11. Our commitment</h2>
                <p>We will assess every order issue fairly, based on the circumstances of each case. If something went wrong with your order, please tell us. We would rather understand what happened and make it right than leave you with a poor experience.</p>
            </section>

            <!-- Contact Box -->
            <section class="mt-10 rounded-xl border border-grayscale-30 bg-grayscale-0 p-6 text-body-sm">
                <h3 class="font-bold text-ink text-[16px] mb-2">&Igrave;s&agrave;l&egrave;&egrave; &Egrave;k&oacute; Customer Service</h3>
                <p class="text-body-muted">Saint John, New Brunswick, Canada</p>
                <div class="mt-3 space-y-1.5">
                    <p><span class="font-semibold text-ink">Email:</span> <a href="mailto:support@isaleeeko.com" class="underline text-brand-blue hover:text-ink">support@isaleeeko.com</a></p>
                    <p><span class="font-semibold text-ink">Phone &amp; WhatsApp:</span> <a href="https://wa.me/15063337474" class="underline text-brand-blue hover:text-ink">+1 506-333-7474</a></p>
                    <p class="text-body-xs text-body-muted mt-2">Response target: within 24 hours (immediate for food safety/allergens)</p>
                </div>
            </section>
        </article>
    </div>
</x-layouts::store>
