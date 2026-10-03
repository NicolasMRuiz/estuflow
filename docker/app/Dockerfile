FROM composer:2.9@sha256:b09bccd91a78fe8a9ab4b33d707b862e8fe54fec17782e32683ad2a69c46867d AS composer
FROM php:8.4-cli-bookworm@sha256:f1d32fb402fffba0b3dd8ba8c0aca474c9e9f04395fa846eedea77c503257dee

# Optional trust bundle supplied only by a managed cloud environment.
RUN --mount=type=secret,id=cloud_ca,target=/usr/local/share/ca-certificates/cloud-ca.crt \
    update-ca-certificates && apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libonig-dev libzip-dev libicu-dev libxml2-dev \
    && docker-php-ext-install -j2 pdo_pgsql mbstring zip intl bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
ENV COMPOSER_HOME=/tmp/composer
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
