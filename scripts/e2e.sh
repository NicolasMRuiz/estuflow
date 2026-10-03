#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
docker compose build browser
docker compose --profile test up -d --wait test-postgres redis
docker compose run --rm --no-deps test-app php artisan testing:prepare --env=testing
docker compose --profile test up -d --wait test-app
docker compose run --rm browser npm run test:e2e
