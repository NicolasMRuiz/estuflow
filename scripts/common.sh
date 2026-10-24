#!/usr/bin/env bash
set -euo pipefail
command -v docker >/dev/null || { echo "Falta Docker. Consultá docs/installation.md para tu plataforma." >&2; exit 1; }
docker compose version >/dev/null || { echo "Falta Docker Compose v2. Consultá docs/installation.md." >&2; exit 1; }
docker info >/dev/null 2>&1 || { echo "Docker no está disponible. Iniciá Docker Desktop o el daemon y confirmá que use contenedores Linux (docs/installation.md)." >&2; exit 1; }
cd "$(dirname "${BASH_SOURCE[0]}")/.."
export LOCAL_UID="$(id -u)" LOCAL_GID="$(id -g)"
if [[ -f docker/compose.cloud.yaml ]]; then
  export COMPOSE_FILE=compose.yaml:docker/compose.cloud.yaml
  export BUILDX_CONFIG=/tmp/estuflow-buildx
fi
