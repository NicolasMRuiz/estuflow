# Pruebas y aislamiento

Desde la raíz del proyecto, con Docker funcionando:

```bash
./scripts/setup.sh
./scripts/check.sh
./scripts/test.sh
./scripts/e2e.sh
```

`check.sh` valida Composer, Pint sin cambiar archivos, Larastan nivel 6 sobre `app/` sin ignores generales y build frontend. Para aplicar formato: `docker compose run --rm --no-deps app composer format`.

## Bases y resets

`test.sh` arranca `test-postgres` y Redis y ejecuta Laravel con `.env.testing`. La persistencia usa PostgreSQL 18 con `RefreshDatabase`, nunca SQLite. Antes de que el trait migre, `Tests\TestCase::createApplication()` y el proveedor validan entorno, driver, host y nombre explícito de base.

`e2e.sh` llama a `testing:prepare --env=testing`, que comprueba esas guardas antes de `migrate:fresh`. **Solo recrea `estuflow_test`**, sobre otro servidor y volumen. Luego arranca `test-app`, sin puerto publicado, y un navegador en la red interna. Playwright rechaza cualquier URL diferente de `http://test-app:8000` y exige `E2E_ISOLATED=1`.

Ningún script ejecuta `down -v` ni reset sobre desarrollo. PHPUnit/E2E deben ejecutarse secuencialmente porque comparten la base aislada de pruebas, que contiene únicamente datos ficticios.

## Cobertura

Registro válido/inválido, email normalizado/duplicado, confirmación y hash; login válido/inválido, rate limit, regeneración de sesión y password ausente del flash; dashboard anónimo, identidad de dos cuentas y render Livewire; logout POST, invalidación y token nuevo; entradas malformadas y guardas de reset.

E2E: registro → dashboard → logout → login, CSRF real, errores accesibles, teclado, menú móvil y ausencia de scroll horizontal a 390 y 320 px. Laravel omite CSRF en tests HTTP internos: por eso se comprueba en navegador real. Laravel 13 incluye protección sensible al origen; se usa el middleware oficial.

## Evidencia y límites

Playwright genera `test-results/e2e-results.json` (ignorado) y capturas del dashboard ficticio en `docs/evidence/`. No se guardan trazas, vídeo ni capturas automáticas de formularios, para evitar registrar credenciales/cookies. Los resultados y versiones ejecutadas están en `docs/evidence/checks.md`.

Chromium se instala desde Debian en Docker; Playwright queda fijado en npm. WebKit/Safari y Firefox son checks opcionales futuros. La revisión de accesibilidad cubre estructura, labels, foco y teclado; no constituye una auditoría completa con lectores de pantalla.
