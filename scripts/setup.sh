#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
if [[ -n "${CODEX_ENVIRONMENT_ID:-}" && -n "${HTTPS_PROXY:-}" ]]; then
  python3 scripts/cloud-setup.py
  source scripts/common.sh
fi
docker run --rm --user "$LOCAL_UID:$LOCAL_GID" -v "$PWD:/app" -w /app \
  php:8.4-cli-bookworm@sha256:f1d32fb402fffba0b3dd8ba8c0aca474c9e9f04395fa846eedea77c503257dee php scripts/init-env.php
docker compose build app
docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
docker compose run --rm node npm ci
docker compose run --rm node npm run build
docker compose stop app worker scheduler
docker compose run --rm --no-deps app php artisan config:clear
docker compose up -d --wait postgres redis
# Pending migrations only. Never migrate:fresh/reset on development.
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan catalog:import
docker compose run --rm app php artisan environment:check
