#!/usr/bin/env bash
# Daily backup: database dump + uploaded files, kept for 14 days.
# Installed as a cron job for the deploy user (see docs/deploy-vps.md).
set -euo pipefail

cd "$(dirname "$0")"
DEST="${BACKUP_DIR:-$HOME/backups}"
STAMP="$(date +%Y-%m-%d_%H%M)"
mkdir -p "$DEST"
chmod 700 "$DEST"

set -a; . ./.env; set +a

docker compose exec -T db pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" --no-owner \
  | gzip > "$DEST/db_$STAMP.sql.gz"

docker compose exec -T app tar -C /var/www/html/storage/app -czf - . \
  > "$DEST/storage_$STAMP.tar.gz"

find "$DEST" -name '*.gz' -mtime +14 -delete
echo "Backup written: $DEST/db_$STAMP.sql.gz"
