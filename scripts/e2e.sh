#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
export E2E_CACHE_PREFIX="estuflow_e2e_$(date +%s)_$$_"
docker compose build browser
docker compose --profile test stop test-control test-worker test-app
docker compose --profile test up -d --wait test-postgres redis test-mailpit
docker compose run --rm --no-deps test-app php artisan testing:prepare --env=testing
trap 'docker compose --profile test stop test-control test-worker test-app' EXIT
if [[ "$#" -gt 0 ]]; then
  docker compose --profile test up -d --wait test-app test-worker test-control
  docker compose run --rm browser npm run test:e2e -- "$@"
else
  # Each specification has an isolated limiter/cache namespace. Production limits
  # remain active within a specification; no flush, bypass or shared-prefix reset.
  suite_prefix="$E2E_CACHE_PREFIX"
  for specification in tests/e2e/*.spec.js; do
    export E2E_CACHE_PREFIX="${suite_prefix}$(basename "$specification" .spec.js)_"
    docker compose --profile test up -d --wait test-app test-worker test-control
    docker compose run --rm browser npm run test:e2e -- "$specification"
  done
fi
