#!/usr/bin/env bash
source "$(dirname "$0")/common.sh"
docker compose up -d --wait postgres redis
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan environment:check
docker compose up -d --wait app
docker compose exec -T app php -r '
$html = file_get_contents("http://127.0.0.1:8000/login");
if (!str_contains($html, "Retomá tu camino.") || !str_contains($html, "name=\"_token\"")) { exit(1); }
echo "PASS: formulario de login servido con protección CSRF.\n";
'
