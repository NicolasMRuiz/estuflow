FROM node:22-bookworm-slim@sha256:43ac6c60b8f89723f746e8a92ce91abd5017e627ce1ddfe4238355d3a30b772c
RUN --mount=type=secret,id=cloud_ca,target=/usr/local/share/ca-certificates/cloud-ca.crt \
    apt-get update && apt-get install -y --no-install-recommends ca-certificates chromium \
    && update-ca-certificates && rm -rf /var/lib/apt/lists/*
ENV XDG_CONFIG_HOME=/tmp/estuflow-chromium-config
ENV XDG_CACHE_HOME=/tmp/estuflow-chromium-cache
WORKDIR /app
ENV PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH=/usr/bin/chromium
