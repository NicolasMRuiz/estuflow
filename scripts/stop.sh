#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
docker compose stop app worker scheduler mailpit
