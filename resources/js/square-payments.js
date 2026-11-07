/**
 * Square Web Payments for checkout. Card details are typed into Square's own
 * iframe and turned into a one-time token; only the token reaches our server.
 *
 * Used as x-data="squarePayments({...})" inside the checkout Livewire component.
 * The Pay button dispatches "square-pay"; the result goes to $wire.pay(token, attempt).
 */
const SDK_URLS = {
    sandbox: 'https://sandbox.web.squarecdn.com/v1/square.js',
    production: 'https://web.squarecdn.com/v1/square.js',
};

let sdkPromise = null;

function loadSdk(env) {
    sdkPromise ??= new Promise((resolve, reject) => {
        if (window.Square) return resolve(window.Square);

        const script = document.createElement('script');
        script.src = SDK_URLS[env] ?? SDK_URLS.sandbox;
        script.onload = () => resolve(window.Square);
        script.onerror = () => {
            sdkPromise = null;
            reject(new Error('Square could not be loaded.'));
        };
        document.head.appendChild(script);
    });

    return sdkPromise;
}

window.squarePayments = ({ env, appId, locationId, method, amount, currency, country }) => ({
    method,
    ready: false,
    busy: false,
    error: null,
    instance: null,
    walletAvailable: { applePay: false, googlePay: false },

    async init() {
        // Interac e-Transfer is placed without Square; nothing to load.
        if (method !== 'card' && method !== 'wallet') return;

        try {
            const Square = await loadSdk(env);
            const payments = Square.payments(appId, locationId);

            if (method === 'card') {
                this.instance = await payments.card({
                    style: {
                        input: { fontSize: '16px', color: '#111111' },
                        'input::placeholder': { color: '#777777' },
                        '.input-container.is-focus': { borderColor: '#111111' },
                    },
                });
                await this.instance.attach(this.$refs.card);
            } else {
                const request = payments.paymentRequest({
                    countryCode: country,
                    currencyCode: currency,
                    total: { amount: (amount / 100).toFixed(2), label: 'Ìsàlèè Èkó' },
                });

                this.instance = { googlePay: null, applePay: null };

                try {
                    this.instance.googlePay = await payments.googlePay(request);
                    await this.instance.googlePay.attach(this.$refs.googlePay, { buttonColor: 'black', buttonSizeMode: 'fill' });
                    this.walletAvailable.googlePay = true;
                } catch (e) { /* Not supported in this browser. */ }

                try {
                    this.instance.applePay = await payments.applePay(request);
                    this.walletAvailable.applePay = true;
                } catch (e) { /* Needs Safari and a registered domain. */ }

                if (!this.walletAvailable.googlePay && !this.walletAvailable.applePay) {
                    this.error = 'Apple Pay and Google Pay aren’t available in this browser. Choose card instead.';
                }
            }

            this.ready = true;
        } catch (e) {
            this.error = 'Card payments are unavailable right now. Please try again shortly.';
        }
    },

    destroy() {
        if (this.method === 'card') this.instance?.destroy?.();
        else {
            this.instance?.googlePay?.destroy?.();
            this.instance?.applePay?.destroy?.();
        }
    },

    async pay(wallet = null) {
        if (this.busy) return;

        const source = this.method === 'card'
            ? this.instance
            : this.instance?.[wallet ?? (this.walletAvailable.applePay ? 'applePay' : 'googlePay')];

        if (!source) return;

        this.busy = true;
        this.error = null;

        try {
            const result = await source.tokenize();

            if (result.status !== 'OK') {
                this.error = result.errors?.[0]?.message ?? 'Check your card details and try again.';
                this.done();
                return;
            }

            window.dispatchEvent(new CustomEvent('payment-processing', { detail: true }));
            await this.$wire.pay(result.token, crypto.randomUUID());
        } catch (e) {
            this.error = 'Something went wrong. Your card has not been charged — please try again.';
        } finally {
            this.done();
        }
    },

    done() {
        this.busy = false;
        window.dispatchEvent(new CustomEvent('payment-processing', { detail: false }));
    },
});
