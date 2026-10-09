# Evidencia M02 — 9 de octubre de 2026

Esta evidencia corresponde al checkout local en macOS Apple Silicon, Docker Linux ARM64 (`aarch64`). Se distingue de CI Linux AMD64 y de proveedores externos. Las capturas no implican aceptación visual final de Nicolás.

## Entorno comprobado

- PHP 8.4.26; Laravel 13.34.0.
- PostgreSQL 18.6; Redis 7.4.11.
- Livewire, Socialite, Microsoft y dependencias exactas: `composer.lock`.
- Node 22, Vite 8.3.2, Tailwind y Playwright: `package-lock.json` e imágenes fijadas por digest.

## Ejecuciones locales

El detalle final se completa después de las pruebas secuenciales. Las pruebas no contactan proveedores externos ni utilizan datos de desarrollo.

| Comando | Resultado |
| --- | --- |
| `./scripts/setup.sh` | PASS: dependencias lockfiles, migraciones pendientes y readiness; `.env` existentes preservados. |
| `./scripts/start.sh` | PASS: PostgreSQL/sesiones, clave Redis temporal, web con CSRF, worker y Mailpit activos. |
| `./scripts/check.sh` | PASS: Composer strict, Pint, Larastan nivel6 sin errores y build Vite. |
| `composer audit --locked` en imagen app | PASS: sin advisories conocidos al ejecutar. |
| `./scripts/test.sh` | Ejecución final pendiente de registrar. |
| `./scripts/e2e.sh` | Ejecución final pendiente de registrar. |
| Brevo SMTP real | NO EJECUTADO: sin configuración externa ni destinatario autorizado para envío real. |
| Google real | NO EJECUTADO: proveedor deshabilitado hasta configurar aplicación/credenciales. |
| Microsoft real | NO EJECUTADO: proveedor deshabilitado hasta configurar Entra/credenciales/cuentas autorizadas. |
| Zoom real del navegador al200% | NO EJECUTADO: control UI de Chrome no disponible/sin permisos; reflow equivalente640×360 cubierto por E2E, no se equipara a prueba manual de zoom. |

Hubo fallos intermedios corregidos en expectativas de tests, aislamiento de contadores y un doble de sesión de Socialite. El resultado anterior no sustituye la ejecución final. PHPUnit fuerza caché array por proceso; E2E usa prefijo Redis nuevo por ejecución, sin flush. Ambos mantienen PostgreSQL `testing`/`test-postgres`/`estuflow_test` y se ejecutan secuencialmente.

## GitHub Actions

Fallo previo confirmado en [run37083754713](https://github.com/NicolasMRuiz/estuflow/actions/runs/37083754713): setup, check y PHPUnit PASS; E2E FAIL antes de ejecutar los recorridos, por `chrome_crashpad_handler: --database is required` y salida Chromium SIGTRAP. No fue un PASS remoto de M01. M02 configura `XDG_CONFIG_HOME`/`XDG_CACHE_HOME` escribibles para el UID del contenedor, sin desactivar TLS, firmas o checks.

Actions M02 ejecuta setup → check → PHPUnit → E2E y publica únicamente las capturas PNG como artifact durante14días. El resultado del commit publicado se registra en la PR y en la entrega final, con enlace a la corrida real. Consultar los checks de esa PR; el PASS local no acredita CI. No se desactivaron verificaciones para obtener verde.

## Capturas y revisión visual

Se guardan bajo esta carpeta, con cuentas ficticias, passwords vacíos, sin barra URL, tokens visibles, cookies, bandejas ni trazas. El helper rechaza capturas si contiene un password poblado.

- `login-before-2560x1440.png`: baseline QHD real antes del ajuste; columna izquierda anclada al borde con mucho espacio vacío. Medidas y alcance de reproducción en [UI](../../ui.md).
- `login-390x844.png`, `login-1440x900.png`, `login-2560x1440.png`, `login-3840x2160.png`.
- `dashboard-390x844.png`, `dashboard-1440x900.png`, `dashboard-2560x1440.png`, `dashboard-3840x2160.png` y móvil completo.
- Estados nuevos de registro, recuperación, reset, verificación, enlace inválido, errores inline y métodos de acceso, cuando generados por E2E.

La matriz comprueba320×740,390×844,430×932,768×1024,1280×720,1440×900,1920×1080,2560×1440 y3840×2160 CSSpx. Las capturas se inspeccionan además de las aserciones de overflow/alineación; reflow a320px no depende del zoom del sistema.

## Límites pendientes

Pruebas reales Brevo/Google/Microsoft y consentimiento institucional, zoom manual200%, Safari/Firefox y auditoría completa con lector de pantalla. [Correo](../../mail.md) y [OAuth](../../oauth.md) detallan exactamente cómo activar las integraciones. M02 no despliega ni implementa matrícula/modelo académico.
