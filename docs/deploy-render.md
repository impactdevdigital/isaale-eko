# Deploying to Render

The repo contains everything Render needs:

- `Dockerfile` builds the app (PHP 8.4 + Nginx, port 8080, front-end assets built inside).
- `render.yaml` is a Render Blueprint that creates:
  - **isaale-eko-db**: Postgres (Basic 256 MB, about $6/month)
  - **isalee-eko**: the website (Free for now; Starter, $7/month, for the real shop)
  - **isaale-eko-worker**: background jobs + scheduler, incl. the 5-minute Square sync
    (Starter, $7/month). Commented out in `render.yaml` while on the free plan,
    because Render has no free workers.

Every push to the deployed branch redeploys automatically.

## 1. Before you start

1. Merge `storefront-ui` into `main` (Render deploys `main`).
2. Generate an app key on your computer and keep it somewhere safe:
   ```
   C:\php8.4\php.exe -d extension_dir=C:/php8.4/ext -d extension=mbstring -d extension=openssl artisan key:generate --show
   ```
3. Have your Square values ready (sandbox first is fine): access token, location ID, application ID, and the `SQUARE_VERSION` from your `.env`.

## 2. Create everything

1. Render Dashboard → **New → Blueprint** → connect GitHub → pick this repository.
2. Render reads `render.yaml` and asks for the `sync: false` values:
   - `APP_KEY`: the key from step 1.2 (starts with `base64:`)
   - `APP_URL`: leave as the `https://isalee-eko.onrender.com` address for now
   - Square values; leave the two webhook values empty for now
   - `MAIL_FROM_ADDRESS`: e.g. `orders@yourdomain.ca`
3. Click **Apply**. The first build takes ~5–10 minutes.

## 3. First-time setup (automatic)

Every time the website container starts it:

1. runs the database migrations,
2. runs `php artisan shop:install`, which adds opening hours and payment methods
   only if they're missing, and runs the first Square import if no products are linked yet.

This works on Render's free plan too (no Shell or pre-deploy step needed).
Do not run `DemoCatalogSeeder` on the live site; products come from Square.

On paid plans you can also run commands yourself in **isalee-eko → Shell**.

## 4. Square webhook (after the site is up)

1. Square Developer Dashboard → your app → **Webhooks** → Add subscription.
2. URL: `https://<your domain>/api/webhooks/square`
3. Events: `payment.created`, `payment.updated`, `catalog.version.updated`, `inventory.count.updated`.
4. Copy the **signature key** into Render (`SQUARE_WEBHOOK_SIGNATURE_KEY` in the
   `isaale-eko-shared` group) and set `SQUARE_WEBHOOK_URL` to exactly the URL above.

## 5. Your domain

isalee-eko → **Settings → Custom Domains** → add the domain and follow the DNS
instructions. HTTPS is automatic. Then update `APP_URL` (and the webhook URL) to the new address.

## 6. Going live with real payments

In the `isaale-eko-shared` environment group:

- `SQUARE_ENV` → `production`
- `SQUARE_ACCESS_TOKEN`, `SQUARE_LOCATION_ID`, `SQUARE_APP_ID` → production values
- email via Resend: `MAIL_MAILER=resend`, `RESEND_API_KEY=re_...`. Before the domain is verified in Resend, use `MAIL_FROM_ADDRESS=onboarding@resend.dev` (only delivers to the Resend account owner). After verifying the domain, set `MAIL_FROM_ADDRESS=orders@<domain>`.

Then in the Shell: `php artisan square:sync --full --retire-demo`.

Before announcing: the `/admin` pages have **no login yet** and must be protected first.

## Troubleshooting

- **A 500 error with nothing in the logs:** set `APP_DEBUG=true` on the web service
  (Environment), reload the page to read the error, then set it back to `false`.
- **Free plan:** no Shell, no pre-deploy step, and no background worker, so the
  5-minute Square sync and background emails don't run. The site sleeps after 15
  minutes without visitors. Use Starter plans for the real shop.
- **Logs:** each service → **Logs**. If `MAIL_MAILER=log`, sign-in codes appear there instead of being emailed.
- **Health check:** `https://<your domain>/up` should say the app is up.
- **Sync by hand:** Shell → `php artisan square:sync`.
