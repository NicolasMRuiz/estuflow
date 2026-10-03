#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
export LOCAL_UID="$(id -u)" LOCAL_GID="$(id -g)"
if [[ -f docker/compose.cloud.yaml ]]; then
  export COMPOSE_FILE=compose.yaml:docker/compose.cloud.yaml
  export BUILDX_CONFIG=/tmp/estuflow-buildx
fi
