#!/usr/bin/env bash
# Pull the latest code, rebuild the image and restart the containers.
# Usage (as deploy, on the server):  ~/isaale-eko/deploy/vps/deploy.sh [branch]
set -euo pipefail

BRANCH="${1:-main}"
REPO_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$REPO_DIR"

echo "==> Updating code to origin/$BRANCH"
git fetch --quiet origin "$BRANCH"
git checkout --quiet -B "$BRANCH" "origin/$BRANCH"

cd deploy/vps
[ -f .env ] || { echo "deploy/vps/.env is missing (copy .env.example)"; exit 1; }

echo "==> Building image"
docker compose build app

echo "==> Restarting containers"
docker compose up -d --remove-orphans

echo "==> Waiting for the site"
for i in $(seq 1 60); do
  if [ "$(docker inspect -f '{{.State.Health.Status}}' "$(docker compose ps -q app)" 2>/dev/null)" = "healthy" ]; then
    break
  fi
  sleep 3
done
docker compose ps

docker image prune -f >/dev/null
echo "==> Deployed $(git -C "$REPO_DIR" log --oneline -1)"
