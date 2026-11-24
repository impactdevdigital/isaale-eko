# Deploying to the client's server (VPS)

Server: Ubuntu 24.04 at `203.161.46.218`. Everything runs in Docker Compose from
`deploy/vps/`: the website (`app`), the queue `worker`, the `scheduler`, Postgres (`db`)
and Caddy (HTTPS). Unlike the Render free plan, the worker and the 5-minute Square
sync actually run here.

## Access and security

- Log in as **`deploy`** only: `ssh deploy@203.161.46.218` (SSH key, no password).
  Root login and password login are switched off (`/etc/ssh/sshd_config.d/01-hardening.conf`).
  If you get locked out, use the hosting provider's web console.
- To give someone access, add their **public** key to `/home/deploy/.ssh/authorized_keys`.
- Firewall (ufw): only 22, 80 and 443 are open. fail2ban bans repeated SSH failures.
  Security updates install automatically (unattended-upgrades).
- Only Caddy publishes ports. Docker skips ufw for published ports, so never add
  `ports:` to `db` or `app` in `compose.yml`.

## Layout on the server

| Path | What |
| --- | --- |
| `~/isaale-eko` | git clone of this repo |
| `~/isaale-eko/deploy/vps/.env` | secrets and settings (chmod 600, not in git) |
| `~/backups` | daily database + uploads backups, 14 days kept |

## Deploying an update

```sh
ssh deploy@203.161.46.218
~/isaale-eko/deploy/vps/deploy.sh          # deploys origin/main
~/isaale-eko/deploy/vps/deploy.sh my-branch  # or another branch, for testing
```

It pulls the code, rebuilds the image, restarts the containers, and the app runs
migrations and `shop:install` when it starts.

## Everyday commands (from `~/isaale-eko/deploy/vps`)

```sh
docker compose ps                          # what is running
docker compose logs -f app                 # site log (emails show here in test mode)
docker compose logs -f worker scheduler    # background jobs and the Square sync
docker compose exec app php artisan ...    # any artisan command
./backup.sh                                # backup now
```

## Test mode → live

The server starts in **test mode**: Square sandbox keys, emails written to the log,
plain HTTP on the IP. To go live, in `deploy/vps/.env`:

1. Point the domain's DNS A record at `203.161.46.218`, set `SITE_ADDRESS=isaleeeko.com`
   (Caddy fetches the certificate), `APP_URL=https://isaleeeko.com`, `SESSION_SECURE_COOKIE=true`.
2. Put in the production Square keys (`SQUARE_ENV=production`, token, location, app ID),
   set the Square webhook to `https://isaleeeko.com/api/webhooks/square` and copy its
   signature key into `SQUARE_WEBHOOK_SIGNATURE_KEY` / `SQUARE_WEBHOOK_URL`.
3. Set a real mailer (`MAIL_MAILER=resend`, `RESEND_API_KEY`, `MAIL_FROM_ADDRESS`).
4. `docker compose up -d` then `docker compose exec app php artisan square:sync --full --retire-demo`.

## Restoring a backup

```sh
gunzip -c ~/backups/db_YYYY-MM-DD_HHMM.sql.gz | docker compose exec -T db psql -U isaale_eko -d isaale_eko
docker compose exec -T app tar -C /var/www/html/storage/app -xzf - < ~/backups/storage_YYYY-MM-DD_HHMM.tar.gz
```
