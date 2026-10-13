#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
export E2E_CACHE_PREFIX="estuflow_e2e_$(date +%s)_$$_"
docker compose build browser
docker compose --profile test stop test-worker test-app
docker compose --profile test up -d --wait test-postgres redis test-mailpit
docker compose run --rm --no-deps test-app php artisan testing:prepare --env=testing
trap 'docker compose --profile test stop test-worker test-app' EXIT
docker compose --profile test up -d --wait test-app test-worker
docker compose run --rm browser npm run test:e2e
