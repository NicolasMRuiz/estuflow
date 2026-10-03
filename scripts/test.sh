#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
docker compose --profile test up -d --wait test-postgres redis
docker compose run --rm --no-deps test-app php artisan test --env=testing
