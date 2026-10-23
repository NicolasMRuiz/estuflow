#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
docker compose up -d --wait postgres redis
# Stop long-lived PHP processes before migrations/cache changes; volumes stay mounted.
docker compose stop app worker scheduler
docker compose run --rm --no-deps app php artisan config:clear
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan catalog:import
docker compose run --rm app php artisan environment:check
docker compose up -d --wait mailpit
docker compose up -d --wait --force-recreate app worker scheduler
docker compose exec -T app php -r '
$html = file_get_contents("http://127.0.0.1:8000/login");
if (!str_contains($html, "Retomá tu camino.") || !str_contains($html, "name=\"_token\"")) { exit(1); }
echo "PASS: formulario de login servido con protección CSRF.\n";
'
