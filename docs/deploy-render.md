# Deploying to Render

The repo contains everything Render needs:

- `Dockerfile` builds the app (PHP 8.4 + Nginx, port 8080, front-end assets built inside).
- `render.yaml` is a Render Blueprint that creates:
  - **isaale-eko-db**: Postgres (Basic 256 MB, about $6/month)
  - **isaale-eko**: the website (Starter, $7/month), migrations run before each deploy
  - **isaale-eko-worker**: background jobs + scheduler, incl. the 5-minute Square sync (Starter, $7/month)

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
   - `APP_URL`: leave as the `https://isaale-eko.onrender.com` address for now
   - Square values; leave the two webhook values empty for now
   - `MAIL_FROM_ADDRESS`: e.g. `orders@yourdomain.ca`
3. Click **Apply**. The first build takes ~5–10 minutes.

## 3. First-time setup (once)

Open **isaale-eko → Shell** and run:

```
php artisan db:seed --class=ProductionSeeder --force   # opening hours + payment methods
php artisan square:sync --full                          # products, prices, photos, stock from Square
```

Do not run `DemoCatalogSeeder` on the live site; products come from Square.
`ProductionSeeder` resets opening hours, so only run it once.

## 4. Square webhook (after the site is up)

1. Square Developer Dashboard → your app → **Webhooks** → Add subscription.
2. URL: `https://<your domain>/api/webhooks/square`
3. Events: `payment.created`, `payment.updated`, `catalog.version.updated`, `inventory.count.updated`.
4. Copy the **signature key** into Render (`SQUARE_WEBHOOK_SIGNATURE_KEY` in the
   `isaale-eko-shared` group) and set `SQUARE_WEBHOOK_URL` to exactly the URL above.

## 5. Your domain

isaale-eko → **Settings → Custom Domains** → add the domain and follow the DNS
instructions. HTTPS is automatic. Then update `APP_URL` (and the webhook URL) to the new address.

## 6. Going live with real payments

In the `isaale-eko-shared` environment group:

- `SQUARE_ENV` → `production`
- `SQUARE_ACCESS_TOKEN`, `SQUARE_LOCATION_ID`, `SQUARE_APP_ID` → production values
- an email provider (e.g. Postmark): `MAIL_MAILER=postmark`, `POSTMARK_API_KEY=...`

Then in the Shell: `php artisan square:sync --full --retire-demo`.

Before announcing: the `/admin` pages have **no login yet** and must be protected first.

## Troubleshooting

- **Logs:** each service → **Logs**. Until email is set up (`MAIL_MAILER=log`), sign-in codes appear there.
- **Health check:** `https://<your domain>/up` should say the app is up.
- **Sync by hand:** Shell → `php artisan square:sync`.
