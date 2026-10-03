# Evidencia ejecutada

Validación original: 2 de octubre de 2026, Docker en Linux AMD64. La entrega se guardó posteriormente en `main` por autorización explícita de Nicolás. No se publicó la aplicación en un hosting; la aceptación visual sigue pendiente.

## Versiones observadas

| Herramienta | Versión |
| --- | --- |
| PHP | 8.4.26 |
| Composer | 2.9.8 |
| Laravel | 13.34.0 |
| Livewire | 4.4.7 |
| Node | 22.23.3 |
| Tailwind | 4.3.3 |
| Vite | 8.3.2 |
| PostgreSQL | 18.6 |
| Redis | 7.4.11 |
| Pint | 1.32.1 |
| Larastan | 3.12.2, nivel 6 |
| PHPUnit | 12.5.37 |
| Playwright | 1.58.2 |
| Chromium | 154.0.8037.92, Debian Bookworm |

Los digests de todas las imágenes base contienen plataformas Linux ARM64 y AMD64, comprobadas con `docker buildx imagetools inspect --raw`. Esto confirma disponibilidad de imágenes, no ejecución en una Mac real.

## PASS

| Comando o comprobación | Resultado |
| --- | --- |
| `./scripts/setup.sh` | Instalación congelada con ambos lockfiles, build y servicios. Repetido con exit 0; segunda migración «Nothing to migrate». |
| Comparación SHA256 antes/después del setup | `.env`, `.env.testing`, `PROMPT.md`, `composer.lock` y `package-lock.json` conservados. |
| `docker compose config --quiet` | Configuración válida. |
| `./scripts/start.sh` | Arranque tras detener la web, healthchecks, PostgreSQL/sesiones, operación temporal Redis y login servido con CSRF; exit 0. |
| `./scripts/check.sh` | Composer validate estricto, Pint, Larastan sin errores y build Vite; exit 0. |
| `./scripts/test.sh` | **19 pruebas pasadas, 85 assertions**, PostgreSQL real y base aislada; exit 0. |
| `./scripts/e2e.sh` | **3 pruebas pasadas**, sin skipped/flaky/unexpected; exit 0. |
| Peticiones locales | Login HTTP 200; dashboard anónimo HTTP 302 a login. |
| Redis | Escritura/lectura correcta; eliminada únicamente la clave temporal UUID. |
| Guarda de reset | `testing:prepare` en desarrollo termina con error antes de migrar, como se espera; guardas cubiertas también por tres tests. |
| `docker compose run --rm node npm audit --json` | 0 vulnerabilidades informadas al momento de ejecución. |
| Revisión de Git | Código local nuevo; entorno, cachés, vendor, node_modules y artefactos privados ignorados. |
| Aislamiento E2E | La base de desarrollo conservó 0 cuentas; E2E usó únicamente cuentas ficticias en otro servicio PostgreSQL. |

El E2E ejecutó registro → dashboard → logout → login, rechazo de logout con token inválido, login inválido con errores asociados, foco con teclado y menú móvil. Se comprobó ausencia de scroll horizontal a 390 y 320 px. Las capturas de dashboard y formularios fueron inspeccionadas visualmente.

## FAIL / bloqueo externo opcional

`docker compose run --rm --no-deps app composer audit --format=json` terminó con exit 100: el proxy devolvió HTTP 403 para `packagist.org/api/security-advisories/`. La auditoría Composer **no se completó**; esto no prueba ausencia ni presencia de vulnerabilidades. Se guardó `packagist.org` en los dominios del borrador del entorno para habilitar una repetición después de aplicar esa configuración.

Se guardó también `api.github.com`, bloqueado durante la instalación inicial. Esa instalación se resolvió con archivos del destino oficial `codeload.github.com` y la caché de Composer, en las referencias exactas del lockfile, sin cambiar checksums ni desactivar TLS. Los primeros E2E fallaron porque Chromium enviaba el hostname interno al proxy; la conexión directa a la red aislada de Compose corrigió ese problema. No hay fallos pendientes en los checks requeridos.

## NO EJECUTADO

- Ejecución real en macOS Apple Silicon, Safari/WebKit o Firefox.
- CI en GitHub: el workflow está preparado, pero no fue publicado ni disparado.
- Restauración en una nueva tarea cloud/publicación del snapshot. Solo se validaron esta instancia y las instrucciones guardadas.
- Instalación desde GitHub en una Mac real. El código de esta entrega se conserva en `main`; ejecutar el setup en Apple Silicon sigue pendiente.
- Auditoría completa de accesibilidad o aceptación visual de Nicolás.

## Archivos de evidencia

- `laravel-tests.txt`, `checks.txt`, `e2e.txt`, `startup.txt`: salidas reales sin códigos ANSI.
- `dashboard-1440x900.png`, `dashboard-390x844.png`: dimensiones solicitadas, cuenta ficticia.
- `dashboard-390-full.png`, `dashboard-320-full.png`: vistas completas para revisión de reflow.
- `login-1440x900.png`, `registro-390x844.png`: formularios vacíos, sin credenciales.
- `test-results/e2e-results.json`: reporte estructurado de la última corrida, ignorado por Git.

`install_script`, `start_skill` y los dominios `api.github.com`/`packagist.org` quedaron persistidos en el borrador del entorno. Guardar ese borrador no publica ni aplica por sí mismo la política a esta instancia. No se agregaron requisitos de secretos externos.
