#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
docker compose run --rm --no-deps app composer validate --strict
docker compose run --rm --no-deps app composer format:check
docker compose run --rm --no-deps app composer analyse
docker compose run --rm node npm run build
